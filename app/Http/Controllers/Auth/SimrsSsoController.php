<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\OidcAuthenticationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SimrsSsoLoginRequest;
use App\Services\Auth\Oidc\SimrsOidcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SimrsSsoController extends Controller
{
    public function __construct(
        protected SimrsOidcService $oidcService
    ) {}

    /**
     * Handle an incoming SIMRS SSO authentication request.
     */
    public function login(SimrsSsoLoginRequest $request): RedirectResponse
    {
        if (!config('simrs_oidc.enabled')) {
            return back()->withErrors([
                'username_simrs' => 'Layanan Login SSO SIMRS sedang dinonaktifkan. Silakan gunakan Akun Lokal.',
            ])->withInput($request->only('username_simrs'));
        }

        try {
            // 1. Authenticate against SIMRS OIDC Server via ROPC
            $tokenData = $this->oidcService->authenticate(
                $request->input('username_simrs'),
                $request->input('password_simrs')
            );

            // 2. Fetch enriched employee profile from UserInfo endpoint (if available)
            $userInfo = null;
            if (!empty($tokenData['access_token'])) {
                $userInfo = $this->oidcService->fetchUserInfo($tokenData['access_token']);
            }

            // 3. Synchronize / JIT Provision user into SIPAKAR with merged claims and UserInfo
            $user = $this->oidcService->syncUser(
                $tokenData,
                $request->input('username_simrs'),
                $userInfo
            );

            if (!$user->is_active) {
                return back()->withErrors([
                    'username_simrs' => 'Akun Anda dinonaktifkan di SIPAKAR. Silakan hubungi Administrator.',
                ])->withInput($request->only('username_simrs'));
            }

            // 4. Save tokens and expiration into session for downstream SIMRS API communications & token refresh
            $expiresIn = (int) ($tokenData['expires_in'] ?? 900);
            session([
                'simrs_access_token' => $tokenData['access_token'] ?? null,
                'simrs_refresh_token' => $tokenData['refresh_token'] ?? null,
                'simrs_token_expires_in' => $expiresIn,
                'simrs_token_expires_at' => now()->addSeconds($expiresIn)->timestamp,
            ]);

            // 5. Log in user & regenerate session
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // 6. Role-based redirect
            $url = match ($user->role) {
                'Administrator' => route('admin.dashboard'),
                'Supervisor' => route('supervisor.dashboard'),
                'Operator' => route('operator.dashboard'),
                default => route('dashboard'),
            };

            return redirect()->intended($url);

        } catch (OidcAuthenticationException $e) {
            return back()->withErrors([
                'username_simrs' => $e->getMessage(),
            ])->withInput($request->only('username_simrs'));
        }
    }
}
