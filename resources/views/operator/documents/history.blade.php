<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Riwayat Versi Dokumen') }} {{ $type }} - {{ $submission->header->year }} ({{ $submission->unit->name }})
            </h2>
            <a href="{{ route('operator.submissions.show', $submission) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded text-sm transition">
                Kembali ke Workboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-6 pb-4 border-b">
                        <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600">
                            <!-- Document Icon -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Daftar Unggahan Dokumen {{ $type }}</h3>
                            <p class="text-sm text-gray-500">Menampilkan seluruh riwayat revisi dokumen pendukung RBA</p>
                        </div>
                    </div>

                    <div class="relative border-l-2 border-indigo-100 ml-4 pl-6 space-y-8">
                        @forelse($versions as $version)
                            <div class="relative">
                                <!-- Dot indicator -->
                                <span class="absolute -left-10 top-1.5 bg-indigo-600 w-4 h-4 rounded-full border-4 border-white shadow-md"></span>
                                
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 shadow-sm hover:shadow-md transition">
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                                        <span class="bg-indigo-100 text-indigo-800 text-xs px-2.5 py-1 rounded-full font-bold uppercase">
                                            Versi {{ $version->version_number }}
                                        </span>
                                        <span class="text-xs text-gray-500 font-medium">
                                            Diunggah pada: {{ $version->created_at->timezone('Asia/Jakarta')->format('d M Y - H:i') }} WIB
                                        </span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center mt-3">
                                        <div class="text-xs text-gray-500">
                                            Oleh: <strong class="text-gray-700">{{ $version->uploader->name }}</strong>
                                        </div>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($version->file_path) }}" target="_blank"
                                            class="inline-flex items-center space-x-1.5 text-xs text-indigo-600 hover:text-indigo-900 font-bold bg-white border border-indigo-200 px-3 py-1.5 rounded shadow-sm hover:bg-indigo-50 transition">
                                            <!-- Download icon -->
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            <span>Unduh PDF</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-500 italic">
                                Belum ada riwayat unggahan untuk dokumen ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
