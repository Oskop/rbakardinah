<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard - RBA Hospital') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 text-center">
                    <h3 class="text-lg font-bold mb-4">Administration & Master Data</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.users.index') }}"
                            class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 text-indigo-700 font-semibold transition">
                            Users
                        </a>
                        <a href="{{ route('admin.units.index') }}"
                            class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 text-indigo-700 font-semibold transition">
                            Units
                        </a>
                        <a href="{{ route('admin.account-codes.index') }}"
                            class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 text-indigo-700 font-semibold transition">
                            Account Codes
                        </a>
                        <a href="{{ route('admin.periods.index') }}"
                            class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 text-indigo-700 font-semibold transition">
                            RBA Periods
                        </a>
                        <a href="{{ route('admin.headers.index') }}"
                            class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 text-indigo-700 font-semibold transition">
                            Init RBA (Headers)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>