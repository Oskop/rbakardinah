<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    {{ __('Monitoring & Log Akses REST API') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Audit trail dan metrik performa real-time pemanggilan antarmuka REST API eksternal SIPAKAR.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-200 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    API Gateway v1 Active
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{
        showDetailModal: false,
        showPruneModal: false,
        showCliGuide: false,
        activeLog: null,
        loadingDetail: false,

        openDetail(logId) {
            this.loadingDetail = true;
            this.showDetailModal = true;
            fetch('{{ url('admin/api-logs') }}/' + logId)
                .then(res => res.json())
                .then(data => {
                    this.activeLog = data;
                    this.loadingDetail = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loadingDetail = false;
                });
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
                </div>
            @endif

            <!-- Navigation Tabs -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-gray-200 pb-1 gap-3">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('admin.logs.index') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-500 hover:text-indigo-600 hover:bg-gray-50 rounded-t-xl transition">
                        <span>📝 Log Transaksi Database (Internal)</span>
                    </a>
                    <a href="{{ route('admin.api-logs.index') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-indigo-700 bg-white border border-b-0 border-gray-200 rounded-t-xl shadow-2xs">
                        <span>🌐 Log Akses REST API (Eksternal)</span>
                    </a>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('api.documentation') }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition shadow-2xs">
                        <span>📖 Dokumentasi API</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                    <button type="button" @click="showCliGuide = !showCliGuide"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg transition shadow-2xs">
                        <span>⌨️ Panduan CLI</span>
                    </button>
                    <button type="button" @click="showPruneModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition shadow-2xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Bersihkan Log Lama</span>
                    </button>
                </div>
            </div>

            <!-- Admin CLI Reference Panel (Collapsible) -->
            <div x-show="showCliGuide" style="display: none;" x-transition
                 class="bg-slate-900 text-slate-100 p-5 rounded-2xl shadow-md border border-slate-800 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⌨️</span>
                        <div>
                            <h3 class="font-bold text-sm text-white">Panduan Perintah CLI Artisan Administrator</h3>
                            <p class="text-xs text-slate-400">Gunakan perintah berikut via terminal / SSH server untuk mengelola API Key rekanan dan pemeliharaan log.</p>
                        </div>
                    </div>
                    <button type="button" @click="showCliGuide = false" class="text-slate-400 hover:text-white font-bold">&times;</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60">
                        <div class="font-bold text-indigo-400 mb-1">1. Generate API Key Baru untuk Rekanan:</div>
                        <code class="block bg-slate-950 p-2 rounded-lg font-mono text-emerald-400 select-all text-[11px]">php artisan api:client-create "Nama Aplikasi Rekanan" [--expires-in-days=365]</code>
                    </div>
                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60">
                        <div class="font-bold text-indigo-400 mb-1">2. Lihat Daftar Seluruh Klien &amp; Kapan Terakhir Diakses:</div>
                        <code class="block bg-slate-950 p-2 rounded-lg font-mono text-emerald-400 select-all text-[11px]">php artisan api:client-list</code>
                    </div>
                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60">
                        <div class="font-bold text-rose-400 mb-1">3. Cabut / Nonaktifkan API Key Rekanan:</div>
                        <code class="block bg-slate-950 p-2 rounded-lg font-mono text-emerald-400 select-all text-[11px]">php artisan api:client-revoke {id_klien}</code>
                    </div>
                    <div class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60">
                        <div class="font-bold text-amber-400 mb-1">4. Bersihkan Log Usang via Terminal / Scheduler:</div>
                        <code class="block bg-slate-950 p-2 rounded-lg font-mono text-emerald-400 select-all text-[11px]">php artisan api:logs-prune --days=30</code>
                    </div>
                </div>
            </div>

            <!-- Summary Performance & Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Requests -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Request API</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalLogs, 0, ',', '.') }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">Sejak sistem aktif</div>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center font-bold text-xl shadow-2xs">
                        🌐
                    </div>
                </div>

                <!-- Requests Today -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Panggilan Hari Ini</div>
                        <div class="text-2xl font-black text-blue-600 mt-1">{{ number_format($totalToday, 0, ',', '.') }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ now()->isoFormat('D MMMM Y') }}</div>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center font-bold text-xl shadow-2xs">
                        ⚡
                    </div>
                </div>

                <!-- Success Rate -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tingkat Keberhasilan</div>
                        <div class="text-2xl font-black {{ $successRate >= 95 ? 'text-emerald-600' : ($successRate >= 80 ? 'text-amber-600' : 'text-rose-600') }} mt-1">
                            {{ $successRate }}%
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            <span class="text-emerald-600 font-bold">{{ $totalSuccess }} OK</span> • <span class="text-rose-600 font-bold">{{ $totalErrors }} Err</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center font-bold text-xl shadow-2xs">
                        🎯
                    </div>
                </div>

                <!-- Latency / Response Time -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rata-rata Latensi</div>
                        <div class="text-2xl font-black {{ $avgResponseTime <= 150 ? 'text-emerald-600' : ($avgResponseTime <= 400 ? 'text-amber-600' : 'text-rose-600') }} mt-1">
                            {{ $avgResponseTime }} <span class="text-sm font-semibold text-gray-500">ms</span>
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            @if($avgResponseTime <= 100)
                                ⚡ Sangat responsif (<100ms)
                            @elseif($avgResponseTime <= 300)
                                ✅ Kecepatan optimal
                            @else
                                ⏳ Perlu evaluasi beban query
                            @endif
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center font-bold text-xl shadow-2xs">
                        ⏱️
                    </div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-5">
                <form method="GET" action="{{ route('admin.api-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Cari Keyword</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Nama klien, endpoint, IP, pesan error..."
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs pl-8">
                            <span class="absolute left-2.5 top-2.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <!-- Status Code Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status HTTP</label>
                        <select name="status" class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                            <option value="">Semua Status</option>
                            <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>2xx Sukses</option>
                            <option value="client_error" {{ request('status') === 'client_error' ? 'selected' : '' }}>4xx Error Klien</option>
                            <option value="unauthorized" {{ request('status') === 'unauthorized' ? 'selected' : '' }}>401 Unauthorized</option>
                            <option value="forbidden" {{ request('status') === 'forbidden' ? 'selected' : '' }}>403 Forbidden</option>
                            <option value="throttle" {{ request('status') === 'throttle' ? 'selected' : '' }}>429 Rate Limited</option>
                            <option value="server_error" {{ request('status') === 'server_error' ? 'selected' : '' }}>5xx Server Error</option>
                        </select>
                    </div>

                    <!-- API Client Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Klien API</label>
                        <select name="client_id" class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                            <option value="">Semua Klien</option>
                            @foreach($apiClients as $client)
                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                            Terapkan
                        </button>
                        <a href="{{ route('admin.api-logs.index') }}"
                            class="inline-flex justify-center items-center px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition shadow-2xs" title="Reset Filter">
                            ✕
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider w-12">ID</th>
                                <th class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider w-40">Waktu</th>
                                <th class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider w-56">Klien API</th>
                                <th class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Method & Endpoint</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Status</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Latensi</th>
                                <th class="px-4 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider w-32">IP Address</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-28">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-xs">
                            @forelse($logs as $log)
                                <tr class="hover:bg-indigo-50/30 transition">
                                    <!-- ID -->
                                    <td class="px-4 py-3 font-mono text-gray-400 text-[11px]">#{{ $log->id }}</td>

                                    <!-- Waktu -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-gray-800">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>

                                    <!-- Klien API -->
                                    <td class="px-4 py-3">
                                        @if($log->client)
                                            <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                {{ $log->client->name }}
                                            </div>
                                            <div class="text-[10px] font-mono text-gray-500 truncate mt-0.5">
                                                {{ $log->client->description ?? 'Terotentikasi' }}
                                            </div>
                                        @elseif($log->client_name)
                                            <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                {{ $log->client_name }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 italic">API Key Dinonaktifkan/Diubah</div>
                                        @else
                                            <div class="font-bold text-rose-600 flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                Tanpa Klien
                                            </div>
                                            <div class="text-[10px] text-rose-500/80 font-mono">Unauthenticated</div>
                                        @endif
                                    </td>

                                    <!-- Method & Endpoint -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @php
                                                $methodBg = match(strtoupper($log->method)) {
                                                    'GET' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                    'POST' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                    'PUT', 'PATCH' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                    'DELETE' => 'bg-rose-100 text-rose-700 border-rose-200',
                                                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-[10px] font-mono font-black rounded-md border {{ $methodBg }}">
                                                {{ strtoupper($log->method) }}
                                            </span>
                                            <span class="font-mono text-gray-800 font-semibold text-[11px] truncate max-w-xs md:max-w-md" title="{{ $log->endpoint }}">
                                                {{ $log->endpoint }}
                                            </span>
                                        </div>
                                        @if(!empty($log->query_params))
                                            <div class="text-[10px] text-gray-500 font-mono mt-1 truncate max-w-xs md:max-w-md">
                                                Params: {{ json_encode($log->query_params) }}
                                            </div>
                                        @endif
                                        @if($log->error_message)
                                            <div class="text-[10px] text-rose-600 font-medium mt-1 truncate max-w-xs md:max-w-md">
                                                ⚠️ {{ $log->error_message }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Status HTTP -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $log->status_badge_class }}">
                                            {{ $log->status_code }}
                                        </span>
                                    </td>

                                    <!-- Response Time Latensi -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @php
                                            $time = (float) $log->response_time_ms;
                                            $timeBadge = match(true) {
                                                $time <= 100 => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                                $time <= 300 => 'text-blue-700 bg-blue-50 border-blue-200',
                                                $time <= 600 => 'text-amber-700 bg-amber-50 border-amber-200',
                                                default => 'text-rose-700 bg-rose-50 border-rose-200',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded font-mono text-[10px] font-bold border {{ $timeBadge }}">
                                            {{ $log->response_time_ms }} ms
                                        </span>
                                    </td>

                                    <!-- IP Address -->
                                    <td class="px-4 py-3 font-mono text-[11px] text-gray-600 whitespace-nowrap">
                                        {{ $log->ip_address }}
                                    </td>

                                    <!-- Action Detail Button -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <button type="button"
                                            @click="openDetail({{ $log->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-[11px] font-bold transition shadow-2xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            <span>Detail</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-16 text-center text-gray-400">
                                        <div class="text-4xl mb-3">🌐</div>
                                        <div class="font-bold text-base text-gray-700">Belum ada rekaman panggilan REST API.</div>
                                        <div class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                                            Setiap request yang masuk melalui endpoint <code>/api/v1/*</code> akan tercatat otomatis di dashboard monitoring ini.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- Detail Modal (Alpine.js) -->
        <div x-show="showDetailModal" style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div x-show="showDetailModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showDetailModal = false"
                    class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-xs transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div x-show="showDetailModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-200">

                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🌐</span>
                            <div>
                                <h3 class="font-bold text-base" x-text="activeLog ? ('Detail Panggilan API #' + activeLog.id) : 'Memuat data...'"></h3>
                                <p class="text-xs text-indigo-200 font-mono mt-0.5" x-text="activeLog ? (activeLog.method + ' ' + activeLog.endpoint) : ''"></p>
                            </div>
                        </div>
                        <button type="button" @click="showDetailModal = false" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto text-xs">
                        <!-- Loading State -->
                        <div x-show="loadingDetail" class="py-12 text-center text-gray-500">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent mb-2"></div>
                            <p class="font-semibold text-sm">Mengambil rincian log...</p>
                        </div>

                        <div x-show="!loadingDetail && activeLog" class="space-y-4">
                            <!-- Quick Meta Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Klien API:</span>
                                    <span class="font-bold text-gray-900" x-text="activeLog ? activeLog.client_name : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">HTTP Status:</span>
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold border mt-0.5"
                                          :class="activeLog ? activeLog.status_badge : ''"
                                          x-text="activeLog ? activeLog.status_code : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Latensi Waktu:</span>
                                    <span class="font-mono font-bold text-indigo-700" x-text="activeLog ? (activeLog.response_time_ms + ' ms') : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Waktu Panggilan:</span>
                                    <span class="font-medium text-gray-800" x-text="activeLog ? activeLog.created_at : '-'"></span>
                                </div>
                            </div>

                            <!-- Error Message Box (if any) -->
                            <template x-if="activeLog && activeLog.error_message">
                                <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-xl">
                                    <div class="font-bold flex items-center gap-1.5 text-xs text-rose-900 mb-1">
                                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Pesan Kesalahan (Error Payload)
                                    </div>
                                    <p class="font-mono text-[11px] whitespace-pre-wrap" x-text="activeLog.error_message"></p>
                                </div>
                            </template>

                            <!-- Query Parameters -->
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <div class="bg-gray-100 px-4 py-2.5 font-bold text-gray-800 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <span>🔍</span>
                                        <span>Filter / Query Parameters Terkirim</span>
                                    </span>
                                </div>
                                <div class="p-4 bg-gray-50/50">
                                    <template x-if="activeLog && activeLog.query_params && Object.keys(activeLog.query_params).length > 0">
                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <template x-for="(value, key) in activeLog.query_params" :key="key">
                                                    <div class="bg-white p-2.5 rounded-lg border border-gray-200">
                                                        <span class="font-mono font-bold text-indigo-600" x-text="key"></span>: 
                                                        <span class="font-mono text-gray-800" x-text="value"></span>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="pt-2">
                                                <div class="text-[10px] uppercase font-bold text-gray-400 mb-1">Raw JSON Query:</div>
                                                <pre class="p-3 bg-gray-900 text-emerald-400 rounded-lg font-mono text-[11px] overflow-x-auto" x-text="JSON.stringify(activeLog.query_params, null, 2)"></pre>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!activeLog || !activeLog.query_params || Object.keys(activeLog.query_params).length === 0">
                                        <p class="text-gray-400 italic">Tidak ada parameter query (request memanggil endpoint dasar tanpa filter query string).</p>
                                    </template>
                                </div>
                            </div>

                            <!-- Network & Client Info -->
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <div class="bg-gray-100 px-4 py-2.5 font-bold text-gray-800">
                                    <span>🌐 Informasi Jaringan & User Agent</span>
                                </div>
                                <div class="p-4 space-y-3 bg-white">
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Alamat IP:</span>
                                        <span class="font-mono text-gray-800 text-[11px]" x-text="activeLog ? activeLog.ip_address : '-'"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">User-Agent Header:</span>
                                        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-200 font-mono text-[11px] text-gray-700 break-all"
                                             x-text="activeLog ? activeLog.user_agent : '-'"></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="showDetailModal = false"
                            class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-xl text-xs font-bold transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prune Confirmation Modal -->
        <div x-show="showPruneModal" style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="prune-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showPruneModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showPruneModal = false"
                    class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-xs transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showPruneModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200">
                    
                    <form method="POST" action="{{ route('admin.api-logs.prune') }}">
                        @csrf
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 font-bold text-2xl">
                                🗑️
                            </div>
                            <h3 class="font-bold text-lg text-gray-900">Bersihkan Riwayat Log API</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Tindakan ini akan menghapus permanen rekaman log akses API yang sudah kadaluwarsa guna menghemat ruang penyimpanan server.
                            </p>

                            <div class="mt-4">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Hapus log yang lebih lama dari:
                                </label>
                                <select name="days" class="w-full text-xs rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500">
                                    <option value="7">7 Hari yang lalu</option>
                                    <option value="14">14 Hari yang lalu</option>
                                    <option value="30" selected>30 Hari yang lalu (Disarankan)</option>
                                    <option value="60">60 Hari yang lalu</option>
                                    <option value="90">90 Hari yang lalu</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                            <button type="button" @click="showPruneModal = false"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                Ya, Bersihkan Log
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
