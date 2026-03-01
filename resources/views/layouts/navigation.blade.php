<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if(Auth::user()->role === 'Administrator')
                        <x-nav-link :href="route('admin.units.index')" :active="request()->routeIs('admin.units.*')">
                            {{ __('Units') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ __('Users') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.kelompok-belanja.index')"
                            :active="request()->routeIs('admin.kelompok-belanja.*')">
                            {{ __('Kelompok Belanja') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.account-codes.index')"
                            :active="request()->routeIs('admin.account-codes.*')">
                            {{ __('Account Codes') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.periods.index')" :active="request()->routeIs('admin.periods.*')">
                            {{ __('Periods') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.headers.index')" :active="request()->routeIs('admin.headers.*')">
                            {{ __('RBA Headers') }}
                        </x-nav-link>
                    @elseif(Auth::user()->role === 'Supervisor')
                        <x-nav-link :href="route('supervisor.submissions.index')"
                            :active="request()->routeIs('supervisor.submissions.*')">
                            {{ __('Review RBA') }}
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
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
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
        @if(Auth::user()->role === 'Administrator')
            <x-responsive-nav-link :href="route('admin.units.index')" :active="request()->routeIs('admin.units.*')">
                {{ __('Units') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                {{ __('Users') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.kelompok-belanja.index')"
                :active="request()->routeIs('admin.kelompok-belanja.*')">
                {{ __('Kelompok Belanja') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.account-codes.index')"
                :active="request()->routeIs('admin.account-codes.*')">
                {{ __('Account Codes') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.periods.index')" :active="request()->routeIs('admin.periods.*')">
                {{ __('Periods') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.headers.index')" :active="request()->routeIs('admin.headers.*')">
                {{ __('RBA Headers') }}
            </x-responsive-nav-link>
        @elseif(Auth::user()->role === 'Supervisor')
            <x-responsive-nav-link :href="route('supervisor.submissions.index')"
                :active="request()->routeIs('supervisor.submissions.*')">
                {{ __('Review RBA') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('supervisor.users.index')"
                :active="request()->routeIs('supervisor.users.*')">
                {{ __('Users') }}
            </x-responsive-nav-link>
        @endif

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
    </div>
</nav>