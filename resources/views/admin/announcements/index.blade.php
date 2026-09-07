<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>📢</span>
                    <span>{{ __('Manajemen Pengumuman Sistem') }}</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Kelola penyiaran pesan, pengumuman terjadwal, dan peringatan batas waktu untuk seluruh level pengguna.</p>
            </div>
            <a href="{{ route('admin.announcements.create') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Buat Pengumuman Baru</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Session Alert -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold shadow-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-800 font-bold">&times;</button>
                </div>
            @endif

            <!-- Tabs Filter & Search Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                        <a href="{{ route('admin.announcements.index', ['status' => 'all', 'search' => $search]) }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>Semua</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'all' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">{{ $stats['all'] }}</span>
                        </a>
                        <a href="{{ route('admin.announcements.index', ['status' => 'running', 'search' => $search]) }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'running' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sedang Tayang</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'running' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $stats['running'] }}</span>
                        </a>
                        <a href="{{ route('admin.announcements.index', ['status' => 'scheduled', 'search' => $search]) }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'scheduled' ? 'bg-white text-sky-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>Terjadwal</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'scheduled' ? 'bg-sky-100 text-sky-800' : 'bg-slate-200 text-slate-700' }}">{{ $stats['scheduled'] }}</span>
                        </a>
                        <a href="{{ route('admin.announcements.index', ['status' => 'expired', 'search' => $search]) }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'expired' ? 'bg-white text-slate-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>Telah Berakhir</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'expired' ? 'bg-slate-200 text-slate-800' : 'bg-slate-200 text-slate-700' }}">{{ $stats['expired'] }}</span>
                        </a>
                        <a href="{{ route('admin.announcements.index', ['status' => 'inactive', 'search' => $search]) }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'inactive' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <span>Disembunyikan</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'inactive' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-700' }}">{{ $stats['inactive'] }}</span>
                        </a>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="{{ route('admin.announcements.index') }}" class="w-full lg:w-72">
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari judul / isi pengumuman..."
                                class="w-full text-xs border-gray-300 rounded-xl pl-9 pr-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            @if($search)
                                <a href="{{ route('admin.announcements.index', ['status' => $statusFilter]) }}" class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 text-xs font-bold">&times;</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements Table -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Pengumuman & Isi</th>
                                <th class="px-4 py-3.5">Tipe</th>
                                <th class="px-4 py-3.5">Sasaran Pengguna</th>
                                <th class="px-4 py-3.5">Jadwal & Masa Aktif</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi Administrator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($announcements as $announcement)
                                @php
                                    $info = $announcement->status_info;
                                    $now = now();
                                    $isCurrentlyRunning = $announcement->isCurrentlyActive();
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <!-- Judul & Konten -->
                                    <td class="px-5 py-4 max-w-sm">
                                        <div class="font-bold text-sm text-gray-900 leading-snug">
                                            {{ $announcement->title }}
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2 leading-relaxed">
                                            {{ $announcement->content }}
                                        </p>
                                        <div class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-2">
                                            <span>Oleh: <strong class="text-gray-600">{{ $announcement->creator->name ?? 'Administrator' }}</strong></span>
                                            <span>&bull;</span>
                                            <span>Dibuat: {{ $announcement->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </td>

                                    <!-- Tipe -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($announcement->type === 'warning')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span>⚠️</span> Peringatan
                                            </span>
                                        @elseif($announcement->type === 'danger')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                                <span>🚨</span> Mendesak
                                            </span>
                                        @elseif($announcement->type === 'success')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <span>✅</span> Informasi Baik
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                <span>ℹ️</span> Info Umum
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Target Audience -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($announcement->target_type === 'all')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <span>🌐</span> Semua Pengguna
                                            </span>
                                        @elseif($announcement->target_type === 'all_supervisors')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                <span>🛡️</span> Semua Supervisor
                                            </span>
                                        @elseif($announcement->target_type === 'all_operators')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                <span>💼</span> Semua Operator
                                            </span>
                                        @elseif($announcement->target_type === 'specific_users')
                                            <div class="space-y-1">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                                    <span>👥</span> {{ $announcement->target_users_count }} Pengguna Terpilih
                                                </span>
                                                <div class="text-[10px] text-gray-400">
                                                    (Kombinasi Spesifik)
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Jadwal & Durasi -->
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">
                                        <div class="font-semibold text-gray-700 flex items-center gap-1">
                                            <span class="text-gray-400 text-[10px]">Mulai:</span>
                                            <span>{{ $announcement->start_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="font-semibold text-gray-700 mt-1 flex items-center gap-1">
                                            <span class="text-gray-400 text-[10px]">Selesai:</span>
                                            @if($announcement->end_at)
                                                <span>{{ $announcement->end_at->format('d/m/Y H:i') }}</span>
                                            @else
                                                <span class="italic text-emerald-600 font-normal">Tanpa Batas Waktu</span>
                                            @endif
                                        </div>
                                        <!-- Relative time hint -->
                                        <div class="text-[10px] mt-1 text-gray-400">
                                            @if($isCurrentlyRunning && $announcement->end_at)
                                                <span class="text-amber-600 font-semibold">Berakhir {{ $announcement->end_at->diffForHumans() }}</span>
                                            @elseif(!$announcement->is_active)
                                                <span class="text-gray-400">Dimatikan manual</span>
                                            @elseif($announcement->start_at->isFuture())
                                                <span class="text-sky-600 font-semibold">Mulai {{ $announcement->start_at->diffForHumans() }}</span>
                                            @elseif($announcement->end_at && $announcement->end_at->isPast())
                                                <span class="text-gray-400">Selesai {{ $announcement->end_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $info['badge'] }}">
                                            <span class="w-2 h-2 rounded-full {{ $info['dot'] }}"></span>
                                            <span>{{ $info['label'] }}</span>
                                        </span>
                                    </td>

                                    <!-- Aksi Administrator -->
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Tombol Edit -->
                                            <a href="{{ route('admin.announcements.edit', $announcement) }}"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-200 transition-all"
                                                title="Ubah Pengumuman">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>

                                            <!-- Tombol Toggle Active (Sembunyikan / Tampilkan) -->
                                            <form action="{{ route('admin.announcements.toggle-active', $announcement) }}" method="POST" class="inline"
                                                onsubmit="return confirm('{{ $announcement->is_active ? 'Sembunyikan pengumuman ini dari penayangan?' : 'Tampilkan kembali pengumuman ini?' }}')">
                                                @csrf
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg border transition-all cursor-pointer {{ $announcement->is_active ? 'text-amber-600 hover:bg-amber-50 border-amber-200' : 'text-emerald-600 hover:bg-emerald-50 border-emerald-200' }}"
                                                    title="{{ $announcement->is_active ? 'Sembunyikan Pengumuman' : 'Aktifkan Pengumuman' }}">
                                                    @if($announcement->is_active)
                                                        <!-- Icon Eye Off -->
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                                    @else
                                                        <!-- Icon Eye On -->
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    @endif
                                                </button>
                                            </form>

                                            <!-- Tombol Akhiri Sekarang (Force End) -->
                                            @if($isCurrentlyRunning)
                                                <form action="{{ route('admin.announcements.force-end', $announcement) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin mengakhiri penayangan pengumuman ini sekarang juga?')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 border border-rose-200 transition-all cursor-pointer"
                                                        title="Akhiri Penayangan Sekarang (Force End)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Hapus pengumuman ini secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 transition-all cursor-pointer"
                                                    title="Hapus Pengumuman">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        <span class="text-4xl block mb-2 opacity-60">📢</span>
                                        <p class="text-sm font-semibold text-slate-600">Tidak ada pengumuman ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Buat Pengumuman Baru" untuk mempublikasikan pesan pertama.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($announcements->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $announcements->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
