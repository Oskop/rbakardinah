<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Master Barang BMD: ') }} {{ $masterBarang->nama_barang }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.master-barangs.update', $masterBarang) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <!-- Kode Barang -->
                        <div>
                            <x-input-label for="kode_barang" :value="__('Kode Barang BMD')" />
                            <x-text-input id="kode_barang" class="block mt-1 w-full font-mono" type="text" name="kode_barang"
                                :value="old('kode_barang', $masterBarang->kode_barang)" required autofocus />
                            <p class="text-xs text-slate-500 mt-1">Sesuai penomoran klasifikasi kodefikasi Permendagri No. 108 Tahun 2016.</p>
                            <x-input-error :messages="$errors->get('kode_barang')" class="mt-2" />
                        </div>

                        <!-- Nama Barang -->
                        <div>
                            <x-input-label for="nama_barang" :value="__('Nama Barang Resmi')" />
                            <x-text-input id="nama_barang" class="block mt-1 w-full" type="text" name="nama_barang"
                                :value="old('nama_barang', $masterBarang->nama_barang)" required />
                            <x-input-error :messages="$errors->get('nama_barang')" class="mt-2" />
                        </div>

                        <!-- Satuan -->
                        <div>
                            <x-input-label for="satuan" :value="__('Satuan Standar')" />
                            <x-text-input id="satuan" class="block mt-1 w-full" type="text" name="satuan"
                                :value="old('satuan', $masterBarang->satuan)" />
                            <x-input-error :messages="$errors->get('satuan')" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div>
                            <x-input-label for="is_active" :value="__('Status')" />
                            <select id="is_active" name="is_active"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="1" {{ old('is_active', $masterBarang->is_active) ? 'selected' : '' }}>Active (Aktif)</option>
                                <option value="0" {{ old('is_active', $masterBarang->is_active) ? '' : 'selected' }}>Inactive (Nonaktif)</option>
                            </select>
                            <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <x-input-label for="deskripsi" :value="__('Deskripsi / Catatan Umum')" />
                            <textarea id="deskripsi" name="deskripsi" rows="3"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('deskripsi', $masterBarang->deskripsi) }}</textarea>
                            <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <a href="{{ route('admin.master-barangs.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900 underline">
                                {{ __('Batal') }}
                            </a>
                            <x-primary-button>
                                {{ __('Perbarui Barang') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
