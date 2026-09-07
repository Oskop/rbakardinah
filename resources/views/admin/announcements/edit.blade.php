<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.announcements.index') }}" 
                   class="p-2 bg-white rounded-xl border border-gray-200 text-gray-600 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                        <span>✏️</span>
                        <span>{{ __('Edit Pengumuman') }}</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Perbarui pesan, sasaran target penerima, atau durasi penayangan pengumuman.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2.5 py-1 rounded-full font-bold {{ $announcement->status_info['class'] }}">
                    {{ $announcement->status_info['label'] }}
                </span>
                <a href="{{ route('admin.announcements.index') }}" 
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all">
                    Kembali ke Daftar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">

            @if ($errors->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-sm shadow-xs">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Terdapat beberapa kesalahan pengisian form:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700 pl-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST" 
                  x-data="announcementEditForm()" class="space-y-8">
                @csrf
                @method('PUT')

                <!-- Section 1: Informasi Konten Pengumuman -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200 shadow-sm space-y-6">
                    <div class="border-b border-gray-100 pb-4">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-sm">1</span>
                            <span>Isi Pesan & Kategori Pengumuman</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Tentukan judul, tipe visual peringatan, dan pesan yang ingin disampaikan kepada pengguna.</p>
                    </div>

                    <!-- Judul Pengumuman -->
                    <div>
                        <label for="title" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Judul Pengumuman <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" required x-model="title"
                               placeholder="Contoh: Pemeliharaan Server / Batas Akhir Finalisasi RBA 2026"
                               class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs transition-colors" />
                    </div>

                    <!-- Pilihan Tipe / Urgensi Pengumuman -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                            Tipe & Urgensi Pengumuman <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Info -->
                            <label class="cursor-pointer border-2 rounded-xl p-3 flex items-start gap-3 transition-all"
                                   :class="type === 'info' ? 'border-sky-500 bg-sky-50/50 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                                <input type="radio" name="type" value="info" x-model="type" class="sr-only">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold text-gray-900">Informasi (Biru)</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">Pemberitahuan umum & berita sistem</div>
                                </div>
                            </label>

                            <!-- Warning -->
                            <label class="cursor-pointer border-2 rounded-xl p-3 flex items-start gap-3 transition-all"
                                   :class="type === 'warning' ? 'border-amber-500 bg-amber-50/50 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                                <input type="radio" name="type" value="warning" x-model="type" class="sr-only">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold text-gray-900">Peringatan (Kuning)</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">Batas waktu, pengingat deadline</div>
                                </div>
                            </label>

                            <!-- Danger -->
                            <label class="cursor-pointer border-2 rounded-xl p-3 flex items-start gap-3 transition-all"
                                   :class="type === 'danger' ? 'border-rose-500 bg-rose-50/50 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                                <input type="radio" name="type" value="danger" x-model="type" class="sr-only">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold text-gray-900">Penting / Kritis (Merah)</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">Penutupan akses, darurat</div>
                                </div>
                            </label>

                            <!-- Success -->
                            <label class="cursor-pointer border-2 rounded-xl p-3 flex items-start gap-3 transition-all"
                                   :class="type === 'success' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                                <input type="radio" name="type" value="success" x-model="type" class="sr-only">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div class="text-left">
                                    <div class="text-xs font-bold text-gray-900">Sukses (Hijau)</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">Pembaruan sukses, pengumuman kabar baik</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Isi Pesan Pengumuman -->
                    <div>
                        <label for="content" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Isi Pesan Pengumuman <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="content" id="content" rows="4" required x-model="content"
                                  placeholder="Tuliskan pesan lengkap yang ingin ditampilkan. Mendukung baris baru / enter..."
                                  class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs transition-colors"></textarea>
                    </div>

                    <!-- Live Preview Card -->
                    <div class="p-4 rounded-xl border border-dashed border-gray-300 bg-slate-50/70 space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <span>Pratinjau Langsung Tampilan Banner Pengguna:</span>
                            <span class="text-indigo-600 font-semibold lowercase">real-time preview</span>
                        </div>
                        
                        <div class="p-4 rounded-xl border flex items-start gap-3 shadow-xs transition-all"
                             :class="{
                                 'bg-sky-50/90 border-sky-200 text-sky-900': type === 'info',
                                 'bg-amber-50/90 border-amber-200 text-amber-900': type === 'warning',
                                 'bg-rose-50/90 border-rose-200 text-rose-900': type === 'danger',
                                 'bg-emerald-50/90 border-emerald-200 text-emerald-900': type === 'success',
                             }">
                            <div class="shrink-0 mt-0.5">
                                <template x-if="type === 'info'">
                                    <span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-sm">ℹ️</span>
                                </template>
                                <template x-if="type === 'warning'">
                                    <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">⚠️</span>
                                </template>
                                <template x-if="type === 'danger'">
                                    <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm">⛔</span>
                                </template>
                                <template x-if="type === 'success'">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">✅</span>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold truncate" x-text="title || 'Judul Pengumuman'"></h4>
                                <p class="text-xs mt-1 whitespace-pre-line text-opacity-90 leading-relaxed" x-text="content || 'Isi pesan...'"></p>
                            </div>
                            <div class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full border border-current opacity-70">
                                Banner Live
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Target Pengumuman -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200 shadow-sm space-y-6">
                    <div class="border-b border-gray-100 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-sm">2</span>
                                <span>Target Sasaran Penerima</span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Pilih siapa saja pengguna yang berhak melihat pengumuman ini pada dashboard mereka.</p>
                        </div>
                        <template x-if="targetType === 'specific_users'">
                            <span class="text-xs font-bold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg border border-indigo-100">
                                Terpilih: <span x-text="selectedUserIds.length"></span> Pengguna
                            </span>
                        </template>
                    </div>

                    <!-- Radio Options Target Type -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- All Users -->
                        <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col justify-between transition-all"
                               :class="targetType === 'all' ? 'border-indigo-600 bg-indigo-50/40 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">🌐</span>
                                    <input type="radio" name="target_type" value="all" x-model="targetType" class="sr-only">
                                </div>
                                <div class="text-xs font-bold text-gray-900">Semua Pengguna</div>
                                <div class="text-[11px] text-gray-500 mt-1">Muncul untuk seluruh admin, supervisor, dan operator di seluruh unit.</div>
                            </div>
                        </label>

                        <!-- All Supervisors -->
                        <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col justify-between transition-all"
                               :class="targetType === 'all_supervisors' ? 'border-indigo-600 bg-indigo-50/40 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">👔</span>
                                    <input type="radio" name="target_type" value="all_supervisors" x-model="targetType" class="sr-only">
                                </div>
                                <div class="text-xs font-bold text-gray-900">Semua Supervisor</div>
                                <div class="text-[11px] text-gray-500 mt-1">Hanya supervisor ({{ $allSupervisorsCount }} orang) di semua unit kerja.</div>
                            </div>
                        </label>

                        <!-- All Operators -->
                        <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col justify-between transition-all"
                               :class="targetType === 'all_operators' ? 'border-indigo-600 bg-indigo-50/40 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">👷</span>
                                    <input type="radio" name="target_type" value="all_operators" x-model="targetType" class="sr-only">
                                </div>
                                <div class="text-xs font-bold text-gray-900">Semua Operator</div>
                                <div class="text-[11px] text-gray-500 mt-1">Hanya operator ({{ $allOperatorsCount }} orang) di semua unit kerja.</div>
                            </div>
                        </label>

                        <!-- Specific Users -->
                        <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col justify-between transition-all"
                               :class="targetType === 'specific_users' ? 'border-indigo-600 bg-indigo-50/40 shadow-xs' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">🎯</span>
                                    <input type="radio" name="target_type" value="specific_users" x-model="targetType" class="sr-only">
                                </div>
                                <div class="text-xs font-bold text-gray-900">Pengguna Tertentu</div>
                                <div class="text-[11px] text-gray-500 mt-1">Pilih spesifik beberapa supervisor / operator sesuai unit kerja.</div>
                            </div>
                        </label>
                    </div>

                    <!-- Panel Pemilihan Pengguna Spesifik -->
                    <div x-show="targetType === 'specific_users'" x-transition class="space-y-4 pt-2">
                        <div class="bg-slate-50 p-4 rounded-xl border border-gray-200 space-y-3">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                <div class="relative w-full sm:w-72">
                                    <input type="text" x-model="userSearch" placeholder="Cari nama atau NIP..."
                                           class="w-full text-xs rounded-xl border-gray-300 pl-8 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 shadow-xs" />
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" @click="selectAllSupervisors()"
                                            class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-semibold border border-purple-200 transition-colors">
                                        + Pilih Semua Spv
                                    </button>
                                    <button type="button" @click="selectAllOperators()"
                                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold border border-emerald-200 transition-colors">
                                        + Pilih Semua Opr
                                    </button>
                                    <button type="button" @click="clearAllUsers()"
                                            class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition-colors">
                                        Kosongkan Pilihan
                                    </button>
                                </div>
                            </div>

                            <!-- Daftar Accordion / List Unit & Pengguna -->
                            <div class="max-h-96 overflow-y-auto pr-1 space-y-3">
                                @forelse($units as $unit)
                                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-xs"
                                         x-data="{ open: true }"
                                         x-show="unitHasMatchingUsers('{{ $unit->id }}')">
                                        <div class="px-4 py-2.5 bg-slate-100/70 border-b border-gray-200 flex items-center justify-between">
                                            <div class="flex items-center gap-2 cursor-pointer select-none" @click="open = !open">
                                                <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                                <span class="text-xs font-bold text-gray-800">{{ $unit->name }}</span>
                                                <span class="text-[10px] text-gray-500">({{ $unit->users->count() }} orang)</span>
                                            </div>
                                            <button type="button" @click="toggleUnitUsers('{{ $unit->id }}')" 
                                                    class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                                                Toggle Semua di Unit
                                            </button>
                                        </div>

                                        <div x-show="open" class="p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                            @forelse($unit->users as $u)
                                                <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-gray-200 transition-colors text-xs"
                                                       x-show="matchesSearch('{{ strtolower($u->name) }}', '{{ strtolower($u->nip ?? '') }}')"
                                                       data-unit-id="{{ $unit->id }}"
                                                       data-user-id="{{ $u->id }}"
                                                       data-user-role="{{ $u->role }}">
                                                    <input type="checkbox" name="target_user_ids[]" value="{{ $u->id }}"
                                                           x-model="selectedUserIds"
                                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="font-semibold text-gray-900 truncate">{{ $u->name }}</div>
                                                        <div class="flex items-center gap-1.5 mt-0.5">
                                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $u->role === 'Supervisor' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                                {{ $u->role }}
                                                            </span>
                                                            @if($u->nip)
                                                                <span class="text-[10px] text-gray-400 truncate">{{ $u->nip }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            @empty
                                                <div class="col-span-full py-2 text-center text-xs text-gray-400">Tidak ada user aktif di unit ini.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-xs text-gray-400">Belum ada unit kerja terdaftar.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Jadwal, Waktu & Durasi Penayangan -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200 shadow-sm space-y-6">
                    <div class="border-b border-gray-100 pb-4">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-sm">3</span>
                            <span>Rentang Waktu & Durasi Penayangan</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Tentukan kapan pengumuman mulai tayang dan kapan otomatis berakhir. Anda juga dapat menggunakan tombol pintas durasi.</p>
                    </div>

                    <!-- Presets Durasi Cepat -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Pintasan Durasi Cepat (Hitung Otomatis Berakhir):
                            </label>
                            <span class="text-[11px] text-indigo-600 font-medium">Klik untuk menambah durasi dari waktu mulai</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" @click="addDuration(15, 'minutes')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+15 Menit</button>
                            <button type="button" @click="addDuration(30, 'minutes')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+30 Menit</button>
                            <button type="button" @click="addDuration(1, 'hours')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+1 Jam</button>
                            <button type="button" @click="addDuration(3, 'hours')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+3 Jam</button>
                            <button type="button" @click="addDuration(6, 'hours')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+6 Jam</button>
                            <button type="button" @click="addDuration(12, 'hours')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+12 Jam</button>
                            <button type="button" @click="addDuration(1, 'days')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+1 Hari</button>
                            <button type="button" @click="addDuration(3, 'days')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+3 Hari</button>
                            <button type="button" @click="addDuration(7, 'days')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+1 Minggu</button>
                            <button type="button" @click="addDuration(1, 'months')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+1 Bulan</button>
                            <button type="button" @click="addDuration(1, 'years')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border border-slate-200 rounded-lg text-xs font-bold transition-all">+1 Tahun</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- Waktu Mulai -->
                        <div>
                            <label for="start_at" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                Waktu Mulai Tayang <span class="text-rose-500">*</span>
                            </label>
                            <input type="datetime-local" name="start_at" id="start_at" required x-model="startAt"
                                   step="1"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs" />
                            <p class="text-[11px] text-gray-500 mt-1">Pengumuman akan mulai terlihat pada tanggal dan jam ini.</p>
                        </div>

                        <!-- Waktu Selesai -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="end_at" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                    Waktu Selesai Tayang
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-medium text-gray-600">
                                    <input type="checkbox" x-model="noEndTime" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Tanpa Batas Waktu</span>
                                </label>
                            </div>
                            <input type="datetime-local" name="end_at" id="end_at" x-model="endAt"
                                   step="1"
                                   :disabled="noEndTime"
                                   :class="noEndTime ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : ''"
                                   class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-xs" />
                            <p class="text-[11px] text-gray-500 mt-1" x-show="!noEndTime">Setelah waktu ini tercapai, pengumuman otomatis berhenti tayang.</p>
                            <p class="text-[11px] text-amber-600 font-semibold mt-1" x-show="noEndTime">Pengumuman akan tayang terus menerus hingga diakhiri manual oleh admin.</p>
                        </div>
                    </div>

                    <!-- Status Aktif -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-gray-900">Status Aktif Pengumuman</div>
                            <div class="text-[11px] text-gray-500">Bisa di-uncheck untuk menyembunyikan sementara pengumuman ini.</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.announcements.index') }}" 
                       class="px-5 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-bold transition-all shadow-xs">
                        Batal
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    @push('scripts')
    <script>
        function announcementEditForm() {
            return {
                title: @json(old('title', $announcement->title)),
                content: @json(old('content', $announcement->content)),
                type: @json(old('type', $announcement->type)),
                targetType: @json(old('target_type', $announcement->target_type)),
                startAt: @json(old('start_at', $announcement->start_at ? $announcement->start_at->format('Y-m-d\TH:i:s') : '')),
                endAt: @json(old('end_at', $announcement->end_at ? $announcement->end_at->format('Y-m-d\TH:i:s') : '')),
                noEndTime: {{ old('end_at', $announcement->end_at) ? 'false' : 'true' }},
                userSearch: '',
                selectedUserIds: @json(old('target_user_ids', $selectedUserIds)),

                matchesSearch(name, nip) {
                    if (!this.userSearch) return true;
                    const q = this.userSearch.toLowerCase();
                    return name.includes(q) || nip.includes(q);
                },

                unitHasMatchingUsers(unitId) {
                    if (!this.userSearch) return true;
                    const items = document.querySelectorAll(`[data-unit-id="${unitId}"]`);
                    for (let el of items) {
                        const name = el.textContent.toLowerCase();
                        if (name.includes(this.userSearch.toLowerCase())) {
                            return true;
                        }
                    }
                    return false;
                },

                selectAllSupervisors() {
                    const spvElements = document.querySelectorAll('[data-user-role="Supervisor"]');
                    spvElements.forEach(el => {
                        const uid = parseInt(el.getAttribute('data-user-id'));
                        if (uid && !this.selectedUserIds.includes(uid) && !this.selectedUserIds.includes(String(uid))) {
                            this.selectedUserIds.push(uid);
                        }
                    });
                },

                selectAllOperators() {
                    const oprElements = document.querySelectorAll('[data-user-role="Operator"]');
                    oprElements.forEach(el => {
                        const uid = parseInt(el.getAttribute('data-user-id'));
                        if (uid && !this.selectedUserIds.includes(uid) && !this.selectedUserIds.includes(String(uid))) {
                            this.selectedUserIds.push(uid);
                        }
                    });
                },

                clearAllUsers() {
                    this.selectedUserIds = [];
                },

                toggleUnitUsers(unitId) {
                    const unitElements = document.querySelectorAll(`[data-unit-id="${unitId}"]`);
                    const unitUserIds = [];
                    unitElements.forEach(el => {
                        const uid = parseInt(el.getAttribute('data-user-id'));
                        if (uid) unitUserIds.push(uid);
                    });

                    const allInUnitSelected = unitUserIds.every(id => this.selectedUserIds.includes(id) || this.selectedUserIds.includes(String(id)));
                    if (allInUnitSelected) {
                        this.selectedUserIds = this.selectedUserIds.filter(id => !unitUserIds.includes(id) && !unitUserIds.includes(parseInt(id)));
                    } else {
                        unitUserIds.forEach(id => {
                            if (!this.selectedUserIds.includes(id) && !this.selectedUserIds.includes(String(id))) {
                                this.selectedUserIds.push(id);
                            }
                        });
                    }
                },

                addDuration(amount, unit) {
                    this.noEndTime = false;
                    let base = this.startAt ? new Date(this.startAt) : new Date();
                    if (isNaN(base.getTime())) {
                        base = new Date();
                    }

                    let target = new Date(base.getTime());
                    if (unit === 'minutes') {
                        target.setMinutes(target.getMinutes() + amount);
                    } else if (unit === 'hours') {
                        target.setHours(target.getHours() + amount);
                    } else if (unit === 'days') {
                        target.setDate(target.getDate() + amount);
                    } else if (unit === 'months') {
                        target.setMonth(target.getMonth() + amount);
                    } else if (unit === 'years') {
                        target.setFullYear(target.getFullYear() + amount);
                    }

                    // Format to YYYY-MM-DDTHH:mm:ss
                    const pad = (n) => String(n).padStart(2, '0');
                    const formatted = target.getFullYear() + '-' +
                        pad(target.getMonth() + 1) + '-' +
                        pad(target.getDate()) + 'T' +
                        pad(target.getHours()) + ':' +
                        pad(target.getMinutes()) + ':' +
                        pad(target.getSeconds());

                    this.endAt = formatted;
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
