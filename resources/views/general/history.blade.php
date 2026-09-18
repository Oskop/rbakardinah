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
                            @php
                                $otherShared = $attachment->details->where('id', '!=', $detail->id);
                            @endphp
                            <div class="p-4 bg-gray-50 rounded-lg border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-indigo-600">Versi {{ $attachment->version_number }}</span>
                                            @if($attachment->document)
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-100 text-indigo-800">
                                                    📄 {{ $attachment->document->document_name }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600 font-mono mt-0.5">
                                            Berkas: {{ $attachment->original_filename ?? basename($attachment->file_path) }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Diunggah pada: {{ $attachment->created_at->format('d M Y, H:i') }} | Oleh: {{ $attachment->user?->name ?? 'Operator' }}
                                        </div>
                                    </div>
                                    @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path))
                                        <a href="{{ Storage::url($attachment->file_path) }}" target="_blank"
                                            class="bg-blue-500 hover:bg-blue-600 text-white py-1.5 px-4 rounded text-xs font-bold transition shadow">
                                            Lihat PDF
                                        </a>
                                    @else
                                        <span class="bg-amber-100 border border-amber-300 text-amber-800 py-1 px-3 rounded text-xs font-semibold cursor-help" title="File PDF fisik tidak ditemukan di storage server. Minta Operator unggah ulang.">
                                            ⚠️ File Tidak Ditemukan
                                        </span>
                                    @endif
                                </div>

                                @if($otherShared->isNotEmpty())
                                    <div class="mt-3 pt-2.5 border-t border-gray-200 text-[11px] text-gray-600">
                                        <div class="font-bold text-indigo-900 flex items-center gap-1 mb-1">
                                            <span>👥</span>
                                            <span>Dokumen ini digunakan bersama oleh {{ $otherShared->count() }} rincian belanja lainnya:</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pl-2">
                                            @foreach($otherShared as $other)
                                                <div class="flex items-center justify-between p-1.5 bg-white rounded border border-gray-200">
                                                    <span class="truncate max-w-[220px] font-medium text-gray-800">
                                                        {{ $other->accountCode->code }} - {{ $other->description }}
                                                    </span>
                                                    <span class="font-mono text-[10px] text-indigo-600 font-semibold">
                                                        Rp {{ number_format($other->nominal_request, 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
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