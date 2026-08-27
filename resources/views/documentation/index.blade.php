<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-600 text-white rounded-xl shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">
                        {{ __('Dokumentasi & Buku Panduan Sistem') }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Panduan resmi pengoperasian Rencana Bisnis dan Anggaran (RBA) RSUD Kardinah
                    </p>
                </div>
            </div>

            <!-- Top Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <!-- Tab Switcher (Web vs PDF) -->
                <div class="inline-flex p-1 bg-gray-100 rounded-xl border border-gray-200 text-xs font-semibold">
                    <a href="{{ route('documentation.index', ['tab' => 'web', 'version' => $selectedHtmlVersion?->version]) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $tab === 'web' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        📘 Panduan Web
                    </a>
                    <a href="{{ route('documentation.index', ['tab' => 'pdf', 'version' => $selectedHtmlVersion?->version]) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $tab === 'pdf' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        📄 Manual Book PDF
                    </a>
                </div>

                @if(Auth::user()->role === 'Administrator')
                    <a href="{{ route('admin.documentation.index') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition shadow-sm"
                        title="Panel Kelola Dokumentasi Administrator">
                        <span>⚙️</span>
                        <span>Kelola Dokumentasi</span>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- Custom Style for GoFiber Docs Article Reader -->
    <style>
        .docs-article h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            scroll-margin-top: 6rem;
        }
        .docs-article h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            scroll-margin-top: 6rem;
        }
        .docs-article p {
            font-size: 0.875rem;
            line-height: 1.7;
            color: #334155;
            margin-bottom: 1rem;
        }
        .docs-article ul, .docs-article ol {
            font-size: 0.875rem;
            line-height: 1.7;
            color: #334155;
            margin-bottom: 1rem;
        }
        .admonition {
            margin: 1.25rem 0;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            border-left-width: 4px;
            font-size: 0.825rem;
            line-height: 1.6;
        }
        .admonition-title {
            font-weight: 800;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .admonition-info {
            background-color: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
        }
        .admonition-tip {
            background-color: #f0fdf4;
            border-color: #22c55e;
            color: #166534;
        }
        .admonition-warning {
            background-color: #fffbeb;
            border-color: #f59e0b;
            color: #92400e;
        }
        .admonition-danger {
            background-color: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .step-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            margin: 0.85rem 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
        }
        .step-badge {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            background: #4f46e5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        .step-content h4 {
            font-size: 0.875rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }
        .step-content p {
            font-size: 0.8rem;
            color: #475569;
            margin-bottom: 0;
        }
    </style>

    <div class="py-6" x-data="docsReader({{ Js::from($searchIndex) }})">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('error'))
                <div class="mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg text-rose-800 text-sm font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @if($tab === 'web')
                <!-- GoFiber Style 3-Column Documentation Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN: Navigation Tree / Sidebar (col-span-3) -->
                    <aside class="lg:col-span-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-sm sticky top-6 max-h-[calc(100vh-6rem)] overflow-y-auto space-y-6">
                        <!-- Search Bar Trigger (Ctrl+K) -->
                        <div>
                            <button type="button" @click="openSearchModal()"
                                class="w-full flex items-center justify-between px-3 py-2 text-xs text-gray-500 bg-slate-50 hover:bg-slate-100 rounded-xl border border-gray-200 transition shadow-inner">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <span>Cari dokumentasi...</span>
                                </div>
                                <kbd class="px-1.5 py-0.5 text-[10px] font-semibold text-gray-500 bg-white border border-gray-300 rounded shadow-sm">Ctrl+K</kbd>
                            </button>
                        </div>

                        <!-- Version Selector Dropdown -->
                        <div class="border-t border-gray-100 pt-3">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Versi Dokumentasi</label>
                            <select onchange="window.location.href = this.value"
                                class="block w-full text-xs font-bold text-gray-700 bg-slate-50 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                                @foreach($htmlVersions as $ver)
                                    <option value="{{ route('documentation.index', ['version' => $ver->version, 'tab' => 'web']) }}"
                                        {{ $selectedHtmlVersion && $selectedHtmlVersion->id === $ver->id ? 'selected' : '' }}>
                                        {{ $ver->version }} {{ $ver->is_active ? '(Aktif / Terbaru)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Categories & Articles Navigation Tree -->
                        <nav class="space-y-4">
                            @forelse($groupedArticles as $category => $items)
                                <div>
                                    <div class="text-[11px] font-black uppercase tracking-wider text-gray-400 mb-1.5 px-2">
                                        {{ $category }}
                                    </div>
                                    <ul class="space-y-0.5">
                                        @foreach($items as $art)
                                            @php
                                                $isActiveArticle = $currentArticle && $currentArticle->id === $art->id;
                                            @endphp
                                            <li>
                                                <a href="{{ route('documentation.index', ['version' => $selectedHtmlVersion?->version, 'article' => $art->slug, 'tab' => 'web']) }}"
                                                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition {{ $isActiveArticle ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-gray-600 hover:bg-slate-50 hover:text-gray-900' }}">
                                                    <span>{{ $art->icon ?? '📄' }}</span>
                                                    <span class="truncate">{{ $art->title }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @empty
                                <div class="p-3 text-xs text-gray-500 text-center">
                                    Belum ada artikel panduan dalam versi ini.
                                </div>
                            @endforelse
                        </nav>
                    </aside>

                    <!-- CENTER COLUMN: Article Reader (col-span-6 or 7) -->
                    <main class="lg:col-span-6 bg-white p-6 sm:p-8 rounded-2xl border border-gray-200 shadow-sm min-h-[600px]">
                        @if($currentArticle)
                            <!-- Breadcrumb -->
                            <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
                                <span>Dokumentasi</span>
                                <span>/</span>
                                <span class="text-gray-600">{{ $currentArticle->category }}</span>
                                <span>/</span>
                                <span class="font-semibold text-indigo-600">{{ $currentArticle->title }}</span>
                            </div>

                            <!-- Title & Metadata -->
                            <div class="pb-4 border-b border-gray-100 mb-6">
                                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                                    <span>{{ $currentArticle->icon ?? '📄' }}</span>
                                    <span>{{ $currentArticle->title }}</span>
                                </h1>
                                <div class="flex items-center gap-4 text-xs text-gray-400 mt-2">
                                    <span>Versi: <strong class="text-gray-700">{{ $selectedHtmlVersion?->version }}</strong></span>
                                    <span>•</span>
                                    <span>Terakhir diperbarui: {{ $currentArticle->updated_at->timezone('Asia/Jakarta')->format('d M Y') }}</span>
                                </div>
                            </div>

                            <!-- Article Content Body -->
                            <div id="docs-content" class="docs-article leading-relaxed">
                                {!! $currentArticle->content !!}
                            </div>

                            <!-- Bottom Next / Previous Navigation -->
                            <div class="mt-12 pt-6 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @if($prevArticle)
                                    <a href="{{ route('documentation.index', ['version' => $selectedHtmlVersion?->version, 'article' => $prevArticle->slug, 'tab' => 'web']) }}"
                                        class="p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/30 transition text-left group">
                                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider group-hover:text-indigo-600">← Sebelumnya</div>
                                        <div class="text-xs font-bold text-gray-900 mt-0.5 flex items-center gap-1">
                                            <span>{{ $prevArticle->icon ?? '📄' }}</span>
                                            <span>{{ $prevArticle->title }}</span>
                                        </div>
                                    </a>
                                @else
                                    <div></div>
                                @endif

                                @if($nextArticle)
                                    <a href="{{ route('documentation.index', ['version' => $selectedHtmlVersion?->version, 'article' => $nextArticle->slug, 'tab' => 'web']) }}"
                                        class="p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/30 transition text-right group">
                                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider group-hover:text-indigo-600">Selanjutnya →</div>
                                        <div class="text-xs font-bold text-gray-900 mt-0.5 flex items-center justify-end gap-1">
                                            <span>{{ $nextArticle->title }}</span>
                                            <span>{{ $nextArticle->icon ?? '📄' }}</span>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-16 text-gray-500">
                                <div class="text-4xl mb-2">📚</div>
                                <h3 class="font-bold text-gray-800 text-sm">Dokumentasi Belum Tersedia</h3>
                                <p class="text-xs text-gray-500 mt-1">Belum ada artikel panduan dalam rilis versi ini.</p>
                            </div>
                        @endif
                    </main>

                    <!-- RIGHT COLUMN: Table of Contents / On This Page (col-span-3) -->
                    <aside class="hidden lg:block lg:col-span-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-sm sticky top-6 max-h-[calc(100vh-6rem)] overflow-y-auto">
                        <div class="text-[11px] font-black uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                            </svg>
                            <span>On This Page</span>
                        </div>
                        
                        <nav id="toc-nav" class="space-y-1 text-xs">
                            <template x-for="heading in headings" :key="heading.id">
                                <a :href="'#' + heading.id"
                                    @click.prevent="scrollToHeading(heading.id)"
                                    class="block py-1 px-2 rounded hover:bg-slate-50 transition leading-snug"
                                    :class="{
                                        'pl-2 text-gray-600 font-medium hover:text-indigo-600': heading.level === 2,
                                        'pl-4 text-gray-400 font-normal hover:text-indigo-600': heading.level === 3,
                                        'text-indigo-600 font-bold bg-indigo-50/50': activeHeadingId === heading.id
                                    }"
                                    x-text="heading.text">
                                </a>
                            </template>
                            <div x-show="headings.length === 0" class="text-[11px] text-gray-400 italic py-2">
                                Tidak ada sub-judul pada halaman ini.
                            </div>
                        </nav>

                        <!-- Release Info Card -->
                        <div class="mt-8 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-600 text-[11px] space-y-1.5">
                            <div class="font-bold text-slate-800 flex items-center gap-1">
                                <span>📌</span> Versi Rilis {{ $selectedHtmlVersion?->version }}
                            </div>
                            <p class="text-[10px] leading-relaxed text-slate-500">
                                {{ $selectedHtmlVersion?->release_notes ?? 'Dokumentasi resmi sistem RBA RSUD Kardinah.' }}
                            </p>
                            <div class="text-[9px] text-slate-400 pt-1 border-t border-slate-200">
                                Tanggal: {{ $selectedHtmlVersion?->released_at ? $selectedHtmlVersion->released_at->format('d/m/Y') : '-' }}
                            </div>
                        </div>
                    </aside>

                </div>

            @else
                <!-- TAB PDF: Manual Book PDF Viewer & Download -->
                <div class="space-y-6">
                    <!-- Active PDF Overview Card -->
                    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="p-3.5 bg-rose-100 text-rose-600 rounded-2xl shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 bg-rose-100 text-rose-800 rounded-full text-xs font-bold uppercase">Dokumen Resmi PDF</span>
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">Versi Aktif: {{ $activePdfVersion?->version ?? 'v1.0.0' }}</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-900 mt-1">
                                    {{ $activePdfVersion?->title ?? 'Buku Panduan Penggunaan Sistem RBA RSUD Kardinah' }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed max-w-2xl">
                                    {{ $activePdfVersion?->release_notes ?? 'Berkas cetak resmi buku panduan pengoperasian modul Rencana Bisnis dan Anggaran.' }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-gray-400 mt-2 font-medium">
                                    <span>Ukuran: <strong class="text-gray-700">{{ $activePdfVersion?->formatted_file_size }}</strong></span>
                                    <span>•</span>
                                    <span>Rilis: <strong class="text-gray-700">{{ $activePdfVersion?->released_at ? $activePdfVersion->released_at->format('d F Y') : '-' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                            @if($activePdfVersion && $activePdfVersion->file_path)
                                <a href="{{ route('documentation.pdf.download', $activePdfVersion) }}"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <span>Unduh Berkas PDF</span>
                                </a>
                                <a href="{{ route('documentation.pdf.preview', $activePdfVersion) }}" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl border border-gray-300 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span>Buka di Tab Baru</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- PDF Preview Section (If file exists) -->
                    @if($activePdfVersion && $activePdfVersion->file_path)
                        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3 px-2">
                                <span class="text-xs font-bold text-gray-700">Preview Dokumen PDF Terbuka</span>
                                <span class="text-[11px] text-gray-400">Gunakan toolbar viewer untuk zoom atau cetak</span>
                            </div>
                            <div class="w-full h-[650px] bg-slate-100 rounded-xl overflow-hidden border border-gray-300">
                                <iframe src="{{ route('documentation.pdf.preview', $activePdfVersion) }}" class="w-full h-full" frameborder="0"></iframe>
                            </div>
                        </div>
                    @endif

                    <!-- PDF Version History Table -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                        <h4 class="text-sm font-bold text-gray-900 mb-3">Arsip Riwayat Versi Manual Book PDF</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Versi</th>
                                        <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Judul Dokumen</th>
                                        <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Tanggal Rilis</th>
                                        <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Ukuran</th>
                                        <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Catatan Pembaruan</th>
                                        <th class="px-4 py-2.5 text-center font-bold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($pdfVersions as $pver)
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 font-bold text-gray-900 whitespace-nowrap">
                                                {{ $pver->version }}
                                                @if($pver->is_active)
                                                    <span class="ml-1.5 px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold">Aktif</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-800 font-semibold">{{ $pver->title }}</td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $pver->released_at ? $pver->released_at->format('d/m/Y') : '-' }}</td>
                                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $pver->formatted_file_size }}</td>
                                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $pver->release_notes ?? '-' }}</td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap space-x-1.5">
                                                @if($pver->file_path)
                                                    <a href="{{ route('documentation.pdf.download', $pver) }}"
                                                        class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Unduh</a>
                                                    <span class="text-gray-300">|</span>
                                                    <a href="{{ route('documentation.pdf.preview', $pver) }}" target="_blank"
                                                        class="text-gray-600 hover:text-gray-900 font-bold text-xs">Lihat</a>
                                                @else
                                                    <span class="text-gray-400 italic text-[11px]">Tidak ada file</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-6 text-center text-gray-400 italic">Belum ada riwayat dokumen PDF.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- INSTANT SEARCH MODAL (Ctrl + K) -->
        <div x-show="searchModalOpen" style="display: none;"
            @keydown.window.escape="searchModalOpen = false"
            @keydown.window.ctrl.k.prevent="openSearchModal()"
            @keydown.window.cmd.k.prevent="openSearchModal()"
            class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20" role="dialog" aria-modal="true">
            
            <div x-show="searchModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity"
                @click="searchModalOpen = false"></div>

            <div x-show="searchModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="mx-auto max-w-2xl transform divide-y divide-gray-100 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all">
                
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" x-model="searchQuery" x-ref="searchInput"
                        placeholder="Ketik kata kunci pencarian dokumentasi (misal: validasi, pagu, revisi)..."
                        class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm">
                </div>

                <!-- Search Results List -->
                <div class="max-h-96 scroll-py-3 overflow-y-auto p-3">
                    <template x-for="item in filteredResults" :key="item.id">
                        <a :href="'{{ route('documentation.index', ['version' => $selectedHtmlVersion?->version]) }}&article=' + item.slug"
                            class="flex flex-col select-none rounded-xl p-3 hover:bg-indigo-50/70 transition group">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-indigo-600 flex items-center gap-1.5" x-text="item.icon + ' ' + item.title"></span>
                                <span class="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-semibold" x-text="item.category"></span>
                            </div>
                            <div class="text-[11px] text-gray-500 leading-snug line-clamp-2" x-text="item.snippet"></div>
                        </a>
                    </template>

                    <div x-show="filteredResults.length === 0 && searchQuery.trim() !== ''" class="p-6 text-center text-xs text-gray-500">
                        Tidak ada artikel yang cocok dengan kata kunci "<span class="font-bold" x-text="searchQuery"></span>".
                    </div>

                    <div x-show="searchQuery.trim() === ''" class="p-6 text-center text-xs text-gray-400">
                        Mulai mengetik untuk mencari judul bab atau isi panduan...
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between bg-gray-50 px-4 py-2.5 text-[11px] text-gray-500">
                    <div class="flex items-center gap-2">
                        <span>Navigasi: <kbd class="px-1.5 py-0.5 rounded bg-white border font-mono">ESC</kbd> untuk menutup</span>
                    </div>
                    <span class="font-semibold text-indigo-600">{{ $searchIndex->count() }} artikel terindeks</span>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function docsReader(searchIndex) {
            return {
                searchModalOpen: false,
                searchQuery: '',
                searchData: searchIndex || [],
                headings: [],
                activeHeadingId: null,

                init() {
                    this.extractHeadings();
                    this.initScrollspy();
                },

                openSearchModal() {
                    this.searchModalOpen = true;
                    this.searchQuery = '';
                    this.$nextTick(() => {
                        this.$refs.searchInput.focus();
                    });
                },

                get filteredResults() {
                    if (!this.searchQuery || this.searchQuery.trim() === '') {
                        return this.searchData.slice(0, 5);
                    }
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.searchData.filter(item => {
                        return (item.title && item.title.toLowerCase().includes(q)) ||
                               (item.category && item.category.toLowerCase().includes(q)) ||
                               (item.snippet && item.snippet.toLowerCase().includes(q));
                    });
                },

                extractHeadings() {
                    const contentEl = document.getElementById('docs-content');
                    if (!contentEl) return;

                    const headingEls = contentEl.querySelectorAll('h2, h3');
                    const items = [];
                    headingEls.forEach((el, index) => {
                        if (!el.id) {
                            el.id = 'heading-' + index + '-' + el.innerText.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
                        }
                        items.push({
                            id: el.id,
                            text: el.innerText,
                            level: el.tagName === 'H2' ? 2 : 3
                        });
                    });
                    this.headings = items;
                },

                scrollToHeading(id) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth' });
                        this.activeHeadingId = id;
                    }
                },

                initScrollspy() {
                    window.addEventListener('scroll', () => {
                        if (this.headings.length === 0) return;
                        const scrollPosition = window.scrollY + 120;
                        for (let i = this.headings.length - 1; i >= 0; i--) {
                            const el = document.getElementById(this.headings[i].id);
                            if (el && el.offsetTop <= scrollPosition) {
                                this.activeHeadingId = this.headings[i].id;
                                break;
                            }
                        }
                    }, { passive: true });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
