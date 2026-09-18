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
                    <form action="{{ route('operator.details.update', $detail) }}" method="POST">
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

                        <!-- Info Dokumen Lampiran Saat Ini & Opsi Ganti -->
                        <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200" x-data="{ changeDoc: false }">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-gray-800 text-xs font-black uppercase tracking-wider">
                                        Dokumen Lampiran PDF Terpasang
                                    </label>
                                    @php
                                        $latestAtt = $detail->latestAttachment();
                                        $currentDoc = $detail->document();
                                    @endphp
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-sm font-bold text-gray-900">
                                            📄 {{ $currentDoc?->document_name ?? 'Dokumen Usulan Belanja' }}
                                        </span>
                                        @if($latestAtt)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-sky-800">
                                                V{{ $latestAtt->version_number }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        Berkas: {{ $latestAtt?->original_filename ?? basename($latestAtt?->file_path ?? '-') }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($latestAtt && \Illuminate\Support\Facades\Storage::disk('public')->exists($latestAtt->file_path))
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($latestAtt->file_path) }}" target="_blank"
                                           class="px-2.5 py-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-white border border-indigo-200 rounded-lg inline-flex items-center gap-1 shadow-2xs">
                                            <span>Lihat PDF</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>
                                    @endif

                                    @if(isset($existingDocuments) && $existingDocuments->count() > 1)
                                        <button type="button" @click="changeDoc = !changeDoc"
                                                class="px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white border border-slate-300 rounded-lg shadow-2xs cursor-pointer">
                                            <span x-text="changeDoc ? 'Tutup Pilihan' : 'Ganti Dokumen'"></span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if(isset($existingDocuments) && $existingDocuments->count() > 1)
                                <div x-show="changeDoc" x-cloak class="mt-4 pt-3 border-t border-slate-200">
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                        Pilih Dokumen Lain yang Tersedia di Pengajuan Ini:
                                    </label>
                                    <select name="rba_detail_document_id" class="w-full text-xs border-gray-300 rounded-lg shadow-xs">
                                        <option value="">-- Tetap Gunakan Dokumen Saat Ini --</option>
                                        @foreach($existingDocuments as $doc)
                                            @if(!$currentDoc || $doc->id !== $currentDoc->id)
                                                <option value="{{ $doc->id }}">
                                                    {{ $doc->document_name }} (V{{ $doc->latestVersion?->version_number }}) - {{ $doc->latestVersion?->original_filename }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 mt-1 italic">
                                        * Memilih dokumen lain akan menautkan usulan ini ke dokumen tersebut tanpa menghapus dokumen sebelumnya.
                                    </p>
                                </div>
                            @endif
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