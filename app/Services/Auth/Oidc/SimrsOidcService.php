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
     * Fetch enriched employee profile from SIMRS UserInfo endpoint.
     */
    public function fetchUserInfo(string $accessToken): ?array
    {
        $endpoint = config('simrs_oidc.userinfo_endpoint');
        $timeout = config('simrs_oidc.timeout_seconds', 10);

        try {
            $response = Http::withToken($accessToken)
                ->timeout($timeout)
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? $data : null;
            }

            Log::warning('SIMRS OIDC UserInfo fetch returned non-200 status', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Throwable $e) {
            Log::warning('SIMRS OIDC UserInfo Error: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
            ]);
        }

        return null;
    }

    /**
     * Refresh an expired or near-expiry access token using the refresh token flow.
     */
    public function refreshToken(?string $refreshToken = null): ?array
    {
        $refreshToken = $refreshToken ?: session('simrs_refresh_token');

        if (!$refreshToken) {
            return null;
        }

        $endpoint = config('simrs_oidc.token_endpoint');
        $clientId = config('simrs_oidc.client_id');
        $clientSecret = config('simrs_oidc.client_secret');
        $timeout = config('simrs_oidc.timeout_seconds', 10);

        try {
            $response = Http::asForm()
                ->timeout($timeout)
                ->post($endpoint, [
                    'grant_type' => 'refresh_token',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();

                if (isset($tokenData['access_token'])) {
                    $expiresIn = (int) ($tokenData['expires_in'] ?? 900);

                    session([
                        'simrs_access_token' => $tokenData['access_token'],
                        'simrs_refresh_token' => $tokenData['refresh_token'] ?? $refreshToken,
                        'simrs_token_expires_in' => $expiresIn,
                        'simrs_token_expires_at' => now()->addSeconds($expiresIn)->timestamp,
                    ]);

                    return $tokenData;
                }
            }

            Log::warning('SIMRS OIDC Refresh Token Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Throwable $e) {
            Log::error('SIMRS OIDC Refresh Token Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get a valid, active SIMRS access token from the session, automatically refreshing if necessary.
     */
    public function getValidAccessToken(): ?string
    {
        $accessToken = session('simrs_access_token');
        $expiresAt = session('simrs_token_expires_at');

        if (!$accessToken) {
            return null;
        }

        // If expiresAt is set and token expires within 60 seconds, trigger refresh flow
        if ($expiresAt && now()->timestamp >= ($expiresAt - 60)) {
            $refreshed = $this->refreshToken();
            return $refreshed['access_token'] ?? $accessToken;
        }

        return $accessToken;
    }

    /**
     * Revoke tokens on SIMRS OIDC Server during Single Logout (SLO).
     * Fails gracefully to never block local SIPAKAR logout.
     */
    public function revokeToken(?string $refreshToken = null): bool
    {
        $refreshToken = $refreshToken ?: session('simrs_refresh_token');

        if (!$refreshToken) {
            return true;
        }

        $endpoint = config('simrs_oidc.logout_endpoint');
        $clientId = config('simrs_oidc.client_id');
        $clientSecret = config('simrs_oidc.client_secret');

        try {
            $response = Http::asForm()
                ->timeout(3) // Fast timeout for logout
                ->post($endpoint, [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                ]);

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('SIMRS OIDC Single Logout Revocation warning: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
            ]);
            return false;
        }
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
     * Combines JWT claims with UserInfo profile data.
     * Existing user roles and unit associations in SIPAKAR are strictly preserved.
     */
    public function syncUser(array $tokenData, string $usernameInput, ?array $userInfo = null): User
    {
        $idToken = $tokenData['id_token'] ?? null;
        $claims = $this->parseJwtPayload($idToken);

        // Combined metadata from JWT claims and UserInfo profile
        $metadata = array_merge($claims, $userInfo ?? []);

        // Extract identifier & profile claims with UserInfo prioritized
        $sub = $userInfo['sub'] ?? $claims['sub'] ?? $claims['id'] ?? null;
        $nip = $userInfo['username'] ?? $claims['nip'] ?? $claims['preferred_username'] ?? $usernameInput;
        $name = $userInfo['name'] ?? $claims['name'] ?? $claims['nama'] ?? "Pegawai {$nip}";
        
        // Email fallback: use UserInfo email -> claim email -> default domain email
        $email = $userInfo['email'] ?? $claims['email'] ?? "{$nip}@rsudkardinah.tegal.go.id";

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
                'simrs_metadata' => $metadata,
            ]);

            return $user;
        }

        // If new user: JIT Provision with configured default role (Operator) and unassigned unit (null)
        $defaultRole = config('simrs_oidc.default_role', 'Operator');

        return User::create([
            'name' => $name,
            'email' => $email,
            'simrs_sub' => $sub,
            'nip' => $nip,
            'password' => Hash::make(Str::random(32)),
            'role' => $defaultRole,
            'auth_provider' => 'simrs_oidc',
            'simrs_metadata' => $metadata,
            'is_active' => true,
        ]);
    }
}
