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
        <div class="w-full space-y-2.5 px-4 sm:px-6 lg:px-8 pt-4">
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

                <div x-data="{
                        dismissed: sessionStorage.getItem('announcement_dismissed_{{ $announcement->id }}') === 'true',
                        dismiss() {
                            this.dismissed = true;
                            sessionStorage.setItem('announcement_dismissed_{{ $announcement->id }}', 'true');
                        }
                     }" 
                     x-show="!dismissed"
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
                        <button type="button" @click="dismiss()" 
                                title="Tutup pengumuman ini untuk sesi ini"
                                class="absolute top-3.5 right-3.5 p-1.5 rounded-xl hover:bg-black/5 text-current opacity-60 hover:opacity-100 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endauth
