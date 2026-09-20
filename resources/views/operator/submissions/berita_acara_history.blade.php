<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-extrabold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span>Riwayat Dokumen Berita Acara Ditandatangani</span>
            </h2>
            <a href="{{ route('operator.submissions.show', $submission) }}"
                class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-xl text-sm transition">
                ← Kembali ke Workboard
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50/50 min-h-[calc(100vh-4rem)]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">{{ $deskVerification->sub_unit_name ?? ($targetUser->subUnit->name ?? 'Unit') }}</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            RBA Tahun {{ $submission->header->year }} ({{ $submission->header->period->name }}) &bull; Operator: <strong>{{ $targetUser->name }}</strong>
                        </p>
                    </div>
                    <a href="{{ route('submissions.berita-acara.print', ['submission' => $submission->id, 'user_id' => $targetUser->id]) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-1.5 rounded-lg text-xs transition">
                        <span>🖨️ Cetak Lembar Berita Acara</span>
                    </a>
                </div>

                <div class="mt-6 space-y-4">
                    <h4 class="font-bold text-sm text-gray-800 uppercase tracking-wider text-xs">Riwayat Versi Berkas Scan</h4>

                    @if($documents->isEmpty())
                        <div class="text-center py-12 text-gray-400 italic text-sm bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            Belum ada dokumen scan Berita Acara yang diunggah.
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 border border-gray-200 rounded-xl overflow-hidden">
                            @foreach($documents as $doc)
                                <div class="p-4 bg-white hover:bg-gray-50/80 transition flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-sm shrink-0 border border-indigo-200">
                                            V{{ $doc->version_number }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-gray-900">{{ $doc->original_filename ?: 'Berita_Acara_Signed_V' . $doc->version_number . '.pdf' }}</span>
                                                @if($loop->first)
                                                    <span class="bg-green-100 text-green-800 text-[10px] px-2 py-0.5 rounded-full font-bold">Versi Aktif</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Diunggah oleh <strong>{{ $doc->uploader?->name ?? 'Pengguna' }}</strong> pada {{ $doc->created_at->format('d M Y, H:i') }}
                                            </p>
                                            @if($doc->notes)
                                                <p class="text-xs text-amber-700 bg-amber-50 px-2 py-1 rounded-md mt-2 border border-amber-200/60">
                                                    Catatan: {{ $doc->notes }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-3.5 py-2 rounded-xl shadow transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span>Unduh Berkas</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
