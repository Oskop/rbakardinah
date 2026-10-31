<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">⚙️</span>
                    {{ __('Pengaturan Sistem & Kebijakan Keamanan') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Konfigurasi global tingkat administrator untuk mengontrol fitur kritis dan proteksi data anggaran.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.headers.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition shadow-sm">
                    📋 Daftar RBA
                </a>
                <a href="{{ route('admin.archives.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-slate-800 text-white rounded-xl font-semibold text-xs uppercase tracking-widest hover:bg-slate-700 transition shadow-sm">
                    🗃️ Arsip Sampah
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Notifications -->
            @if(session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-emerald-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm font-semibold text-emerald-800">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-rose-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-semibold text-rose-800">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Main Security Setting Card: RBA Deletion Policy -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl {{ $allowRbaDeletion ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }} flex items-center justify-center font-bold text-lg">
                            🛡️
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg">Proteksi Tindakan Hapus RBA Header</h3>
                            <p class="text-xs text-gray-500">Kontrol visibilitas tombol Hapus dan izin endpoint penghapusan RBA.</p>
                        </div>
                    </div>
                    <div>
                        @if($allowRbaDeletion)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                MODE AKTIF (TOMBOL DITAMPILKAN)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                MODE AMAN (TOMBOL TERKUNCI & TERSEMBUNYI)
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-6 md:p-8 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                        <div class="lg:col-span-2 space-y-3">
                            <h4 class="font-bold text-gray-900 text-base">Deskripsi & Cara Kerja:</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Fitur ini dirancang untuk mencegah penghapusan data RBA secara tidak sengaja oleh administrator. Ketika tombol berstatus <strong>Terkunci</strong>:
                            </p>
                            <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                                <li>Tombol <strong>"Hapus RBA"</strong> sama sekali tidak di-render di tabel daftar RBA Header.</li>
                                <li>Endpoint server <code>DELETE /admin/headers/{id}</code> secara ketat menolak segala permintaan penghapusan.</li>
                                <li>Ketika Anda perlu membersihkan data RBA yang salah input, aktifkan fitur ini untuk memunculkan tombol hapus, lakukan penghapusan, lalu <strong>kunci kembali</strong> setelah selesai.</li>
                            </ul>
                        </div>

                        <!-- Toggle Box -->
                        <div class="p-5 rounded-2xl {{ $allowRbaDeletion ? 'bg-amber-50/70 border-2 border-amber-200' : 'bg-gray-50 border border-gray-200' }} text-center flex flex-col items-center justify-center space-y-4">
                            <div class="text-3xl">
                                {{ $allowRbaDeletion ? '🔓' : '🔒' }}
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-gray-500">Status Saat Ini</div>
                                <div class="text-lg font-extrabold {{ $allowRbaDeletion ? 'text-amber-800' : 'text-slate-800' }}">
                                    {{ $allowRbaDeletion ? 'Tombol Hapus Terbuka' : 'Tombol Hapus Disembunyikan' }}
                                </div>
                            </div>

                            <form action="{{ route('admin.settings.update') }}" method="POST"
                                onsubmit="return confirm('{{ $allowRbaDeletion ? 'PERINGATAN: Anda akan MENONAKTIFKAN dan MENGUNCI kembali tombol Hapus RBA. Lanjutkan?' : 'PERHATIAN: Anda akan MENGAKTIFKAN tombol Hapus RBA di halaman daftar RBA. Pastikan berhati-hati saat melakukan penghapusan data. Lanjutkan?' }}');">
                                @csrf
                                <input type="hidden" name="allow_rba_deletion" value="{{ $allowRbaDeletion ? '0' : '1' }}">

                                @if($allowRbaDeletion)
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl shadow-md transition transform active:scale-95 cursor-pointer">
                                        🔒 Kunci & Sembunyikan Tombol Hapus
                                    </button>
                                @else
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md transition transform active:scale-95 cursor-pointer">
                                        🔓 Buka Kunci / Tampilkan Tombol Hapus
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>

                    @if($allowRbaDeletion)
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                            <span class="text-amber-600 text-lg">⚠️</span>
                            <div class="text-xs text-amber-800 leading-relaxed">
                                <strong>Peringatan Keamanan:</strong> Mode pembersihan saat ini sedang <strong>AKTIF</strong>. Tombol Hapus RBA dapat dilihat dan digunakan oleh administrator di menu <a href="{{ route('admin.headers.index') }}" class="underline font-bold text-amber-900">Daftar RBA Header</a>. Demi kepatuhan audit dan keselamatan anggaran, segera tekan tombol <em>"Kunci & Sembunyikan Tombol Hapus"</em> di atas setelah Anda selesai membersihkan data yang keliru.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Activity Log Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                        <span>📜</span> Riwayat Perubahan Pengaturan Sistem (Audit Trail)
                    </h3>
                    <a href="{{ route('admin.logs.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        Lihat Seluruh Log &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50/70 text-gray-500 uppercase text-xs font-bold">
                            <tr>
                                <th class="px-6 py-3 text-left">Waktu</th>
                                <th class="px-6 py-3 text-left">Administrator</th>
                                <th class="px-6 py-3 text-left">Aksi / Perubahan</th>
                                <th class="px-6 py-3 text-left">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentLogs as $log)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-3 text-gray-500 whitespace-nowrap text-xs">
                                        {{ $log->created_at->format('d M Y, H:i:s') }}
                                        <span class="text-gray-400">({{ $log->created_at->diffForHumans() }})</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ $log->user_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $log->user_role }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        {{ $log->description }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-400 font-mono text-xs">
                                        {{ $log->ip_address ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                        Belum ada riwayat perubahan pengaturan sistem yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

