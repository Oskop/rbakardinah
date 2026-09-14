<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Master Barang BMD (Permendagri 108/2016)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.master-barangs.store') }}" class="space-y-6">
                        @csrf

                        <!-- Kode Barang -->
                        <div>
                            <x-input-label for="kode_barang" :value="__('Kode Barang BMD')" />
                            <x-text-input id="kode_barang" class="block mt-1 w-full font-mono" type="text" name="kode_barang"
                                :value="old('kode_barang')" placeholder="Contoh: 1.3.2.10.01.02.001" required autofocus />
                            <p class="text-xs text-slate-500 mt-1">Sesuai penomoran klasifikasi kodefikasi Permendagri No. 108 Tahun 2016.</p>
                            <x-input-error :messages="$errors->get('kode_barang')" class="mt-2" />
                        </div>

                        <!-- Nama Barang -->
                        <div>
                            <x-input-label for="nama_barang" :value="__('Nama Barang Resmi')" />
                            <x-text-input id="nama_barang" class="block mt-1 w-full" type="text" name="nama_barang"
                                :value="old('nama_barang')" placeholder="Contoh: Laptop / Komputer Jinjing Medis" required />
                            <x-input-error :messages="$errors->get('nama_barang')" class="mt-2" />
                        </div>

                        <!-- Satuan -->
                        <div>
                            <x-input-label for="satuan" :value="__('Satuan Standar')" />
                            <x-text-input id="satuan" class="block mt-1 w-full" type="text" name="satuan"
                                :value="old('satuan')" placeholder="Contoh: Unit, Buah, Set, Kotak, Botol" />
                            <x-input-error :messages="$errors->get('satuan')" class="mt-2" />
                        </div>

                        <!-- Deskripsi / Spesifikasi Umum -->
                        <div>
                            <x-input-label for="deskripsi" :value="__('Deskripsi / Catatan Umum')" />
                            <textarea id="deskripsi" name="deskripsi" rows="3"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                placeholder="Keterangan klasifikasi atau peruntukan umum barang ini...">{{ old('deskripsi') }}</textarea>
                            <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <a href="{{ route('admin.master-barangs.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900 underline">
                                {{ __('Batal') }}
                            </a>
                            <x-primary-button>
                                {{ __('Simpan Barang') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
