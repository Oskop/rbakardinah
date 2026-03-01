<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Rincian Belanja') }} - {{ $submission->header->year }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('operator.details.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="rba_submission_id" value="{{ $submission->id }}">

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Kode Rekening</label>
                            <select name="account_code_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">-- Pilih Rekening --</option>
                                @foreach($accountCodes as $code)
                                    <option value="{{ $code->id }}">{{ $code->code }} - {{ $code->name }}</option>
                                @endforeach
                            </select>
                            @error('account_code_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Belanja</label>
                            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Contoh: Pembelian Kertas A4 100 Rim" required></textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nominal Usulan (Rp)</label>
                            <input type="number" name="nominal_request"
                                class="w-full border-gray-300 rounded-md shadow-sm" required min="0">
                            @error('nominal_request') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
</x-app-layout>