<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Supervisor Dashboard - RBA Hospital') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4">
                        {{ __("Selamat Datang Supervisor Unit! Anda bertugas memvalidasi usulan dari Operator.") }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            class="bg-indigo-50 p-4 rounded-lg shadow-sm border border-indigo-100 uppercase tracking-widest ">
                            <h3 class="font-bold text-indigo-800 mb-2">Usulan RBA</h3>
                            <p class="text-sm text-indigo-600 mb-4">Review dan validasi usulan anggaran dari Operator
                                unit Anda.</p>
                            <a href="{{ route('supervisor.submissions.index') }}"
                                class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition">
                                Review Usulan
                            </a>
                        </div>

                        <div
                            class="bg-green-50 p-4 rounded-lg shadow-sm border border-green-100 uppercase tracking-widest ">
                            <h3 class="font-bold text-green-800 mb-2">Manajemen User</h3>
                            <p class="text-sm text-green-600 mb-4">Tambahkan, edit, atau nonaktifkan user Operator di
                                unit Anda.</p>
                            <a href="{{ route('supervisor.users.index') }}"
                                class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow transition">
                                Kelola User
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>