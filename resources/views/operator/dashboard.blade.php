<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Operator Dashboard - RBA Hospital') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p>{{ __("Selamat Datang Operator! Silakan mulai menyusun RBA untuk unit Anda.") }}</p>
                    <a href="{{ route('operator.submissions.index') }}"
                        class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                        Buka Daftar Usulan RBA
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>