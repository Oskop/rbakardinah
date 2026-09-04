<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ Auth::check() ? route('dashboard') : url('/') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>

                        @if(Auth::user()->role === 'Administrator')
                            <!-- Master Data Dropdown -->
                            <div class="inline-flex items-center">
                                <x-dropdown align="left" width="w-56">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.units.*', 'admin.users.*', 'admin.kelompok-belanja.*', 'admin.account-codes.*', 'admin.periods.*') ? 'border-indigo-400 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out h-16 focus:outline-none cursor-pointer">
                                            <span>{{ __('Master Data') }}</span>
                                            <div class="ms-1.5 text-gray-400">
                                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('admin.units.index')" class="{{ request()->routeIs('admin.units.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            🏢 {{ __('Units') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('admin.users.index')" class="{{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            👥 {{ __('Users') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('admin.kelompok-belanja.index')" class="{{ request()->routeIs('admin.kelompok-belanja.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            📁 {{ __('Kelompok Belanja') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('admin.account-codes.index')" class="{{ request()->routeIs('admin.account-codes.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            💳 {{ __('Nomor Rekening') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('admin.periods.index')" class="{{ request()->routeIs('admin.periods.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">
                                            📅 {{ __('Periode') }}
                                        </x-dropdown-link>
                                    </x-slot>
                                </x-dropdown>
                            </div>

                            <x-nav-link :href="route('admin.headers.index')" :active="request()->routeIs('admin.headers.*')">
                                {{ __('RBA Headers') }}
                            </x-nav-link>
                            <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                                {{ __('Laporan') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.logs.index')" :active="request()->routeIs('admin.logs.*')">
                                {{ __('Log Data') }}
                            </x-nav-link>
                        @elseif(Auth::user()->role === 'Supervisor')
                            <x-nav-link :href="route('supervisor.submissions.index')"
                                :active="request()->routeIs('supervisor.submissions.*')">
                                {{ __('Review RBA') }}
                            </x-nav-link>
                            <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                                {{ __('Laporan') }}
                            </x-nav-link>
                            <x-nav-link :href="route('supervisor.users.index')"
                                :active="request()->routeIs('supervisor.users.*')">
                                {{ __('Users') }}
                            </x-nav-link>
                        @elseif(Auth::user()->role === 'Operator')
                            <x-nav-link :href="route('operator.submissions.index')"
                                :active="request()->routeIs('operator.submissions.*')">
                                {{ __('Workboard RBA') }}
                            </x-nav-link>
                            <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                                {{ __('Laporan') }}
                            </x-nav-link>
                        @endif
                    @endauth

                    <x-nav-link :href="route('documentation.index')" :active="request()->routeIs('documentation.*')">
                        📖 {{ __('Dokumentasi') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Right Toolbar: Settings Dropdown (Auth) or Login Button (Guest) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-3">
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            <span>{{ __('Masuk ke Akun') }}</span>
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        @auth
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if(Auth::user()->role === 'Administrator')
                <!-- Master Data Responsive Accordion -->
                <div x-data="{ masterDataOpen: {{ request()->routeIs('admin.units.*', 'admin.users.*', 'admin.kelompok-belanja.*', 'admin.account-codes.*', 'admin.periods.*') ? 'true' : 'false' }} }" class="border-b border-gray-100">
                    <button @click="masterDataOpen = !masterDataOpen" type="button" class="w-full flex justify-between items-center ps-3 pe-4 py-2 border-l-4 {{ request()->routeIs('admin.units.*', 'admin.users.*', 'admin.kelompok-belanja.*', 'admin.account-codes.*', 'admin.periods.*') ? 'border-indigo-400 text-indigo-700 bg-indigo-50/50 font-semibold' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50' }} text-base font-medium transition duration-150 ease-in-out cursor-pointer">
                        <span>{{ __('Master Data') }}</span>
                        <svg class="h-4 w-4 transform transition-transform duration-200" :class="{'rotate-180': masterDataOpen}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="masterDataOpen" class="ps-4 space-y-1 bg-gray-50/60 py-1" style="{{ request()->routeIs('admin.units.*', 'admin.users.*', 'admin.kelompok-belanja.*', 'admin.account-codes.*', 'admin.periods.*') ? '' : 'display: none;' }}">
                        <x-responsive-nav-link :href="route('admin.units.index')" :active="request()->routeIs('admin.units.*')">
                            🏢 {{ __('Units') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            👥 {{ __('Users') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.kelompok-belanja.index')" :active="request()->routeIs('admin.kelompok-belanja.*')">
                            📁 {{ __('Kelompok Belanja') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.account-codes.index')" :active="request()->routeIs('admin.account-codes.*')">
                            💳 {{ __('Nomor Rekening') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.periods.index')" :active="request()->routeIs('admin.periods.*')">
                            📅 {{ __('Periode') }}
                        </x-responsive-nav-link>
                    </div>
                </div>
                <x-responsive-nav-link :href="route('admin.headers.index')" :active="request()->routeIs('admin.headers.*')">
                    {{ __('RBA Headers') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Laporan') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.logs.index')" :active="request()->routeIs('admin.logs.*')">
                    {{ __('Log Data') }}
                </x-responsive-nav-link>
            @elseif(Auth::user()->role === 'Supervisor')
                <x-responsive-nav-link :href="route('supervisor.submissions.index')"
                    :active="request()->routeIs('supervisor.submissions.*')">
                    {{ __('Review RBA') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Laporan') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('supervisor.users.index')"
                    :active="request()->routeIs('supervisor.users.*')">
                    {{ __('Users') }}
                </x-responsive-nav-link>
            @elseif(Auth::user()->role === 'Operator')
                <x-responsive-nav-link :href="route('operator.submissions.index')"
                    :active="request()->routeIs('operator.submissions.*')">
                    {{ __('Workboard RBA') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Laporan') }}
                </x-responsive-nav-link>
            @endif
        @endauth

        <x-responsive-nav-link :href="route('documentation.index')" :active="request()->routeIs('documentation.*')">
            📖 {{ __('Dokumentasi') }}
        </x-responsive-nav-link>

        @auth
            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-3 border-t border-gray-200 px-4">
                <a href="{{ route('login') }}"
                    class="block w-full text-center py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    {{ __('Masuk ke Akun') }}
                </a>
            </div>
        @endauth
    </div>
</nav>