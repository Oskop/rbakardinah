<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\OidcAuthenticationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SimrsSsoLoginRequest;
use App\Services\Auth\Oidc\SimrsOidcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

            // 2. Synchronize / JIT Provision user into SIPAKAR
            $user = $this->oidcService->syncUser(
                $tokenData,
                $request->input('username_simrs')
            );

            if (!$user->is_active) {
                return back()->withErrors([
                    'username_simrs' => 'Akun Anda dinonaktifkan di SIPAKAR. Silakan hubungi Administrator.',
                ])->withInput($request->only('username_simrs'));
            }

            // 3. Save tokens into session for downstream SIMRS API communications
            session([
                'simrs_access_token' => $tokenData['access_token'] ?? null,
                'simrs_refresh_token' => $tokenData['refresh_token'] ?? null,
                'simrs_token_expires_in' => $tokenData['expires_in'] ?? null,
            ]);

            // 4. Log in user & regenerate session
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // 5. Role-based redirect
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
