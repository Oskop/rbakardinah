<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <span>✍️</span>
                    <span>{{ __('Formulir Permohonan RKBMD') }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Pengajuan kebutuhan barang milik daerah dari sub-unit ke Operator Pengusul RBA (Permendagri No. 108 Tahun 2016)
                </p>
            </div>
            <a href="{{ route('operator.rkbmd.index') }}"
                class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                ← Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        items: [
            { master_barang_id: '', volume: 1, satuan: '', spesifikasi: '', selected_barang: null }
        ],
        masterBarangs: @js($masterBarangs),
        searchUrl: '{{ route('operator.rkbmd.api.master-barangs') }}',
        addItem() {
            this.items.push({ master_barang_id: '', volume: 1, satuan: '', spesifikasi: '', selected_barang: null });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        validateBeforeSubmit(e) {
            for (let i = 0; i < this.items.length; i++) {
                if (!this.items[i].master_barang_id) {
                    e.preventDefault();
                    alert('Mohon pilih barang dari katalog Permendagri 108 untuk Baris #' + (i + 1) + '.');
                    return false;
                }
            }
        }
    }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-2xl border border-slate-200/80">
                <form method="POST" action="{{ route('operator.rkbmd.store') }}" enctype="multipart/form-data" @submit="validateBeforeSubmit($event)" class="p-6 space-y-6">
                    @csrf

                    <!-- Card Header Pengenal Permohonan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Asal Sub-Unit (Terkunci & Otomatis) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Asal Sub-Unit Pemohon <span class="text-indigo-600 font-semibold">(Terkunci Otomatis)</span>
                            </label>
                            <div class="flex items-center gap-2 p-2.5 bg-slate-200/70 border border-slate-300 rounded-xl text-xs text-slate-800 font-bold">
                                <span>📌</span>
                                <span>{{ $user->subUnit ? $user->subUnit->name . ' (' . $user->subUnit->type . ')' : ($user->unit?->name ?? 'Langsung di bawah Unit Induk') }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1 italic">
                                *Sub-unit terisi otomatis dari profil akun Anda dan tidak dapat diubah.
                            </p>
                        </div>

                        <!-- Operator Pengusul RBA Tujuan -->
                        <div>
                            <label for="target_operator_id" class="block text-xs font-bold text-slate-700 mb-1">
                                Operator Pengusul RBA Tujuan <span class="text-rose-500">*</span>
                            </label>
                            <select id="target_operator_id" name="target_operator_id" required
                                class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                <option value="" disabled selected>-- Pilih Operator Pengusul (PIC RBA) Tujuan --</option>
                                @foreach($targetOperators as $op)
                                    <option value="{{ $op->id }}" {{ old('target_operator_id') == $op->id ? 'selected' : '' }}>
                                        👤 {{ $op->name }} - {{ $op->unit?->name }} {{ $op->subUnit ? '(' . $op->subUnit->name . ')' : '' }} {{ $op->jabatan ? '• ' . $op->jabatan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Hanya menampilkan operator yang memiliki hak pengusulan RBA (can_propose = 1).</p>
                            <x-input-error :messages="$errors->get('target_operator_id')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Judul Permohonan & Tahun Anggaran -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-3">
                            <x-input-label for="title" :value="__('Perihal / Judul Permohonan Kebutuhan')" />
                            <x-text-input id="title" class="block mt-1 w-full text-sm" type="text" name="title"
                                :value="old('title')" placeholder="Contoh: Permohonan Pengadaan Alat Pendingin & Perabot Poli Jantung" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="year" :value="__('Tahun Anggaran')" />
                            <x-text-input id="year" class="block mt-1 w-full text-sm font-mono text-center font-bold" type="number" name="year"
                                :value="old('year', date('Y'))" required />
                            <x-input-error :messages="$errors->get('year')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Catatan / Latar Belakang Kebutuhan -->
                    <div>
                        <x-input-label for="notes" :value="__('Latar Belakang / Penjelasan Kebutuhan (Opsional)')" />
                        <textarea id="notes" name="notes" rows="3"
                            class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm"
                            placeholder="Jelaskan dasar pertimbangan atau alasan mendesak kebutuhan barang tersebut di sub-unit Anda...">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <!-- TABEL DAFTAR BARANG (DYNAMIC ALPINE.JS ASYNC COMBOBOX) -->
                    <div class="border border-slate-200 rounded-2xl p-5 bg-slate-50/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                                    <span>📋</span>
                                    <span>Daftar Barang yang Dimohonkan (Permendagri No. 108 Tahun 2016)</span>
                                </h3>
                                <p class="text-xs text-slate-500">Ketik kata kunci untuk mencari dari 13.476 item Permendagri 108, atau pilih dari rekomendasi cepat</p>
                            </div>
                            <button type="button" @click="addItem()"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs gap-1.5">
                                <span>➕</span>
                                <span>Tambah Baris Barang</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                        <span class="text-xs font-bold text-indigo-700" x-text="'Item #' + (index + 1)"></span>
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="text-xs text-rose-600 hover:text-rose-800 font-semibold inline-flex items-center gap-1">
                                            <span>🗑️</span> Hapus Baris
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                        <!-- Barang BMD Async Combobox -->
                                        <div class="md:col-span-6 relative" :class="open ? 'z-30' : 'z-10'" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                Nama / Kode Barang BMD Permendagri 108 <span class="text-rose-500">*</span>
                                            </label>

                                            <!-- Hidden input payload POST standard -->
                                            <input type="hidden" :name="'items[' + index + '][master_barang_id]'" :value="item.master_barang_id">

                                            <!-- TAMPILAN 1: BARANG SUDAH DIPILIH -->
                                            <template x-if="item.selected_barang">
                                                <div class="flex items-center justify-between p-2.5 bg-indigo-50/70 border border-indigo-200 rounded-xl transition-all">
                                                    <div class="min-w-0 flex-1 pr-2">
                                                        <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono text-[10px] font-bold border border-indigo-200">
                                                                <span x-text="item.selected_barang.kode_permendagri ? ('Ref 108: ' + item.selected_barang.kode_permendagri) : ('Kode: ' + item.selected_barang.kode_barang)"></span>
                                                            </span>
                                                            <span x-show="item.selected_barang.satuan" class="text-[10px] text-slate-500 font-medium" x-text="'• Satuan: ' + item.selected_barang.satuan"></span>
                                                        </div>
                                                        <div class="text-xs font-bold text-slate-900 truncate" x-text="item.selected_barang.nama_barang"></div>
                                                        <template x-if="item.selected_barang.nama_level5 || item.selected_barang.nama_level6">
                                                            <div class="text-[10px] text-slate-600 mt-1 flex items-center gap-1 flex-wrap">
                                                                <span x-show="item.selected_barang.nama_level5" class="px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded text-[9px] font-semibold border border-blue-200" x-text="item.selected_barang.nama_level5"></span>
                                                                <span x-show="item.selected_barang.nama_level5 && item.selected_barang.nama_level6" class="text-slate-400">›</span>
                                                                <span x-show="item.selected_barang.nama_level6" class="px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-[9px] font-semibold border border-indigo-200" x-text="item.selected_barang.nama_level6"></span>
                                                            </div>
                                                        </template>
                                                        <div x-show="!item.selected_barang.nama_level5 && item.selected_barang.deskripsi" class="text-[10px] text-slate-500 truncate mt-0.5" x-text="item.selected_barang.deskripsi"></div>
                                                    </div>
                                                    <button type="button" @click="resetBarang()"
                                                        class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-rose-700 bg-white hover:bg-rose-50 border border-rose-200 rounded-lg shadow-2xs transition-colors"
                                                        title="Ganti pilihan barang ini">
                                                        <span>🔄</span>
                                                        <span>Ganti</span>
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- TAMPILAN 2: BELUM DIPILIH / MODE PENCARIAN -->
                                            <div x-show="!item.selected_barang" class="relative" @click.outside="closeDropdown()">
                                                <!-- Filter Cepat Kategori Level 5 -->
                                                <div class="mb-1.5">
                                                    <select x-model="selectedCategory" @change="search()"
                                                        class="w-full text-[11px] rounded-lg border-slate-200 bg-slate-50/80 text-slate-700 py-1.5 px-2.5 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">-- Semua Kategori Level 5 (Rincian Objek) --</option>
                                                        @if(isset($level5List))
                                                            @foreach ($level5List as $lvl5)
                                                                <option value="{{ $lvl5->kode_level5 }}">[{{ $lvl5->kode_level5 }}] {{ $lvl5->nama_level5 }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>

                                                <div class="relative flex items-center">
                                                    <span class="absolute left-3 text-slate-400 text-xs pointer-events-none">🔍</span>
                                                    <input type="text"
                                                        x-ref="searchInput"
                                                        x-model="query"
                                                        @focus="openDropdown()"
                                                        @input.debounce.250ms="search()"
                                                        @keydown.arrow-down.prevent="highlightNext()"
                                                        @keydown.arrow-up.prevent="highlightPrev()"
                                                        @keydown.enter.prevent="selectHighlighted()"
                                                        @keydown.escape="closeDropdown()"
                                                        placeholder="Ketik nama (misal: laptop, whiteboard, 1.3.2...)"
                                                        class="w-full pl-9 pr-9 text-xs rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2 placeholder:text-slate-400">
                                                    
                                                    <!-- Loading Indicator -->
                                                    <span x-show="loading" class="absolute right-3 inline-flex items-center text-indigo-600 animate-spin">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                        </svg>
                                                    </span>
                                                    <button type="button" x-show="!loading && query" @click="query = ''; search(); $refs.searchInput.focus()"
                                                        class="absolute right-3 text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                                </div>

                                                <!-- Dropdown List Opsi -->
                                                <div x-show="open"
                                                    x-ref="dropdownContainer"
                                                    @mousedown.stop
                                                    @click.stop
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 translate-y-1"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 translate-y-0"
                                                    x-transition:leave-end="opacity-0 translate-y-1"
                                                    class="rkbmd-combobox-dropdown custom-scrollbar divide-y divide-slate-100"
                                                    style="max-height: 280px; overflow-y: auto; overscroll-behavior: contain;">
                                                    
                                                    <div>
                                                        <template x-for="(b, bIdx) in results" :key="b.id">
                                                            <div @click="selectBarang(b)"
                                                                :data-item-index="bIdx"
                                                                :class="{ 'bg-indigo-50 text-indigo-900': highlightedIndex === bIdx, 'hover:bg-slate-50': highlightedIndex !== bIdx }"
                                                                class="p-2.5 text-xs cursor-pointer transition-colors flex items-start justify-between gap-2">
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                                                        <span class="px-1.5 py-0.5 rounded font-mono text-[9px] font-bold"
                                                                            :class="highlightedIndex === bIdx ? 'bg-indigo-200 text-indigo-900' : 'bg-slate-100 text-slate-700'"
                                                                            x-text="b.kode_permendagri ? ('Ref 108: ' + b.kode_permendagri) : ('Kode: ' + b.kode_barang)">
                                                                        </span>
                                                                        <span x-show="b.satuan" class="text-[10px] text-slate-500 font-medium" x-text="'• ' + b.satuan"></span>
                                                                    </div>
                                                                    <div class="font-bold text-slate-800 leading-snug" x-text="b.nama_barang"></div>
                                                                    <template x-if="b.nama_level5 || b.nama_level6">
                                                                        <div class="text-[10px] text-slate-500 mt-1 flex items-center gap-1 flex-wrap">
                                                                            <span x-show="b.nama_level5" class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[9px] font-semibold border border-blue-200" x-text="'📁 ' + b.nama_level5"></span>
                                                                            <span x-show="b.nama_level5 && b.nama_level6" class="text-slate-300">›</span>
                                                                            <span x-show="b.nama_level6" class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[9px] font-semibold border border-indigo-200" x-text="'📂 ' + b.nama_level6"></span>
                                                                        </div>
                                                                    </template>
                                                                    <div x-show="!b.nama_level5 && b.deskripsi" class="text-[10px] text-slate-400 line-clamp-1 mt-0.5" x-text="b.deskripsi"></div>
                                                                </div>
                                                                <span class="text-xs text-indigo-600 font-bold opacity-0 transition-opacity" :class="{ 'opacity-100': highlightedIndex === bIdx }">
                                                                    Pilih ↵
                                                                </span>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <!-- Pesan Tidak Ditemukan -->
                                                    <div x-show="!loading && results.length === 0" class="p-4 text-center text-xs text-slate-400">
                                                        <span>🔍 Barang tidak ditemukan untuk "<span class="font-bold text-slate-600" x-text="query"></span>"</span>
                                                        <p class="text-[10px] text-slate-400 mt-1">Gunakan kata kunci umum atau potongan kode rekening.</p>
                                                    </div>

                                                    <!-- Indikator Pencarian Berjalan -->
                                                    <div x-show="loading" class="p-3 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
                                                        <svg class="h-4 w-4 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                        </svg>
                                                        <span>Mencari di katalog Permendagri 108...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Jumlah / Volume -->
                                        <div class="md:col-span-2">
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                Jumlah <span class="text-rose-500">*</span>
                                            </label>
                                            <input type="number" step="0.01" min="0.01" :name="'items[' + index + '][volume]'" x-model="item.volume" required
                                                class="w-full text-xs font-bold text-right rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2" placeholder="0">
                                        </div>

                                        <!-- Satuan -->
                                        <div class="md:col-span-4">
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                Satuan <span class="text-rose-500">*</span>
                                            </label>
                                            <input type="text" :name="'items[' + index + '][satuan]'" x-model="item.satuan" required
                                                class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2" placeholder="Unit, Buah, Set...">
                                        </div>

                                        <!-- Spesifikasi Tambahan -->
                                        <div class="md:col-span-12">
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                Spesifikasi / Merk / Keterangan Kebutuhan Tambahan (Opsional)
                                            </label>
                                            <input type="text" :name="'items[' + index + '][spesifikasi]'" x-model="item.spesifikasi"
                                                class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                                                placeholder="Contoh: Ukuran 2 PK, Inverter, bahan stainless steel, dilengkapi laci...">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Upload PDF Memo Intern / Nota Dinas -->
                    <div class="bg-indigo-50/40 border border-indigo-200/80 rounded-xl p-4">
                        <label for="attachment" class="block text-xs font-bold text-indigo-950 mb-1">
                            📎 Upload Dokumen Pendukung (Memo Intern / Nota Dinas Permohonan) <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">
                            Unggah berkas resmi nota dinas permohonan dari sub-unit Anda dalam format <strong>PDF (Maks. 10MB)</strong>.
                        </p>
                        <input type="file" id="attachment" name="attachment" accept="application/pdf" required
                            class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                        <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('operator.rkbmd.index') }}"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-sm transition-all gap-2">
                            <span>🚀</span>
                            <span>Kirim Permohonan RKBMD</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .rkbmd-combobox-dropdown {
            position: absolute !important;
            left: 0 !important;
            right: 0 !important;
            top: 100% !important;
            margin-top: 0.25rem !important;
            max-height: 280px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior: contain !important;
            -webkit-overflow-scrolling: touch;
            z-index: 50 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 7px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
            border: 1px solid #f8fafc;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        function rkbmdBarangCombobox(item, masterBarangs, searchUrl) {
            return {
                open: false,
                query: '',
                selectedCategory: '',
                loading: false,
                results: [],
                highlightedIndex: -1,

                init() {
                    if (item.master_barang_id && !item.selected_barang) {
                        const found = masterBarangs.find(b => b.id == item.master_barang_id);
                        if (found) {
                            item.selected_barang = found;
                            if (!item.satuan) item.satuan = found.satuan || '';
                        }
                    }
                },

                openDropdown() {
                    this.open = true;
                    if (this.results.length === 0 && !this.query && !this.selectedCategory) {
                        this.results = masterBarangs;
                    }
                },

                closeDropdown() {
                    this.open = false;
                    this.highlightedIndex = -1;
                },

                async search() {
                    const q = this.query.trim();
                    const cat = this.selectedCategory;
                    if (q.length === 0 && !cat) {
                        this.results = masterBarangs;
                        this.loading = false;
                        return;
                    }
                    this.loading = true;
                    try {
                        let url = searchUrl + '?q=' + encodeURIComponent(q) + '&limit=25';
                        if (cat) {
                            url += '&level5=' + encodeURIComponent(cat);
                        }
                        const resp = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (resp.ok) {
                            this.results = await resp.json();
                            this.highlightedIndex = this.results.length > 0 ? 0 : -1;
                        }
                    } catch (e) {
                        console.error('Gagal memuat barang:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                selectBarang(b) {
                    item.master_barang_id = b.id;
                    item.selected_barang = b;
                    if (b.satuan) {
                        item.satuan = b.satuan;
                    }
                    this.query = '';
                    this.open = false;
                    this.highlightedIndex = -1;
                },

                resetBarang() {
                    item.master_barang_id = '';
                    item.selected_barang = null;
                    this.query = '';
                    this.selectedCategory = '';
                    this.open = true;
                    this.results = masterBarangs;
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                    });
                },

                highlightNext() {
                    if (!this.open) {
                        this.openDropdown();
                        return;
                    }
                    if (this.results.length === 0) return;
                    this.highlightedIndex = (this.highlightedIndex + 1) % this.results.length;
                    this.scrollToHighlighted();
                },

                highlightPrev() {
                    if (!this.open) {
                        this.openDropdown();
                        return;
                    }
                    if (this.results.length === 0) return;
                    this.highlightedIndex = (this.highlightedIndex - 1 + this.results.length) % this.results.length;
                    this.scrollToHighlighted();
                },

                selectHighlighted() {
                    if (this.open && this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                        this.selectBarang(this.results[this.highlightedIndex]);
                    }
                },

                scrollToHighlighted() {
                    this.$nextTick(() => {
                        const container = this.$refs.dropdownContainer;
                        if (!container) return;
                        const itemEl = container.querySelector('[data-item-index="' + this.highlightedIndex + '"]');
                        if (itemEl) {
                            itemEl.scrollIntoView({ block: 'nearest' });
                        }
                    });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
