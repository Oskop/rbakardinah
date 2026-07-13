<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Rincian Belanja') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('operator.details.update', $detail) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Kode Rekening</label>
                            <select name="account_code_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach($accountCodes as $code)
                                    <option value="{{ $code->id }}" {{ $detail->account_code_id == $code->id ? 'selected' : '' }}>
                                        {{ $code->code }} - {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('account_code_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Belanja</label>
                            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm"
                                required>{{ $detail->description }}</textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Volume</label>
                                <input type="number" name="volume" id="volume" step="0.01" min="0.01" value="{{ $detail->volume }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('volume') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Satuan</label>
                                <input type="text" name="satuan" id="satuan" placeholder="Contoh: Rim, Pcs, Bln" value="{{ $detail->satuan }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('satuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Harga Satuan (Rp)</label>
                                <input type="number" name="harga_satuan" id="harga_satuan" min="0" value="{{ (int)$detail->harga_satuan }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('harga_satuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Harga Total (Rp)</label>
                            <input type="text" id="harga_total" readonly
                                class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed font-semibold text-gray-700" value="Rp 0">
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
</x-app-layout>