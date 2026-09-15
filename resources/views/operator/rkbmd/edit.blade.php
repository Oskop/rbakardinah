<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1 rounded-md">
                        {{ $rkbmd->nomor_permohonan }}
                    </span>
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border bg-blue-100 text-blue-800 border-blue-200">
                        Status: {{ $rkbmd->status }}
                    </span>
                </div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2 mt-1.5">
                    <span>✏️</span>
                    <span>{{ __('Edit Permohonan RKBMD') }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Perbarui rincian permohonan kebutuhan barang milik daerah sebelum diproses oleh Operator Pengusul tujuan
                </p>
            </div>
            <a href="{{ route('operator.rkbmd.show', $rkbmd) }}"
                class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                ← Kembali ke Detail
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        items: @js($rkbmd->items->map(fn($item) => [
            'master_barang_id' => (string)$item->master_barang_id,
            'volume' => (float)$item->volume,
            'satuan' => $item->satuan,
            'spesifikasi' => $item->spesifikasi ?? '',
        ])),
        masterBarangs: @js($masterBarangs),
        addItem() {
            this.items.push({ master_barang_id: '', volume: 1, satuan: '', spesifikasi: '' });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        onBarangChange(index) {
            const selectedId = this.items[index].master_barang_id;
            const barang = this.masterBarangs.find(b => b.id == selectedId);
            if (barang && barang.satuan) {
                this.items[index].satuan = barang.satuan;
            }
        }
    }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <form method="POST" action="{{ route('operator.rkbmd.update', $rkbmd) }}" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Card Header Pengenal Permohonan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Asal Sub-Unit (Terkunci & Otomatis) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Asal Sub-Unit Pemohon <span class="text-indigo-600 font-semibold">(Terkunci Otomatis)</span>
                            </label>
                            <div class="flex items-center gap-2 p-2.5 bg-slate-200/70 border border-slate-300 rounded-xl text-xs text-slate-800 font-bold">
                                <span>📌</span>
                                <span>{{ $rkbmd->subUnit ? $rkbmd->subUnit->name . ' (' . $rkbmd->subUnit->type . ')' : ($rkbmd->unit?->name ?? 'Langsung di bawah Unit Induk') }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1 italic">
                                *Sub-unit terisi otomatis sesuai unit pemohon awal.
                            </p>
                        </div>

                        <!-- Operator Pengusul RBA Tujuan -->
                        <div>
                            <label for="target_operator_id" class="block text-xs font-bold text-slate-700 mb-1">
                                Operator Pengusul RBA Tujuan <span class="text-rose-500">*</span>
                            </label>
                            <select id="target_operator_id" name="target_operator_id" required
                                class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                @foreach($targetOperators as $op)
                                    <option value="{{ $op->id }}" {{ old('target_operator_id', $rkbmd->target_operator_id) == $op->id ? 'selected' : '' }}>
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
                                :value="old('title', $rkbmd->title)" placeholder="Contoh: Permohonan Pengadaan Alat Pendingin Poli Jantung" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="year" :value="__('Tahun Anggaran')" />
                            <x-text-input id="year" class="block mt-1 w-full text-sm font-mono text-center font-bold" type="number" name="year"
                                :value="old('year', $rkbmd->year)" required />
                            <x-input-error :messages="$errors->get('year')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Catatan / Latar Belakang Kebutuhan -->
                    <div>
                        <x-input-label for="notes" :value="__('Latar Belakang / Penjelasan Kebutuhan (Opsional)')" />
                        <textarea id="notes" name="notes" rows="3"
                            class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm"
                            placeholder="Jelaskan dasar pertimbangan atau alasan mendesak kebutuhan barang tersebut...">{{ old('notes', $rkbmd->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <!-- TABEL DAFTAR BARANG (DYNAMIC ALPINE.JS) -->
                    <div class="border border-slate-200 rounded-2xl p-5 bg-slate-50/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                                    <span>📋</span>
                                    <span>Daftar Barang yang Dimohonkan (Permendagri No. 108 Tahun 2016)</span>
                                </h3>
                                <p class="text-xs text-slate-500">Sesuaikan pilihan kode barang dan volume yang diajukan</p>
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
                                        <!-- Barang BMD Dropdown -->
                                        <div class="md:col-span-6">
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                Nama / Kode Barang BMD Permendagri 108 <span class="text-rose-500">*</span>
                                            </label>
                                            <select :name="'items[' + index + '][master_barang_id]'" x-model="item.master_barang_id"
                                                @change="onBarangChange(index)" required
                                                class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                                <option value="" disabled>-- Pilih Barang dari Katalog Permendagri 108 --</option>
                                                <template x-for="b in masterBarangs" :key="b.id">
                                                    <option :value="b.id" x-text="b.kode_barang + ' - ' + b.nama_barang" :selected="b.id == item.master_barang_id"></option>
                                                </template>
                                            </select>
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
                    <div class="bg-indigo-50/40 border border-indigo-200/80 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <label for="attachment" class="block text-xs font-bold text-indigo-950">
                                📎 Dokumen Pendukung (Memo Intern / Nota Dinas Permohonan)
                            </label>
                            @if($rkbmd->attachment_path)
                                <a href="{{ asset('storage/' . $rkbmd->attachment_path) }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-700 bg-white border border-indigo-200 px-3 py-1 rounded-lg hover:bg-indigo-50 shadow-2xs">
                                    <span>📄</span>
                                    <span>Lihat File Saat Ini</span>
                                </a>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Unggah berkas baru dalam format <strong>PDF (Maks. 10MB)</strong> jika ingin mengganti dokumen pendukung sebelumnya. <em>Biarkan kosong jika tetap menggunakan file saat ini.</em>
                        </p>
                        <input type="file" id="attachment" name="attachment" accept="application/pdf"
                            class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                        <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('operator.rkbmd.show', $rkbmd) }}"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-sm transition-all gap-2">
                            <span>💾</span>
                            <span>Simpan Perubahan Permohonan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
