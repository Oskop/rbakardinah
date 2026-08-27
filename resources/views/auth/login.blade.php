<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $oidcEnabled = config('simrs_oidc.enabled', true);
        $hasLocalErrors = $errors->has('email') || $errors->has('password');
        $initialTab = ($oidcEnabled && !$hasLocalErrors) ? 'simrs' : 'local';
    @endphp

    <div x-data="{ tab: '{{ old('_login_tab', $initialTab) }}' }">
        @if($oidcEnabled)
            <!-- Dual-Tab Mode Switcher -->
            <div class="flex p-1 mb-6 bg-slate-100/80 rounded-2xl border border-slate-200 text-xs font-bold">
                <button type="button" @click="tab = 'simrs'"
                    :class="tab === 'simrs' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                    class="flex-1 py-2.5 rounded-xl transition flex items-center justify-center gap-1.5">
                    <span>🏥</span>
                    <span>Pegawai SIMRS (SSO)</span>
                </button>
                <button type="button" @click="tab = 'local'"
                    :class="tab === 'local' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                    class="flex-1 py-2.5 rounded-xl transition flex items-center justify-center gap-1.5">
                    <span>🔐</span>
                    <span>Akun Lokal SIPAKAR</span>
                </button>
            </div>
        @endif

        @if($oidcEnabled)
            <!-- TAB 1: FORM LOGIN SIMRS SSO -->
            <form x-cloak x-show="tab === 'simrs'" style="{{ $initialTab === 'simrs' ? '' : 'display: none;' }}" method="POST" action="{{ route('login.sso') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="_login_tab" value="simrs">

                <div class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl text-xs text-indigo-900 flex items-start gap-2">
                    <span class="text-base flex-shrink-0">💡</span>
                    <p class="leading-relaxed">
                        Masuk menggunakan <strong>Username</strong> dan <strong>Kata Sandi</strong> akun resmi <strong>SIGITA SEHATI</strong>.
                    </p>
                </div>

                <!-- Username / NIP SIMRS -->
                <div>
                    <label for="username_simrs" class="block text-sm font-semibold text-slate-700 mb-1.5">Username SIGITA SEHATI</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <input id="username_simrs" 
                               type="text" 
                               name="username_simrs" 
                               value="{{ old('username_simrs') }}" 
                               required 
                               autofocus 
                               autocomplete="username"
                               placeholder="Contoh: nama.anda"
                               class="block w-full pl-11 pr-4 py-3 bg-white/50 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm text-sm"
                        >
                    </div>
                    <x-input-error :messages="$errors->get('username_simrs')" class="mt-2" />
                </div>

                <!-- Password SIMRS -->
                <div>
                    <label for="password_simrs" class="block text-sm font-semibold text-slate-700 mb-1.5">Kata Sandi SIGITA SEHATI</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input id="password_simrs" 
                               type="password"
                               name="password_simrs"
                               required 
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="block w-full pl-11 pr-4 py-3 bg-white/50 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm text-sm"
                        >
                    </div>
                    <x-input-error :messages="$errors->get('password_simrs')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input id="remember_simrs" type="checkbox" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 transition-all cursor-pointer" name="remember">
                    <label for="remember_simrs" class="ms-2 text-sm font-medium text-slate-600 cursor-pointer select-none">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border border-transparent rounded-2xl shadow-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform active:scale-95 shadow-indigo-200">
                        <span>🏥</span>
                        <span>Masuk dengan Akun SIMRS</span>
                    </button>
                </div>
            </form>
        @endif

        <!-- TAB 2: FORM LOGIN LOKAL SIPAKAR -->
        <form x-cloak x-show="!{{ $oidcEnabled ? 'true' : 'false' }} || tab === 'local'" style="{{ (!$oidcEnabled || $initialTab === 'local') ? '' : 'display: none;' }}" method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="_login_tab" value="local">

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Alamat Email SIPAKAR</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autocomplete="username"
                           placeholder="admin@hospital.com"
                           class="block w-full pl-11 pr-4 py-3 bg-white/50 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm text-sm"
                    >
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-sm font-semibold text-slate-700">Kata Sandi</label>
                </div>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input id="password" 
                           type="password"
                           name="password"
                           required 
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="block w-full pl-11 pr-4 py-3 bg-white/50 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm text-sm"
                    >
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input id="remember_local" type="checkbox" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 transition-all cursor-pointer" name="remember">
                <label for="remember_local" class="ms-2 text-sm font-medium text-slate-600 cursor-pointer select-none">
                    Ingat saya di perangkat ini
                </label>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border border-transparent rounded-2xl shadow-xl text-sm font-bold text-white bg-slate-800 hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-all transform active:scale-95 shadow-slate-200">
                    <span>🔐</span>
                    <span>Masuk dengan Akun Lokal</span>
                </button>
            </div>
        </form>

        <div class="pt-4 mt-6 border-t border-slate-100 text-center">
            <a href="{{ route('documentation.index') }}"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition-colors">
                <span>📖</span>
                <span>Butuh panduan penggunaan? Buka Dokumentasi</span>
            </a>
        </div>
    </div>
</x-guest-layout>
