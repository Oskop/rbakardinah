<?php

namespace App\Services\Auth\Oidc;

use App\Exceptions\OidcAuthenticationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SimrsOidcService
{
    /**
     * Authenticate employee credentials against SIMRS OIDC Server via ROPC.
     *
     * @throws OidcAuthenticationException
     */
    public function authenticate(string $username, string $password): array
    {
        $endpoint = config('simrs_oidc.token_endpoint');
        $clientId = config('simrs_oidc.client_id');
        $clientSecret = config('simrs_oidc.client_secret');
        $scope = config('simrs_oidc.scope', 'openid profile email simrs:pegawai');
        $timeout = config('simrs_oidc.timeout_seconds', 10);

        try {
            $response = Http::asForm()
                ->timeout($timeout)
                ->post($endpoint, [
                    'grant_type' => 'password',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'username' => $username,
                    'password' => $password,
                    'scope' => $scope,
                ]);
        } catch (Throwable $e) {
            Log::error('SIMRS OIDC Connection Error: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'username' => $username,
            ]);

            throw new OidcAuthenticationException(
                'Tidak dapat terhubung ke server autentikasi SIMRS. Silakan periksa koneksi jaringan atau gunakan Login Akun Lokal.'
            );
        }

        if ($response->failed()) {
            $errorData = $response->json();
            $errorMsg = $errorData['error_description'] ?? $errorData['message'] ?? $errorData['error'] ?? null;

            Log::warning('SIMRS OIDC Login Failed', [
                'status' => $response->status(),
                'error' => $errorMsg,
                'username' => $username,
            ]);

            if ($response->status() === 400 || $response->status() === 401) {
                throw new OidcAuthenticationException('Username atau kata sandi akun SIMRS salah.');
            }

            throw new OidcAuthenticationException(
                $errorMsg ? "Autentikasi SIMRS gagal: {$errorMsg}" : 'Autentikasi gagal pada server SIMRS. Silakan coba lagi.'
            );
        }

        $tokenData = $response->json();

        if (!isset($tokenData['access_token'])) {
            throw new OidcAuthenticationException('Respons dari server SIMRS tidak memiliki access token yang valid.');
        }

        return $tokenData;
    }

    /**
     * Safely parse JWT payload (ID Token or Access Token) without third-party dependencies.
     */
    public function parseJwtPayload(?string $jwt): array
    {
        if (!$jwt || !is_string($jwt)) {
            return [];
        }

        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return [];
        }

        $payload = $parts[1];
        $remainder = strlen($payload) % 4;
        if ($remainder) {
            $payload .= str_repeat('=', 4 - $remainder);
        }

        $decodedJson = base64_decode(strtr($payload, '-_', '+/'));
        if (!$decodedJson) {
            return [];
        }

        $claims = json_decode($decodedJson, true);
        return is_array($claims) ? $claims : [];
    }

    /**
     * Synchronize or Just-In-Time (JIT) provision the SIMRS user into SIPAKAR.
     * Existing user roles and unit associations are strictly preserved.
     */
    public function syncUser(array $tokenData, string $usernameInput): User
    {
        $idToken = $tokenData['id_token'] ?? null;
        $claims = $this->parseJwtPayload($idToken);

        // Extract identifier & profile claims
        $sub = $claims['sub'] ?? $claims['id'] ?? null;
        $nip = $claims['nip'] ?? $claims['preferred_username'] ?? $usernameInput;
        $name = $claims['name'] ?? $claims['nama'] ?? "Pegawai {$nip}";
        
        // Email fallback: use claim email or construct default domain email
        $email = $claims['email'] ?? "{$nip}@rsudkardinah.tegal.go.id";

        // Find existing user: Match by simrs_sub, nip, or email
        $user = null;
        if ($sub) {
            $user = User::where('simrs_sub', $sub)->first();
        }
        if (!$user && $nip) {
            $user = User::where('nip', $nip)->first();
        }
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        // If user already exists in SIPAKAR: Update SSO metadata but PRESERVE existing role & unit
        if ($user) {
            $user->update([
                'name' => $name ?: $user->name,
                'email' => $email ?: $user->email,
                'simrs_sub' => $sub ?: $user->simrs_sub,
                'nip' => $nip ?: $user->nip,
                'auth_provider' => 'simrs_oidc',
                'simrs_metadata' => $claims,
            ]);

            return $user;
        }

        // If new user: JIT Provision with configured default role (Operator)
        $defaultRole = config('simrs_oidc.default_role', 'Operator');

        return User::create([
            'name' => $name,
            'email' => $email,
            'simrs_sub' => $sub,
            'nip' => $nip,
            'password' => Hash::make(Str::random(32)),
            'role' => $defaultRole,
            'auth_provider' => 'simrs_oidc',
            'simrs_metadata' => $claims,
            'is_active' => true,
        ]);
    }
}
