<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Riwayat Dokumen (Audit Trail)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 pb-4 border-b">
                        <h3 class="font-bold text-lg">{{ $detail->accountCode->code }} -
                            {{ $detail->accountCode->name }}</h3>
                        <p class="text-gray-600">{{ $detail->description }}</p>
                        <p class="font-semibold mt-2">Nominal Usulan: Rp
                            {{ number_format($detail->nominal_request, 0, ',', '.') }}</p>
                    </div>

                    <h4 class="font-bold mb-4 text-gray-700">Versi PDF Rincian:</h4>
                    <div class="space-y-4">
                        @foreach($attachments as $attachment)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                                <div>
                                    <span class="font-bold text-indigo-600">Versi {{ $attachment->version_number }}</span>
                                    <div class="text-xs text-gray-500">Diunggah pada:
                                        {{ $attachment->created_at->format('d M Y, H:i') }}</div>
                                    <div class="text-xs text-gray-500">Oleh: {{ $attachment->user->name }}</div>
                                </div>
                                <a href="{{ Storage::url($attachment->file_path) }}" target="_blank"
                                    class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-4 rounded text-sm transition shadow">
                                    Lihat PDF
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        <button onclick="window.history.back()" class="text-indigo-600 hover:underline">←
                            Kembali</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>