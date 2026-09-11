<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tambah Sub-Unit Kerja Baru') }}
            </h2>
            <a href="{{ route('admin.sub-units.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold">
                ← Kembali ke Daftar Sub-Unit
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 md:p-8 text-gray-900">
                    <form method="POST" action="{{ route('admin.sub-units.store') }}" class="space-y-6">
                        @csrf

                        <!-- Unit Induk (Eselon III) -->
                        <div>
                            <x-input-label for="unit_id" :value="__('Unit Kerja Induk (Eselon III / Bagian / Bidang)')" />
                            <select id="unit_id" name="unit_id" required
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm">
                                <option value="" disabled selected>-- Pilih Unit Induk --</option>
                                @foreach($units as $u)
                                    <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                                        🏢 {{ $u->name }} ({{ $u->code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Pilih Bagian atau Bidang Eselon III yang membawahi satuan kerja ini.</p>
                            <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                        </div>

                        <!-- Tipe / Kategori -->
                        <div>
                            <x-input-label for="type" :value="__('Tipe / Kategori Satuan Kerja')" />
                            <select id="type" name="type" required
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm">
                                <option value="" disabled selected>-- Pilih Tipe --</option>
                                @foreach($types as $t)
                                    <option value="{{ $t }}" {{ old('type') == $t ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <!-- Kode Sub-Unit -->
                        <div>
                            <x-input-label for="code" :value="__('Kode Sub-Unit (Opsional / Unik)')" />
                            <x-text-input id="code" class="block mt-1 w-full font-mono uppercase" type="text" name="code"
                                :value="old('code')" placeholder="Contoh: SUB-REN-01, SUB-FAR-01" />
                            <p class="text-xs text-slate-500 mt-1">Kode unik identifikasi satuan kerja untuk integrasi data.</p>
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <!-- Nama Sub-Unit -->
                        <div>
                            <x-input-label for="name" :value="__('Nama Sub-Unit Kerja')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                :value="old('name')" required placeholder="Contoh: Sub Bag. Perencanaan dan Evaluasi, Instalasi Farmasi" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Status Aktif -->
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ms-2 text-sm text-gray-700 font-medium">{{ __('Sub-Unit Aktif') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end pt-4 border-t border-slate-100 gap-3">
                            <a href="{{ route('admin.sub-units.index') }}"
                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 font-medium">
                                {{ __('Batal') }}
                            </a>
                            <x-primary-button class="rounded-xl px-5 py-2.5">
                                {{ __('Simpan Sub-Unit') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
