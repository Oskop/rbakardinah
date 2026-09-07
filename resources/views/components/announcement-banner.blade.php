@auth
    @php
        $activeAnnouncements = \App\Models\Announcement::activeForUser(auth()->user())
            ->orderByRaw("CASE 
                WHEN type = 'danger' THEN 1 
                WHEN type = 'warning' THEN 2 
                WHEN type = 'info' THEN 3 
                WHEN type = 'success' THEN 4 
                ELSE 5 END")
            ->latest('start_at')
            ->get();
    @endphp

    @if($activeAnnouncements->isNotEmpty())
        <div x-data="{
                announcements: {
                    @foreach($activeAnnouncements as $announcement)
                        '{{ $announcement->id }}': sessionStorage.getItem('{{ $announcement->dismiss_key }}') !== 'true',
                    @endforeach
                },
                keys: {
                    @foreach($activeAnnouncements as $announcement)
                        '{{ $announcement->id }}': '{{ $announcement->dismiss_key }}',
                    @endforeach
                },
                get hasVisible() {
                    return Object.values(this.announcements).some(v => v === true);
                },
                get hasDismissed() {
                    return Object.values(this.announcements).some(v => v === false);
                },
                get dismissedCount() {
                    return Object.values(this.announcements).filter(v => v === false).length;
                },
                dismiss(id) {
                    this.announcements[id] = false;
                    sessionStorage.setItem(this.keys[id], 'true');
                },
                restoreAll() {
                    for (let id in this.announcements) {
                        this.announcements[id] = true;
                        sessionStorage.removeItem(this.keys[id]);
                    }
                }
             }">

            <!-- Banner Penayangan Pengumuman di Bagian Atas -->
            <div x-show="hasVisible"
                 style="display: none;"
                 class="w-full space-y-2.5 px-4 sm:px-6 lg:px-8 pt-2 pb-2">
                @foreach($activeAnnouncements as $announcement)
                    @php
                        $colors = match($announcement->type) {
                            'warning' => [
                                'bg' => 'bg-amber-50/95',
                                'border' => 'border-amber-200',
                                'text' => 'text-amber-900',
                                'subtext' => 'text-amber-800',
                                'badge' => 'bg-amber-100 text-amber-900 border-amber-300',
                                'icon_bg' => 'bg-amber-100 text-amber-700',
                                'label' => 'Peringatan',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
                            ],
                            'danger' => [
                                'bg' => 'bg-rose-50/95',
                                'border' => 'border-rose-200',
                                'text' => 'text-rose-900',
                                'subtext' => 'text-rose-800',
                                'badge' => 'bg-rose-100 text-rose-900 border-rose-300',
                                'icon_bg' => 'bg-rose-100 text-rose-700',
                                'label' => 'Penting & Mendesak',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
                            ],
                            'success' => [
                                'bg' => 'bg-emerald-50/95',
                                'border' => 'border-emerald-200',
                                'text' => 'text-emerald-900',
                                'subtext' => 'text-emerald-800',
                                'badge' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                'icon_bg' => 'bg-emerald-100 text-emerald-700',
                                'label' => 'Informasi',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                            ],
                            default => [
                                'bg' => 'bg-sky-50/95',
                                'border' => 'border-sky-200',
                                'text' => 'text-sky-900',
                                'subtext' => 'text-sky-800',
                                'badge' => 'bg-sky-100 text-sky-900 border-sky-300',
                                'icon_bg' => 'bg-sky-100 text-sky-700',
                                'label' => 'Pengumuman',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                            ],
                        };
                    @endphp

                    <div x-show="announcements['{{ $announcement->id }}']"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="w-full p-4 rounded-2xl border {{ $colors['border'] }} {{ $colors['bg'] }} {{ $colors['text'] }} shadow-xs relative overflow-hidden backdrop-blur-xs">
                        
                        <div class="flex items-start gap-3.5">
                            <!-- Icon Badge -->
                            <div class="w-9 h-9 rounded-xl {{ $colors['icon_bg'] }} flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $colors['icon'] !!}
                                </svg>
                            </div>

                            <!-- Content Area -->
                            <div class="flex-1 min-w-0 pr-6">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $colors['badge'] }}">
                                        {{ $colors['label'] }}
                                    </span>
                                    <h4 class="text-sm font-bold tracking-tight {{ $colors['text'] }}">
                                        {{ $announcement->title }}
                                    </h4>
                                </div>

                                <div class="text-xs {{ $colors['subtext'] }} leading-relaxed whitespace-pre-line mt-1">
                                    {!! nl2br(e($announcement->content)) !!}
                                </div>

                                <div class="flex flex-wrap items-center gap-4 mt-2.5 text-[11px] opacity-80">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @if($announcement->end_at)
                                            Berakhir: <strong class="font-semibold">{{ $announcement->end_at->translatedFormat('d M Y, H:i') }}</strong> 
                                            ({{ $announcement->end_at->diffForHumans() }})
                                        @else
                                            <span>Aktif berkelanjutan</span>
                                        @endif
                                    </span>

                                    @if($announcement->reshown_at)
                                        <span class="flex items-center gap-1 text-indigo-600 font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>Ditegaskan ulang: {{ $announcement->reshown_at->diffForHumans() }}</span>
                                        </span>
                                    @endif

                                    @if($announcement->creator)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Oleh: {{ $announcement->creator->name }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Dismiss / Close Button -->
                            <button type="button" @click="dismiss('{{ $announcement->id }}')" 
                                    title="Sembunyikan pengumuman ini untuk sementara"
                                    class="absolute top-3.5 right-3.5 p-1.5 rounded-xl hover:bg-black/5 text-current opacity-60 hover:opacity-100 transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Floating Action Pill: Munculkan Kembali Pengumuman (Tampil jika ada yang di-dismiss) -->
            <div x-show="hasDismissed"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 style="display: none;"
                 class="fixed bottom-6 right-6 z-40">
                <button type="button" 
                        @click="restoreAll()"
                        title="Klik untuk memunculkan kembali pengumuman yang ditutup"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/95 hover:bg-white text-indigo-700 hover:text-indigo-900 rounded-full shadow-lg hover:shadow-xl border border-indigo-200 backdrop-blur-md text-xs font-bold transition-all hover:scale-105 active:scale-95 cursor-pointer group">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-600"></span>
                    </span>
                    <span class="text-sm">📢</span>
                    <span></span>
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded-full text-[10px] font-extrabold" x-text="dismissedCount"></span>
                </button>
            </div>

        </div>
    @endif
@endauth
