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

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Upload PDF Rincian (V1)</label>
                            <input type="file" name="attachment" accept="application/pdf"
                                class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <p class="text-gray-500 text-xs mt-1">Hanya file PDF (Max 10MB)</p>
                            @error('attachment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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