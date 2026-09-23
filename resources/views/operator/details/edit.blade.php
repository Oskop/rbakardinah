<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Rincian Belanja') }}
        </h2>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
        <style>
            /* Harmonize Tom Select with Tailwind CSS */
            .ts-control {
                border-color: #d1d5db !important;
                border-radius: 0.375rem !important;
                padding: 0.5rem 0.75rem !important;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
                font-size: 0.875rem !important;
                line-height: 1.25rem !important;
                background-color: #ffffff !important;
            }
            .ts-control.focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
            }
            .ts-dropdown {
                border-radius: 0.5rem !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                border-color: #e5e7eb !important;
                font-size: 0.875rem !important;
                z-index: 50 !important;
            }
            .ts-dropdown .optgroup-header {
                font-weight: 700 !important;
                font-size: 0.75rem !important;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                background-color: #f8fafc !important;
                color: #475569 !important;
                padding: 0.4rem 0.75rem !important;
                border-bottom: 1px solid #e2e8f0;
                border-top: 1px solid #e2e8f0;
            }
            .ts-dropdown .option {
                padding: 0.5rem 0.75rem !important;
                border-bottom: 1px solid #f1f5f9;
            }
            .ts-dropdown .option.active {
                background-color: #eff6ff !important;
                color: #1e40af !important;
            }
            .ts-dropdown .dropdown-input {
                padding: 0.5rem 0.75rem !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }
        </style>
    @endpush

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('operator.details.update', $detail) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @php
                            $groupedAccounts = $accountCodes->groupBy(function($item) {
                                return $item->kelompokBelanja 
                                    ? ($item->kelompokBelanja->kode . ' - ' . $item->kelompokBelanja->name) 
                                    : 'Tanpa Kelompok Belanja';
                            });
                        @endphp

                        <div class="mb-4">
                            <label for="account_code_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Kode Rekening <span class="text-red-500">*</span>
                            </label>
                            <select name="account_code_id" id="account_code_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">-- Ketik Kode atau Nama Rekening... --</option>
                                @foreach($groupedAccounts as $groupName => $codes)
                                    <optgroup label="{{ $groupName }}">
                                        @foreach($codes as $code)
                                            <option value="{{ $code->id }}"
                                                data-code="{{ $code->code }}"
                                                data-name="{{ $code->name }}"
                                                data-group="{{ $groupName }}"
                                                {{ (old('account_code_id', $detail->account_code_id) == $code->id) ? 'selected' : '' }}>
                                                {{ $code->code }} - {{ $code->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-gray-500 text-xs mt-1">💡 Anda dapat mencari secara bebas dengan mengetik kode rekening (misal: <code>5.1.02</code>), nama rekening belanja, atau kelompok belanja.</p>
                            @error('account_code_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Belanja</label>
                            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm"
                                required>{{ old('description', $detail->description) }}</textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Volume</label>
                                <input type="number" name="volume" id="volume" step="0.01" min="0.01" value="{{ old('volume', $detail->volume) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('volume') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Satuan</label>
                                <input type="text" name="satuan" id="satuan" placeholder="Contoh: Rim, Pcs, Bln" value="{{ old('satuan', $detail->satuan) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('satuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Harga Satuan (Rp)</label>
                                <input type="number" name="harga_satuan" id="harga_satuan" min="0" value="{{ old('harga_satuan', (int)$detail->harga_satuan) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('harga_satuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Harga Total (Rp)</label>
                            <input type="text" id="harga_total" readonly
                                class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed font-semibold text-gray-700" value="Rp 0">
                        </div>

                        @php
                            $latestAtt = $detail->latestAttachment();
                            $currentDoc = $detail->document();
                            $otherDocs = isset($existingDocuments) 
                                ? $existingDocuments->where('id', '!=', $currentDoc?->id) 
                                : collect();
                        @endphp

                        <!-- Dokumen Lampiran PDF & Opsi Perubahan -->
                        <div class="mb-6 p-5 rounded-2xl bg-slate-50 border border-slate-200"
                             x-data="{ 
                                 docAction: '{{ old('document_action', 'keep') }}',
                                 selectedDocId: '{{ old('rba_detail_document_id', '') }}'
                             }">
                            <input type="hidden" name="document_action" :value="docAction">

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div>
                                    <label class="block text-gray-800 text-sm font-black uppercase tracking-wider">
                                        Berkas Lampiran PDF Pendukung
                                    </label>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Pertahankan dokumen saat ini, beralih ke dokumen lain yang ada, atau unggah berkas PDF baru.
                                    </p>
                                </div>

                                <!-- Segmented Controls / Mode Switcher -->
                                <div class="inline-flex p-1 bg-white border border-gray-200 rounded-xl shadow-2xs">
                                    <button type="button"
                                            @click="docAction = 'keep'"
                                            :class="docAction === 'keep' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                                        <span>📄 Tetap Gunakan Saat Ini</span>
                                    </button>
                                    @if($otherDocs->count() > 0)
                                        <button type="button"
                                                @click="docAction = 'existing'"
                                                :class="docAction === 'existing' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                                            <span>🔄 Pilih Dokumen Lain</span>
                                            <span class="px-1.5 py-0.2 bg-indigo-500/30 text-[10px] rounded-full">{{ $otherDocs->count() }}</span>
                                        </button>
                                    @endif
                                    <button type="button"
                                            @click="docAction = 'new'"
                                            :class="docAction === 'new' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                                        <span>📤 Unggah PDF Baru</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mode 1: Tetap Gunakan Dokumen Saat Ini -->
                            <div x-show="docAction === 'keep'" class="p-3.5 bg-white border border-gray-200 rounded-xl flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-gray-900">
                                            📄 {{ $currentDoc?->document_name ?? 'Dokumen Usulan Belanja' }}
                                        </span>
                                        @if($latestAtt)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-sky-800">
                                                V{{ $latestAtt->version_number }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-1">
                                        Berkas: {{ $latestAtt?->original_filename ?? basename($latestAtt?->file_path ?? '-') }}
                                    </div>
                                </div>
                                @if($latestAtt && \Illuminate\Support\Facades\Storage::disk('public')->exists($latestAtt->file_path))
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($latestAtt->file_path) }}" target="_blank"
                                       class="px-2.5 py-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg inline-flex items-center gap-1">
                                        <span>Lihat PDF</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                @endif
                            </div>

                            <!-- Mode 2: Pilih Dokumen Lain yang Tersedia -->
                            @if($otherDocs->count() > 0)
                                <div x-show="docAction === 'existing'" x-cloak class="space-y-3">
                                    <div class="text-xs text-indigo-700 bg-indigo-50/80 p-3 rounded-xl border border-indigo-100 flex items-start gap-2">
                                        <span class="text-base">💡</span>
                                        <div class="leading-relaxed">
                                            Pilih dokumen PDF yang sudah pernah diunggah untuk pengajuan ini. Usulan belanja ini akan dialihkan ke dokumen tersebut.
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2.5 max-h-72 overflow-y-auto pr-1">
                                        @foreach($otherDocs as $doc)
                                            @php
                                                $latest = $doc->latestVersion;
                                                $detailCount = $latest ? $latest->details->count() : 0;
                                            @endphp
                                            <label class="relative flex items-center justify-between p-3.5 rounded-xl border-2 transition-all cursor-pointer bg-white"
                                                   :class="selectedDocId == {{ $doc->id }} ? 'border-indigo-600 bg-indigo-50/30 shadow-xs' : 'border-gray-200 hover:border-indigo-200'">
                                                <div class="flex items-center gap-3">
                                                    <input type="radio" name="rba_detail_document_id" value="{{ $doc->id }}"
                                                           x-model="selectedDocId"
                                                           class="text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-bold text-sm text-gray-900">{{ $doc->document_name }}</span>
                                                            @if($latest)
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-sky-800">
                                                                    V{{ $latest->version_number }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-3 mt-1 text-[11px] text-gray-500">
                                                            <span>📁 {{ $latest?->original_filename ?? basename($latest?->file_path ?? '-') }}</span>
                                                            <span>•</span>
                                                            <span class="text-indigo-600 font-semibold">{{ $detailCount }} usulan belanja terikat</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($latest && \Illuminate\Support\Facades\Storage::disk('public')->exists($latest->file_path))
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($latest->file_path) }}" target="_blank"
                                                       @click.stop
                                                       class="px-2.5 py-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 inline-flex items-center gap-1">
                                                        <span>Lihat PDF</span>
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                    </a>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('rba_detail_document_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <!-- Mode 3: Unggah Berkas PDF Baru -->
                            <div x-show="docAction === 'new'" x-cloak class="space-y-4">
                                <div class="text-xs text-emerald-800 bg-emerald-50/80 p-3 rounded-xl border border-emerald-200 flex items-start gap-2">
                                    <span class="text-base">📤</span>
                                    <div class="leading-relaxed">
                                        Unggah berkas PDF baru untuk usulan ini. Berkas akan disimpan sebagai entitas dokumen tersendiri dan dapat digunakan bersama oleh usulan lainnya jika diperlukan.
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-xs font-bold mb-1">
                                        Nama / Judul Dokumen (Opsional)
                                    </label>
                                    <input type="text" name="document_name" value="{{ old('document_name') }}"
                                           placeholder="Contoh: Nota Dinas Belanja Alkes 2026 (kosongkan untuk menggunakan nama file)"
                                           class="w-full border-gray-300 rounded-lg shadow-xs text-xs">
                                    <p class="text-[11px] text-gray-400 mt-1">Nama ini akan menjadi label pengenal dokumen di sistem.</p>
                                    @error('document_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-xs font-bold mb-1">
                                        Pilih Berkas PDF Baru <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="flex items-center justify-center w-full">
                                        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition-all">
                                            <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                                <svg class="w-7 h-7 mb-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                                <p class="text-xs text-gray-600"><span class="font-bold text-indigo-600">Klik untuk memilih berkas</span> atau seret PDF ke sini</p>
                                                <p class="text-[10px] text-gray-400 mt-0.5">Format PDF (Maks. 10MB)</p>
                                            </div>
                                            <input type="file" name="attachment" accept=".pdf,application/pdf" class="hidden"
                                                   @change="const f = $event.target.files[0]; if(f) { $el.closest('label').querySelector('p.text-xs').innerHTML = '<span class=\'font-bold text-emerald-600\'>' + f.name + '</span> (' + (f.size/1024).toFixed(1) + ' KB)'; }">
                                        </label>
                                    </div>
                                    @error('attachment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('operator.submissions.show', $detail->rba_submission_id) }}"
                                class="mr-4 text-sm text-gray-600 hover:text-gray-900">Batal</a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-lg">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Inisialisasi Tom Select untuk Kode Rekening
                if (document.getElementById('account_code_id')) {
                    new TomSelect('#account_code_id', {
                        create: false,
                        maxOptions: 1000,
                        allowEmptyOption: true,
                        placeholder: '-- Ketik Kode atau Nama Rekening... --',
                        searchField: ['text', 'code', 'name', 'group'],
                        plugins: ['clear_button'],
                        render: {
                            option: function(data, escape) {
                                const code = data.code || (data.text ? data.text.split(' - ')[0] : '');
                                const name = data.name || (data.text ? data.text.split(' - ').slice(1).join(' - ') : data.text);
                                return `<div class="flex items-center justify-between py-1 px-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            ${escape(code)}
                                        </span>
                                        <span class="text-gray-800 font-medium text-sm">${escape(name)}</span>
                                    </div>
                                </div>`;
                            },
                            item: function(data, escape) {
                                const code = data.code || (data.text ? data.text.split(' - ')[0] : '');
                                const name = data.name || (data.text ? data.text.split(' - ').slice(1).join(' - ') : data.text);
                                return `<div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                        ${escape(code)}
                                    </span>
                                    <span class="text-gray-900 font-medium">${escape(name)}</span>
                                </div>`;
                            }
                        }
                    });
                }

                // Kalkulasi Total Biaya
                const volumeInput = document.getElementById('volume');
                const hargaSatuanInput = document.getElementById('harga_satuan');
                const hargaTotalInput = document.getElementById('harga_total');

                function calculateTotal() {
                    const volume = parseFloat(volumeInput.value) || 0;
                    const hargaSatuan = parseFloat(hargaSatuanInput.value) || 0;
                    const total = volume * hargaSatuan;
                    
                    // Format rupiah for display
                    hargaTotalInput.value = 'Rp ' + total.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                }

                volumeInput.addEventListener('input', calculateTotal);
                hargaSatuanInput.addEventListener('input', calculateTotal);
                
                // Run initial calculation
                calculateTotal();
            });
        </script>
    @endpush
</x-app-layout>