<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-gray-800 leading-tight flex items-center gap-2">
            <span>{{ __('Administrator Dashboard - SIPAKAR RBA') }}</span>
        </h2>
    </x-slot>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="py-8 bg-gray-50/50 min-h-[calc(100vh-4rem)]" x-data="{
        rbas: {{ json_encode($rbaData) }},
        selectedRba: null,
        viewMode: 'table',
        historyViewMode: 'list',
        searchRba: '',
        chartInstance: null,
        historyChartInstance: null,

        init() {
            if (this.rbas.length > 0) {
                this.selectedRba = this.rbas[0];
            }
            this.$watch('viewMode', (value) => {
                if (value === 'chart') {
                    this.$nextTick(() => this.renderChart());
                }
            });
            this.$watch('historyViewMode', (value) => {
                if (value === 'chart') {
                    this.$nextTick(() => this.renderHistoryBarChart());
                }
            });
            this.$watch('selectedRba', () => {
                if (this.viewMode === 'chart') {
                    this.$nextTick(() => this.renderChart());
                }
            });
        },

        filteredRbas() {
            if (!this.searchRba.trim()) return this.rbas;
            const q = this.searchRba.toLowerCase().trim();
            return this.rbas.filter(r => 
                ('rba ' + r.year).toLowerCase().includes(q) ||
                r.year.toString().includes(q) ||
                (r.period_name || '').toLowerCase().includes(q)
            );
        },

        selectRba(rba) {
            this.selectedRba = rba;
            this.$nextTick(() => {
                if (this.$refs.detailWorkspace) {
                    this.$refs.detailWorkspace.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        },

        formatIDR(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        getInitials(name) {
            if (!name) return 'OP';
            return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        },

        renderChart() {
            if (!this.selectedRba || !this.$refs.canvas) return;

            if (this.chartInstance) {
                this.chartInstance.destroy();
            }

            const ctx = this.$refs.canvas.getContext('2d');
            const operators = this.selectedRba.operators || [];

            const labels = operators.map(o => `${o.operator_name} (${o.unit_name})`);
            const data = operators.map(o => o.total_usulan);

            const palette = [
                '#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                '#06b6d4', '#ec4899', '#3b82f6', '#84cc16', '#14b8a6',
                '#f97316', '#a855f7', '#64748b', '#0284c7', '#d97706'
            ];

            const backgroundColors = labels.map((_, i) => palette[i % palette.length]);

            this.chartInstance = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Usulan Belanja Operator',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 14,
                                padding: 16,
                                font: {
                                    size: 12,
                                    weight: '600',
                                    family: 'sans-serif'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const val = context.raw || 0;
                                    const formatted = 'Rp ' + Number(val).toLocaleString('id-ID');
                                    
                                    const dataset = context.chart.data.datasets[0];
                                    const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                                    const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;

                                    return ` ${label}: ${formatted} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderHistoryBarChart() {
            if (!this.$refs.historyCanvas) return;
            if (this.historyChartInstance) {
                this.historyChartInstance.destroy();
            }

            // Reverse rbas to display chronologically from oldest to newest
            const chronologicalRbas = [...this.rbas].reverse();

            const labels = chronologicalRbas.map(r => `RBA ${r.year} ${r.period_name}`);
            const dataUsulan = chronologicalRbas.map(r => r.total_usulan_global);
            const dataPagu = chronologicalRbas.map(r => r.total_pagu_global);

            const ctx = this.$refs.historyCanvas.getContext('2d');
            this.historyChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Usulan Belanja',
                            data: dataUsulan,
                            backgroundColor: '#6366f1',
                            borderColor: '#4f46e5',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                        {
                            label: 'Total Pagu Global',
                            data: dataPagu,
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            borderWidth: 1.5,
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 14,
                                padding: 12,
                                font: {
                                    size: 11,
                                    weight: '700',
                                    family: 'sans-serif'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const val = context.raw || 0;
                                    return ` ${label}: Rp ${Number(val).toLocaleString('id-ID')}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000000) {
                                        return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                                    } else if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toFixed(0) + ' Jt';
                                    }
                                    return 'Rp ' + Number(value).toLocaleString('id-ID');
                                },
                                font: { size: 10, weight: '500' }
                            },
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 10, weight: '700' }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Banner Welcome Section -->
            <div
                class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 rounded-2xl shadow-lg p-6 text-gray-700 relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 opacity-10 pointer-events-none">
                    <svg class="w-64 h-64 text-gray-700" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xl font-extrabold tracking-tight text-white">Selamat Datang Administrator,
                        {{ Auth::user()->name }} 🛡️
                    </h3>
                    <p class="text-slate-200 text-sm mt-1 max-w-2xl leading-relaxed">
                        Kelola data master dan pantau seluruh rekapitulasi usulan RBA Rumah Sakit. Pilih dokumen RBA
                        untuk melihat rincian total
                        usulan belanja <strong>masing-masing Operator</strong> dalam format <strong>Tabel
                            Breakdown</strong>
                        maupun diagram <strong>Chart Pie</strong>.
                    </p>
                </div>
            </div>

            <!-- Quick Navigation Master Data Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Navigasi Cepat Master Data</span>
                    </h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    <!-- Button 1: Users -->
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center justify-center gap-2 h-11 px-3.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white font-bold text-xs rounded-xl border border-indigo-200/80 shadow-sm transition-all duration-200 group">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="truncate">Kelola User</span>
                    </a>

                    <!-- Button 2: Units -->
                    <a href="{{ route('admin.units.index') }}"
                        class="flex items-center justify-center gap-2 h-11 px-3.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white font-bold text-xs rounded-xl border border-blue-200/80 shadow-sm transition-all duration-200 group">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span class="truncate">Unit Kerja</span>
                    </a>

                    <!-- Button 3: Kode Rekening -->
                    <a href="{{ route('admin.account-codes.index') }}"
                        class="flex items-center justify-center gap-2 h-11 px-3.5 bg-purple-50 hover:bg-purple-600 text-purple-700 hover:text-white font-bold text-xs rounded-xl border border-purple-200/80 shadow-sm transition-all duration-200 group">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                        </svg>
                        <span class="truncate">Kode Rekening</span>
                    </a>

                    <!-- Button 4: Periode RBA -->
                    <a href="{{ route('admin.periods.index') }}"
                        class="flex items-center justify-center gap-2 h-11 px-3.5 bg-teal-50 hover:bg-teal-600 text-teal-700 hover:text-white font-bold text-xs rounded-xl border border-teal-200/80 shadow-sm transition-all duration-200 group">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="truncate">Periode RBA</span>
                    </a>

                    <!-- Button 5: Init RBA (Headers) -->
                    <a href="{{ route('admin.headers.index') }}"
                        class="flex items-center justify-center gap-2 h-11 px-3.5 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white font-bold text-xs rounded-xl border border-amber-200/80 shadow-sm transition-all duration-200 group">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span class="truncate">Init RBA (Header)</span>
                    </a>
                </div>
            </div>

            <!-- Master-Detail Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- Left Column: Daftar RBA Historis (4 Cols) -->
                <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-200/80 p-5 space-y-4">
                    <div
                        class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-3 border-b border-gray-100 gap-2">
                        <div>
                            <h3 class="font-extrabold text-base text-gray-800">Daftar RBA Historis</h3>
                            <p class="text-xs text-gray-500"
                                x-text="historyViewMode === 'list' ? 'Urutan RBA Terbaru ke Tertua' : 'Grafik Fluktuasi Usulan vs Pagu'">
                            </p>
                        </div>
                        <div class="flex items-center bg-gray-100 p-1 rounded-xl border border-gray-200/80">
                            <button @click="historyViewMode = 'list'"
                                :class="historyViewMode === 'list' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-800 font-medium'"
                                class="px-2.5 py-1 text-xs rounded-lg transition-all flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                                <span>Daftar</span>
                            </button>
                            <button @click="historyViewMode = 'chart'"
                                :class="historyViewMode === 'chart' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-800 font-medium'"
                                class="px-2.5 py-1 text-xs rounded-lg transition-all flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                                <span>Grafik</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mode 1: Mode Daftar -->
                    <div x-show="historyViewMode === 'list'" class="space-y-3">
                        <!-- Search Box for RBA History -->
                        <div class="relative mb-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" style="width: 16px; height: 16px;" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input x-model="searchRba" type="text" placeholder="Cari RBA (Tahun / Periode)..."
                                class="w-full text-xs border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-9 py-2 bg-gray-50/50 focus:bg-white transition-colors">
                        </div>

                        <template x-if="rbas.length === 0">
                            <div class="text-center py-8 text-gray-400 italic text-sm">
                                Belum ada RBA Header yang terdaftar.
                            </div>
                        </template>

                        <template x-if="rbas.length > 0 && filteredRbas().length === 0">
                            <div class="text-center py-8 text-gray-400 italic text-xs">
                                RBA tidak ditemukan dengan kata kunci "<span x-text="searchRba"></span>".
                            </div>
                        </template>

                        <!-- Scrollable container with fixed comfortable height -->
                        <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                            <template x-for="rba in filteredRbas()" :key="rba.id">
                                <div @click="selectRba(rba)" :class="selectedRba && selectedRba.id === rba.id 
                                        ? 'border-indigo-600 bg-indigo-50/40 shadow-sm ring-2 ring-indigo-500/20' 
                                        : 'border-gray-200 hover:border-indigo-300 hover:bg-gray-50/70'"
                                    class="cursor-pointer border rounded-xl p-4 transition-all duration-200 relative group">

                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span
                                                class="inline-flex items-center gap-1 text-sm font-black text-gray-900"
                                                x-text="'RBA ' + rba.year"></span>
                                            <span
                                                class="text-xs text-indigo-700 font-semibold bg-indigo-100/60 px-2 py-0.5 rounded-md ml-1"
                                                x-text="rba.period_name"></span>
                                        </div>
                                        <span :class="rba.status_global === 'Locked' 
                                            ? 'bg-red-50 text-red-700 border-red-200' 
                                            : 'bg-green-50 text-green-700 border-green-200'"
                                            class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                            x-text="rba.status_global">
                                        </span>
                                    </div>

                                    <div class="space-y-1 mt-3 pt-2 border-t border-gray-100 text-xs">
                                        <div class="flex justify-between items-center text-gray-600">
                                            <span>Total Usulan (Semua Operator):</span>
                                            <span class="font-extrabold text-indigo-700"
                                                x-text="formatIDR(rba.total_usulan_global)"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-gray-600">
                                            <span>Pagu Global:</span>
                                            <span class="font-extrabold text-green-700"
                                                x-text="formatIDR(rba.total_pagu_global)"></span>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-3 flex justify-between items-center text-[11px] text-gray-400 font-semibold group-hover:text-indigo-600">
                                        <span x-text="rba.operators.length + ' Operator Berkontribusi'"></span>
                                        <span class="inline-flex items-center gap-0.5">
                                            <span>Lihat Operator</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Mode 2: Mode Grafik -->
                    <div x-show="historyViewMode === 'chart'" class="space-y-3" style="display: none;">
                        <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-200/80 mb-2">
                            <p class="text-xs text-gray-600 leading-relaxed font-medium">
                                📊 <strong>Grafik Fluktuasi RBA:</strong> Membandingkan total usulan belanja dari
                                seluruh operator dengan total pagu global yang ditetapkan oleh Administrator untuk
                                setiap periode RBA (kronologis).
                            </p>
                        </div>
                        <div class="relative w-full h-[450px] bg-white p-3 rounded-xl border border-gray-100">
                            <canvas x-ref="historyCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Detail Workspace RBA (8 Cols) -->
                <div x-ref="detailWorkspace"
                    class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 space-y-6">

                    <template x-if="!selectedRba">
                        <div class="text-center py-16 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="font-medium text-sm">Pilih salah satu RBA di sebelah kiri untuk melihat informasi
                                detail per Operator.</p>
                        </div>
                    </template>

                    <template x-if="selectedRba">
                        <div class="space-y-6">

                            <!-- Header Info RBA Selected -->
                            <div
                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-200">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-black text-xl text-gray-900"
                                            x-text="'Detail RBA ' + selectedRba.year"></h3>
                                        <span
                                            class="text-xs font-bold text-indigo-700 bg-indigo-100 px-2.5 py-0.5 rounded-md"
                                            x-text="selectedRba.period_name"></span>
                                        <span :class="selectedRba.status_global === 'Locked' 
                                            ? 'bg-red-50 text-red-700 border-red-200' 
                                            : 'bg-green-50 text-green-700 border-green-200'"
                                            class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                            x-text="selectedRba.status_global">
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Rincian Usulan Belanja per Operator pada RBA <span
                                            x-text="selectedRba.year"></span> (<span
                                            x-text="selectedRba.period_name"></span>).
                                    </p>
                                </div>

                                <!-- View Toggle (Table vs Chart Pie) -->
                                <div class="flex items-center bg-gray-100 p-1 rounded-xl border border-gray-200">
                                    <button @click="viewMode = 'table'"
                                        :class="viewMode === 'table' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                        class="px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h18M3 14h18M9 4v16m6-16v16"></path>
                                        </svg>
                                        <span>Tabel</span>
                                    </button>
                                    <button @click="viewMode = 'chart'"
                                        :class="viewMode === 'chart' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                        class="px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                        </svg>
                                        <span>Chart Pie</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div
                                    class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-3 flex flex-col justify-between">
                                    <p class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Total
                                        Usulan Global</p>
                                    <p class="text-base sm:text-lg font-black text-indigo-900 mt-1 whitespace-nowrap"
                                        x-text="formatIDR(selectedRba.total_usulan_global)"></p>
                                </div>
                                <div
                                    class="bg-green-50/60 border border-green-100 rounded-xl p-3 flex flex-col justify-between">
                                    <p class="text-[11px] font-bold text-green-700 uppercase tracking-wider">Total Pagu
                                        Global</p>
                                    <p class="text-base sm:text-lg font-black text-green-900 mt-1 whitespace-nowrap"
                                        x-text="formatIDR(selectedRba.total_pagu_global)"></p>
                                </div>
                                <div
                                    class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex flex-col justify-between">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Jumlah
                                        Operator Berkontribusi</p>
                                    <p class="text-base sm:text-lg font-black text-gray-800 mt-1 whitespace-nowrap"
                                        x-text="selectedRba.operators.length + ' Operator'"></p>
                                </div>
                            </div>

                            <!-- Mode 1: Table View per Operator -->
                            <div x-show="viewMode === 'table'"
                                class="overflow-x-auto border border-gray-200 rounded-xl">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead
                                        class="bg-gray-50 text-gray-600 font-bold uppercase text-[11px] tracking-wider">
                                        <tr>
                                            <th class="px-4 py-3 text-left">No</th>
                                            <th class="px-4 py-3 text-left">Nama Operator</th>
                                            <th class="px-4 py-3 text-left">Unit asal</th>
                                            <th class="px-4 py-3 text-right">Total Usulan Belanja</th>
                                            <th class="px-4 py-3 text-right">Pagu Terkait</th>
                                            <th class="px-4 py-3 text-center">Proporsi Usulan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <template x-for="(op, index) in selectedRba.operators" :key="op.operator_id">
                                            <tr class="hover:bg-gray-50/80 transition-colors">
                                                <td class="px-4 py-3 text-gray-400 font-mono" x-text="index + 1"></td>
                                                <td class="px-4 py-3 font-semibold text-gray-900">
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center border border-indigo-200"
                                                            x-text="getInitials(op.operator_name)"></div>
                                                        <div>
                                                            <span x-text="op.operator_name"></span>
                                                            <div class="text-[10px] text-gray-400 font-normal"
                                                                x-text="op.item_count + ' item rincian usulan'"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span
                                                        class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-semibold"
                                                        x-text="op.unit_name"></span>
                                                </td>
                                                <td class="px-4 py-3 text-right font-extrabold text-indigo-700"
                                                    x-text="formatIDR(op.total_usulan)"></td>
                                                <td class="px-4 py-3 text-right font-extrabold text-green-700"
                                                    x-text="op.total_pagu > 0 ? formatIDR(op.total_pagu) : '-'"></td>
                                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="w-16 bg-gray-200 rounded-full h-2 overflow-hidden">
                                                            <div class="bg-indigo-600 h-2 rounded-full"
                                                                :style="'width: ' + op.percentage_share + '%'"></div>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-700"
                                                            x-text="op.percentage_share + '%'"></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="selectedRba.operators.length === 0">
                                            <tr>
                                                <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum
                                                    ada usulan dari Operator pada RBA ini.</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-gray-50 font-bold border-t border-gray-200 text-xs">
                                        <tr>
                                            <td colspan="3"
                                                class="px-4 py-3 text-right uppercase tracking-wider text-gray-600">
                                                Total Akumulasi:</td>
                                            <td class="px-4 py-3 text-right font-black text-indigo-800 text-sm"
                                                x-text="formatIDR(selectedRba.total_usulan_global)"></td>
                                            <td class="px-4 py-3 text-right font-black text-green-800 text-sm"
                                                x-text="formatIDR(selectedRba.total_pagu_global)"></td>
                                            <td class="px-4 py-3 text-center font-black text-gray-800">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Mode 2: Pie Chart View per Operator -->
                            <div x-show="viewMode === 'chart'" class="space-y-4" style="display: none;">
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 relative">
                                    <h4 class="text-sm font-bold text-gray-700 text-center mb-4">Persentase & Proporsi
                                        Total Usulan Belanja per Operator</h4>
                                    <div class="relative h-80 sm:h-96 w-full flex items-center justify-center">
                                        <canvas x-ref="canvas"></canvas>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </template>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>