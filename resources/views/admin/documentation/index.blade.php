<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ __('Kelola Dokumentasi & Manual Book') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Kelola versi rilis, artikel panduan web (HTML), dan berkas PDF manual book.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('documentation.index') }}" target="_blank"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold border border-gray-300 transition">
                    <span>👁️</span>
                    <span>Lihat Halaman Pembaca</span>
                </a>
                <a href="{{ route('admin.documentation.versions.create') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow transition">
                    <span>➕</span>
                    <span>Tambah Versi Baru</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg text-rose-800 text-sm font-medium flex items-center justify-between shadow-sm">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Summary Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Versi Web (HTML) Aktif</div>
                    <div class="text-2xl font-black text-indigo-600 mt-1">{{ $stats['active_html_version'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $stats['total_html_versions'] }} total rilis versi web</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Artikel Panduan</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_html_articles'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Bab & halaman panduan terindeks</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Versi PDF Aktif</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['active_pdf_version'] }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $stats['total_pdf_versions'] }} total rilis berkas PDF</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Status Integrasi</div>
                    <div class="text-2xl font-bold text-purple-600 mt-1">✓ Audit Log</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Semua perubahan tercatat otomatis</div>
                </div>
            </div>

            <!-- TABEL 1: DAFTAR VERSI PANDUAN WEB (HTML) -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📘</span>
                            <span>Daftar Versi Panduan Web (HTML GoFiber Docs)</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola struktur bab artikel dan rilis panduan interaktif.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Versi</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Judul Panduan</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Jumlah Bab / Artikel</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Tanggal Rilis</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($htmlVersions as $ver)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 font-bold text-gray-900 whitespace-nowrap">
                                        {{ $ver->version }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-800 font-semibold max-w-sm truncate">
                                        {{ $ver->title }}
                                        <div class="text-[10px] text-gray-400 font-normal truncate">{{ $ver->release_notes ?? 'Tidak ada catatan rilis' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <a href="{{ route('admin.documentation.articles.index', $ver) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs transition border border-indigo-200">
                                            <span>📝 {{ $ver->articles_count }} Artikel</span>
                                            <span>→</span>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $ver->released_at ? $ver->released_at->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($ver->is_active)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                ✓ Aktif (Live)
                                            </span>
                                        @else
                                            <form action="{{ route('admin.documentation.versions.set-active', $ver) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 border border-gray-200 transition">
                                                    Jadikan Aktif
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                        <a href="{{ route('admin.documentation.articles.index', $ver) }}"
                                            class="text-indigo-600 hover:text-indigo-900 font-bold">Kelola Artikel</a>
                                        <span class="text-gray-300">|</span>
                                        <a href="{{ route('admin.documentation.versions.edit', $ver) }}"
                                            class="text-amber-600 hover:text-amber-800 font-bold">Edit</a>
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('admin.documentation.versions.destroy', $ver) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Hapus versi {{ $ver->version }} beserta seluruh artikelnya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada versi panduan HTML.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABEL 2: DAFTAR VERSI PANDUAN PDF -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📄</span>
                            <span>Daftar Versi Manual Book PDF</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola berkas unduhan PDF resmi manual book.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Versi</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Judul Dokumen</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Ukuran File</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Tanggal Rilis</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($pdfVersions as $pver)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 font-bold text-gray-900 whitespace-nowrap">
                                        {{ $pver->version }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-800 font-semibold max-w-sm truncate">
                                        {{ $pver->title }}
                                        <div class="text-[10px] text-gray-400 font-normal truncate">{{ $pver->file_path ?? 'Belum ada file diunggah' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $pver->formatted_file_size }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $pver->released_at ? $pver->released_at->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($pver->is_active)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                ✓ Aktif (Live)
                                            </span>
                                        @else
                                            <form action="{{ route('admin.documentation.versions.set-active', $pver) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 border border-gray-200 transition">
                                                    Jadikan Aktif
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                        @if($pver->file_path)
                                            <a href="{{ route('documentation.pdf.download', $pver) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-bold">Unduh</a>
                                            <span class="text-gray-300">|</span>
                                        @endif
                                        <a href="{{ route('admin.documentation.versions.edit', $pver) }}"
                                            class="text-amber-600 hover:text-amber-800 font-bold">Edit</a>
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('admin.documentation.versions.destroy', $pver) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Hapus versi PDF {{ $pver->version }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada versi manual book PDF.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
