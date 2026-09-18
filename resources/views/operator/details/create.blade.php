<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Rincian Belanja') }} - {{ $submission->header->year }}
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
                    <form action="{{ route('operator.details.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="rba_submission_id" value="{{ $submission->id }}">

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
                                                {{ old('account_code_id') == $code->id ? 'selected' : '' }}>
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
                                placeholder="Contoh: Pembelian Kertas A4 100 Rim" required>{{ old('description') }}</textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Volume</label>
                                <input type="number" name="volume" id="volume" step="0.01" min="0.01" value="{{ old('volume', '1.00') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('volume') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Satuan</label>
                                <input type="text" name="satuan" id="satuan" placeholder="Contoh: Rim, Pcs, Bln" value="{{ old('satuan') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('satuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Harga Satuan (Rp)</label>
                                <input type="number" name="harga_satuan" id="harga_satuan" min="0" value="{{ old('harga_satuan', '0') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('harga_satuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Harga Total (Rp)</label>
                            <input type="text" id="harga_total" readonly
                                class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed font-semibold text-gray-700" value="Rp 0">
                        </div>

                        <div class="mb-6 p-5 rounded-2xl bg-slate-50 border border-slate-200"
                             x-data="{ 
                                docSource: @js($existingDocuments->isNotEmpty() ? old('document_source', 'existing') : 'new'),
                                selectedDocId: @js(old('rba_detail_document_id', $existingDocuments->first()?->id ?? ''))
                             }">
                            <input type="hidden" name="document_source" :value="docSource">

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                                <div>
                                    <label class="block text-gray-800 text-sm font-black">
                                        Berkas Lampiran PDF Pendukung <span class="text-rose-500">*</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        1 berkas PDF (seperti Nota Dinas / KAK) dapat digunakan bersama oleh beberapa usulan rincian belanja.
                                    </p>
                                </div>

                                @if($existingDocuments->isNotEmpty())
                                    <div class="inline-flex p-1 bg-white border border-gray-200 rounded-xl shadow-2xs">
                                        <button type="button"
                                                @click="docSource = 'existing'"
                                                :class="docSource === 'existing' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                                            <span>📄 Gunakan Dokumen yang Ada</span>
                                            <span class="px-1.5 py-0.2 bg-indigo-500/30 text-[10px] rounded-full">{{ $existingDocuments->count() }}</span>
                                        </button>
                                        <button type="button"
                                                @click="docSource = 'new'"
                                                :class="docSource === 'new' ? 'bg-indigo-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                                            <span>📤 Unggah PDF Baru</span>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if($existingDocuments->isNotEmpty())
                                <!-- Opsi 1: Pilih dari Dokumen yang Sudah Ada -->
                                <div x-show="docSource === 'existing'" x-cloak class="space-y-3">
                                    <div class="text-xs text-indigo-700 bg-indigo-50/80 p-3 rounded-xl border border-indigo-100 flex items-start gap-2">
                                        <span class="text-base">💡</span>
                                        <div class="leading-relaxed">
                                            Pilih dokumen PDF yang sudah pernah diunggah untuk pengajuan ini. Usulan belanja ini akan langsung terikat ke dokumen tersebut tanpa perlu mengunggah ulang berkas.
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2.5 max-h-72 overflow-y-auto pr-1">
                                        @foreach($existingDocuments as $doc)
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

                            <!-- Opsi 2: Unggah Dokumen PDF Baru -->
                            <div x-show="docSource === 'new'" class="space-y-4" @if($existingDocuments->isNotEmpty()) x-cloak @endif>
                                <div>
                                    <label class="block text-gray-700 text-xs font-bold mb-1">
                                        Nama / Judul Dokumen (Opsional)
                                    </label>
                                    <input type="text" name="document_name" value="{{ old('document_name') }}"
                                           placeholder="Contoh: Nota Dinas Belanja ATK 2026 (kosongkan untuk menggunakan nama file)"
                                           class="w-full border-gray-300 rounded-lg shadow-xs text-xs">
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-xs font-bold mb-1">
                                        Pilih Berkas PDF <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="file" name="attachment" accept="application/pdf"
                                           :required="docSource === 'new'"
                                           class="w-full border-gray-300 rounded-lg shadow-xs text-xs file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="text-gray-500 text-[11px] mt-1">Format PDF, Ukuran Maksimal 10 MB. Berkas ini nantinya dapat dipilih kembali untuk usulan lainnya.</p>
                                    @error('attachment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('operator.submissions.show', $submission) }}"
                                class="mr-4 text-sm text-gray-600 hover:text-gray-900">Batal</a>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-lg">
                                Simpan Detail
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