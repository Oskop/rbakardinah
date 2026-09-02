<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('RBA Submissions') }} - {{ $header->year }} ({{ $header->period->name }})
            </h2>
            <div class="flex items-center space-x-3" x-data="{ openPrintModal: false, printType: 'usulan', filterScope: 'all', selectedUnits: [], selectedOperators: [] }">
                <!-- Tombol Cetak Admin -->
                <button @click="openPrintModal = true" type="button"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded text-sm inline-flex items-center gap-1.5 shadow transition-all">
                    <span>🖨️ Cetak Rincian Usulan / RBA Final</span>
                </button>

                <a href="{{ route('admin.headers.pagu.index', $header) }}"
                    class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                    Set Pagu Global
                </a>
                <a href="{{ route('admin.headers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to List</a>

                <!-- Modal Konfigurasi Cetak Admin -->
                <div x-show="openPrintModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="openPrintModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="openPrintModal = false"></div>

                        <div x-show="openPrintModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">
                            
                            <form :action="printType === 'final' ? '{{ route('admin.headers.print-preview-final', $header->id) }}' : '{{ route('admin.headers.print-preview', $header->id) }}'" method="GET" target="_blank" @submit="openPrintModal = false">
                                <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-4 flex items-center justify-between text-white">
                                    <h3 class="text-base font-bold flex items-center gap-2">
                                        <span>🖨️ Konfigurasi Cetak RBA Administrator</span>
                                    </h3>
                                    <button type="button" @click="openPrintModal = false" class="text-gray-400 hover:text-white font-bold text-xl">&times;</button>
                                </div>

                                <div class="p-6 space-y-5">
                                    <!-- 1. Jenis Dokumen Laporan -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. Jenis Dokumen Laporan</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label :class="printType === 'usulan' ? 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-slate-50 text-gray-700 hover:border-emerald-300'" class="flex flex-col gap-1 p-3 rounded-xl border cursor-pointer transition-all">
                                                <div class="flex items-center gap-2">
                                                    <input type="radio" x-model="printType" value="usulan" class="text-emerald-600 focus:ring-emerald-500">
                                                    <span class="text-xs font-bold">Usulan Rincian Belanja</span>
                                                </div>
                                                <span class="text-[10px] text-gray-500 pl-5">Laporan usulan standar tanpa kolom Pagu Final.</span>
                                            </label>
                                            <label :class="printType === 'final' ? 'border-indigo-500 bg-indigo-50 text-indigo-900 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-slate-50 text-gray-700 hover:border-indigo-300'" class="flex flex-col gap-1 p-3 rounded-xl border cursor-pointer transition-all">
                                                <div class="flex items-center gap-2">
                                                    <input type="radio" x-model="printType" value="final" class="text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-xs font-bold text-indigo-900">Rincian Belanja & Pagu (RBA Final)</span>
                                                </div>
                                                <span class="text-[10px] text-gray-500 pl-5">Laporan RBA Final bersandingan dengan nominal Pagu.</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 2. Opsi Latar Belakang -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">2. Latar Belakang Sub-Unit</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 hover:border-emerald-500 cursor-pointer bg-slate-50 text-xs font-semibold text-gray-700">
                                                <input type="radio" name="include_background" value="1" checked class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Dengan Latar Belakang</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 hover:border-emerald-500 cursor-pointer bg-slate-50 text-xs font-semibold text-gray-700">
                                                <input type="radio" name="include_background" value="0" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Tanpa Latar Belakang</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 3. Opsi Filter Scope -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">3. Filter Scope Cetak</label>
                                        <div class="grid grid-cols-2 gap-2 mb-3">
                                            <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-800 cursor-pointer hover:bg-emerald-50">
                                                <input type="radio" x-model="filterScope" value="all" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Seluruh RSUD (Semua Unit & Op)</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-800 cursor-pointer hover:bg-emerald-50">
                                                <input type="radio" x-model="filterScope" value="units" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Filter Per Unit (Supervisor)</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-800 cursor-pointer hover:bg-emerald-50">
                                                <input type="radio" x-model="filterScope" value="operators" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Filter Per Operator Spesifik</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-800 cursor-pointer hover:bg-emerald-50">
                                                <input type="radio" x-model="filterScope" value="custom" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Kombinasi Unit + Operator</span>
                                            </label>
                                        </div>

                                        <!-- Filter Checklist Unit Kerja (Jika filterScope == 'units' atau 'custom') -->
                                        <div x-show="filterScope === 'units' || filterScope === 'custom'" x-transition class="bg-gray-50 border border-gray-200 rounded-xl p-3 mb-3 max-h-40 overflow-y-auto space-y-1.5">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 text-[11px] font-bold text-gray-500">
                                                <span>PILIH UNIT KERJA (SUPERVISOR):</span>
                                                <div class="space-x-2">
                                                    <button type="button" @click="selectedUnits = [{{ $units->pluck('id')->join(',') }}]" class="text-emerald-600 hover:underline">Pilih Semua</button>
                                                    <span>|</span>
                                                    <button type="button" @click="selectedUnits = []" class="text-red-600 hover:underline">Reset</button>
                                                </div>
                                            </div>
                                            @foreach($units as $unit)
                                                <label class="flex items-center gap-2 text-xs text-gray-700 py-1 px-1.5 hover:bg-white rounded cursor-pointer transition-colors">
                                                    <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" x-model="selectedUnits" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span><strong>{{ $unit->name }}</strong> <span class="text-gray-400">({{ $unit->code }})</span></span>
                                                </label>
                                            @endforeach
                                        </div>

                                        <!-- Filter Checklist Operator (Jika filterScope == 'operators' atau 'custom') -->
                                        <div x-show="filterScope === 'operators' || filterScope === 'custom'" x-transition class="bg-gray-50 border border-gray-200 rounded-xl p-3 max-h-40 overflow-y-auto space-y-1.5">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 text-[11px] font-bold text-gray-500">
                                                <span>PILIH OPERATOR SPESIFIK:</span>
                                                <div class="space-x-2">
                                                    <button type="button" @click="selectedOperators = [{{ $allOperators->pluck('id')->join(',') }}]" class="text-emerald-600 hover:underline">Pilih Semua</button>
                                                    <span>|</span>
                                                    <button type="button" @click="selectedOperators = []" class="text-red-600 hover:underline">Reset</button>
                                                </div>
                                            </div>
                                            @foreach($allOperators as $op)
                                                <label class="flex items-center gap-2 text-xs text-gray-700 py-1 px-1.5 hover:bg-white rounded cursor-pointer transition-colors">
                                                    <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}" x-model="selectedOperators" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span><strong>{{ $op->name }}</strong> <span class="text-gray-400">({{ $op->unit->name ?? 'Unit' }})</span></span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-2 border-t border-gray-100">
                                    <button type="button" @click="openPrintModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-200 rounded-xl transition-all">Batal</button>
                                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md transition-all flex items-center gap-1.5">
                                        <span>🌐 Buka Pratinjau Cetak</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
                    <div x-data="{ 
                        search: '',
                        formatIDR(val) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        },
                        get totals() {
                            let totalUsulan = {{ $totalUsulan }};
                            let totalPagu = {{ $totalPagu }};
                            return { usulan: totalUsulan, pagu: totalPagu };
                        }
                    }">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <h3 class="text-lg font-bold">Laporan Hierarki RBA</h3>
                                <div class="relative">
                                    <input x-model="search" type="text" placeholder="Cari kode atau uraian..." 
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-72 pl-8">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-8 bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm transition-all hover:shadow-md">
                                <div class="flex space-x-8">
                                    <div class="text-right">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Total Usulan Global</p>
                                        <p class="text-2xl font-black text-indigo-700 leading-none" x-text="formatIDR(totals.usulan)"></p>
                                    </div>
                                    <div class="text-right border-l border-gray-300 pl-8">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Total Pagu Global</p>
                                        <p class="text-2xl font-black text-green-700 leading-none" x-text="formatIDR(totals.pagu)"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Monitoring Penginputan Unit (Level Supervisor & Operator) -->
                        <div class="mb-6 bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm"
                            x-data="{
                                panelOpen: true,
                                searchUnit: '',
                                openUnits: {},
                                toggleUnit(id) {
                                    this.openUnits[id] = !this.openUnits[id];
                                },
                                isUnitOpen(id) {
                                    return !!this.openUnits[id];
                                },
                                toggleAllUnits(openState) {
                                    @foreach($unitMonitoring as $m)
                                        this.openUnits[{{ $m['submission_id'] }}] = openState;
                                    @endforeach
                                }
                            }"
                            x-init="
                                @foreach($unitMonitoring as $m)
                                    openUnits[{{ $m['submission_id'] }}] = false;
                                @endforeach
                            ">
                            <!-- Panel Header & Quick Actions -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                                <div class="flex items-center gap-3 cursor-pointer select-none" @click="panelOpen = !panelOpen" title="Klik untuk meminimalkan/membuka panel">
                                    <span class="p-2.5 bg-indigo-100 text-indigo-700 rounded-xl shadow-inner">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="font-black text-base text-gray-900 flex items-center gap-2">
                                            <span>Monitoring Penginputan Unit dan Progres RBA</span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="panelOpen ? 'rotate-180 text-indigo-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </h3>
                                        <p class="text-xs text-slate-500">Monitoring penginputan latar belakang, nominal usulan, total per supervisor, dan kelengkapan dokumen PDF</p>
                                    </div>
                                </div>

                                <!-- Filter & Toolbar -->
                                <div class="flex flex-wrap items-center gap-2" x-show="panelOpen">
                                    <div class="relative">
                                        <input type="text" x-model="searchUnit" placeholder="Cari unit / pengguna..." 
                                            class="text-xs border-slate-300 rounded-lg pl-8 pr-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 w-44 shadow-sm">
                                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>

                                    <button type="button" @click="toggleAllUnits(true)" 
                                        class="px-2.5 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        <span>Buka Semua</span>
                                    </button>
                                    <button type="button" @click="toggleAllUnits(false)" 
                                        class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 rounded-lg border border-slate-300 transition-colors inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        <span>Tutup Semua</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Summary Pills Bar -->
                            <div class="mt-3.5 mb-3 flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-gray-500 uppercase flex items-center">Status Unit:</span>
                                @foreach($header->submissions as $submission)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border
                                            {{ $submission->status_submission === 'Draft' ? 'bg-gray-100 text-gray-700 border-gray-200' : '' }}
                                            {{ $submission->status_submission === 'Pending Supervisor' ? 'bg-yellow-50 text-yellow-800 border-yellow-200' : '' }}
                                            {{ $submission->status_submission === 'Validated' ? 'bg-green-50 text-green-800 border-green-200' : '' }}
                                        ">
                                        {{ $submission->unit?->name ?? 'Unit' }}: {{ $submission->status_submission }}
                                    </span>
                                @endforeach
                            </div>

                            <!-- Unit Monitoring Cards (Supervisor & Operator Details) -->
                            <div x-show="panelOpen" x-transition class="space-y-3 mt-4">
                                @forelse($unitMonitoring as $m)
                                    <div class="border rounded-xl overflow-hidden bg-white shadow-sm border-slate-200 transition-all"
                                        x-show="!searchUnit || '{{ strtolower($m['unit']?->name ?? '') }} {{ strtolower($m['supervisors']->pluck('name')->join(' ')) }} {{ strtolower(collect($m['operators_monitoring'])->pluck('operator.name')->join(' ')) }}'.includes(searchUnit.toLowerCase())">
                                        <!-- Unit Header Bar -->
                                        <button type="button" @click="toggleUnit({{ $m['submission_id'] }})"
                                            class="w-full text-left p-3.5 flex flex-col lg:flex-row lg:items-center justify-between gap-3 hover:bg-slate-50 transition-colors focus:outline-none">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 font-black text-xs flex items-center justify-center flex-shrink-0 border border-indigo-100">
                                                    {{ strtoupper(substr($m['unit']?->name ?? 'U', 0, 2)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h4 class="text-sm font-bold text-gray-900">
                                                            {{ $m['unit']?->name ?? 'Unit Kerja' }}
                                                        </h4>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border
                                                            {{ $m['status_submission'] === 'Draft' ? 'bg-gray-100 text-gray-700 border-gray-200' : '' }}
                                                            {{ $m['status_submission'] === 'Pending Supervisor' ? 'bg-amber-50 text-amber-800 border-amber-200' : '' }}
                                                            {{ $m['status_submission'] === 'Validated' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : '' }}
                                                        ">
                                                            {{ $m['status_submission'] }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 flex items-center gap-2 mt-0.5">
                                                        <span>Supervisor:</span>
                                                        <span class="font-semibold text-slate-700">
                                                            {{ $m['supervisors']->isNotEmpty() ? $m['supervisors']->pluck('name')->join(', ') : 'Belum ditugaskan' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Unit Overview Metrics -->
                                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                                <!-- Total Usulan Unit -->
                                                <div class="text-right">
                                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Total Usulan Unit</div>
                                                    <div class="font-extrabold text-indigo-700 text-sm">
                                                        Rp {{ number_format($m['total_nominal'], 0, ',', '.') }}
                                                    </div>
                                                </div>

                                                <!-- Validasi Review -->
                                                <div class="text-right pl-3 border-l border-slate-200">
                                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Validasi Review</div>
                                                    <div class="font-semibold text-slate-700 text-xs flex items-center gap-1 justify-end">
                                                        <span class="text-emerald-600 font-bold">{{ $m['validated_details'] }}</span>/{{ $m['total_details'] }} Usulan
                                                        @if($m['rejected_details'] > 0)
                                                            <span class="text-rose-600 text-[10px]">({{ $m['rejected_details'] }} ditolak)</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Expand Chevron Button -->
                                                <div class="pl-2 border-l border-slate-200 flex items-center gap-1 text-slate-600">
                                                    <span class="text-[11px] font-semibold text-indigo-600">{{ $m['operators_count'] }} Operator</span>
                                                    <svg class="w-4 h-4 transition-transform duration-200" :class="isUnitOpen({{ $m['submission_id'] }}) ? 'rotate-180 text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </button>

                                        <!-- Operator Level Monitoring Table (Expanded) -->
                                        <div x-show="isUnitOpen({{ $m['submission_id'] }})" x-transition class="border-t border-slate-200 bg-slate-50/50 p-3.5">
                                            <div class="overflow-x-auto border border-slate-200 rounded-xl bg-white shadow-sm">
                                                <table class="w-full text-left text-xs border-collapse">
                                                    <thead class="bg-slate-100/80 text-slate-700 uppercase text-[10px] font-bold border-b border-slate-200">
                                                        <tr>
                                                            <th class="px-3 py-2.5">Operator</th>
                                                            <th class="px-3 py-2.5 text-center">Status Latar Belakang</th>
                                                            <th class="px-3 py-2.5 text-right">Nominal Usulan</th>
                                                            <th class="px-3 py-2.5 text-center">Dokumen KAK / RAK / RTP</th>
                                                            <th class="px-3 py-2.5 text-center">PDF Lampiran Usulan</th>
                                                            <th class="px-3 py-2.5 text-center">Status Kelengkapan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @forelse($m['operators_monitoring'] as $op)
                                                            <tr class="hover:bg-indigo-50/20 transition-colors">
                                                                <!-- Operator Identity -->
                                                                <td class="px-3 py-2.5 font-medium text-slate-900">
                                                                    <div class="flex items-center gap-2">
                                                                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                                                                            {{ strtoupper(substr($op['operator']->name, 0, 2)) }}
                                                                        </div>
                                                                        <div>
                                                                            <div class="font-bold text-gray-900 leading-tight">{{ $op['operator']->name }}</div>
                                                                            @if($op['operator']->nip)
                                                                                <div class="text-[10px] font-mono text-gray-400">{{ $op['operator']->nip }}</div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </td>

                                                                <!-- Latar Belakang Status -->
                                                                <td class="px-3 py-2.5 text-center">
                                                                    @if($op['has_background'])
                                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 border border-green-200" title="{{ $op['background_text'] }}">
                                                                            <span>✓</span>
                                                                            <span>Sudah Diisi</span>
                                                                        </span>
                                                                    @else
                                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                                            <span>⚠️</span>
                                                                            <span>Belum Diisi</span>
                                                                        </span>
                                                                    @endif
                                                                </td>

                                                                <!-- Nominal Usulan -->
                                                                <td class="px-3 py-2.5 text-right font-semibold text-slate-800">
                                                                    <div class="font-mono text-indigo-700">Rp {{ number_format($op['nominal_usulan'], 0, ',', '.') }}</div>
                                                                    <div class="text-[10px] text-gray-400 font-normal">{{ $op['item_count'] }} usulan</div>
                                                                </td>

                                                                <!-- Dokumen Pokok (KAK, RAK, RTP) -->
                                                                <td class="px-3 py-2.5 text-center">
                                                                    <div class="inline-flex items-center gap-1">
                                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $op['has_kak'] ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                                                            KAK
                                                                        </span>
                                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $op['has_rak'] ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                                                            RAK
                                                                        </span>
                                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $op['has_rtp'] ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                                                            RTP
                                                                        </span>
                                                                    </div>
                                                                    <div class="text-[10px] text-gray-400 mt-0.5 font-medium">{{ $op['mandatory_docs_count'] }}/3 Terunggah</div>
                                                                </td>

                                                                <!-- PDF Lampiran Usulan Belanja -->
                                                                <td class="px-3 py-2.5 text-center font-medium">
                                                                    @if($op['total_details_count'] > 0)
                                                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold {{ $op['details_with_pdf_count'] === $op['total_details_count'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                                            <span>{{ $op['details_with_pdf_count'] }}/{{ $op['total_details_count'] }} PDF</span>
                                                                        </span>
                                                                    @else
                                                                        <span class="text-[10px] text-gray-400 italic">Belum ada usulan</span>
                                                                    @endif
                                                                </td>

                                                                <!-- Status Kelengkapan Keseluruhan -->
                                                                <td class="px-3 py-2.5 text-center">
                                                                    @if($op['is_all_complete'])
                                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                                            <span>✓</span>
                                                                            <span>Lengkap</span>
                                                                        </span>
                                                                    @else
                                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                                            <span>⚠️</span>
                                                                            <span>Belum Lengkap</span>
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="px-4 py-3 text-center text-xs text-gray-400 italic">
                                                                    Belum ada operator aktif terdaftar pada unit kerja ini.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500 italic text-center py-4">Belum ada unit yang terdaftar pada RBA periode ini.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-sm">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-300">
                                        <th class="border-r border-gray-300 px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">KODE REKENING</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">URAIAN BELANJA</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-right text-[10px] font-black uppercase tracking-wider text-gray-600">USULAN (Rp)</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-right text-[10px] font-black uppercase tracking-wider text-gray-600">PAGU (Rp)</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">SUPERVISOR</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">OPERATOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $data)
                                        @php
                                            $isParent = $data['usulan'] > 0 || $data['pagu'] > 0;
                                            $hasDetails = $data['details']->count() > 0;
                                        @endphp
                                        <tr x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
                                            class="border-b border-gray-200 {{ $data['level'] <= 2 ? 'bg-gray-50/50 font-bold' : '' }} hover:bg-indigo-50/30 transition-colors">
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm whitespace-nowrap font-mono text-gray-600">
                                                {{ $data['code'] }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm"
                                                style="padding-left: {{ 1 + ($data['level'] - 1) * 1 }}rem">
                                                {{ strtoupper($data['name']) }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm text-right {{ $data['level'] == 1 ? 'text-indigo-700' : '' }}">
                                                {{ number_format($data['usulan'], 0, ',', '.') }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm text-right {{ $data['level'] == 1 ? 'text-green-700' : '' }}">
                                                {{ number_format($data['pagu'], 0, ',', '.') }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm"></td>
                                            <td class="px-4 py-2 text-sm"></td>
                                        </tr>

                                        @if($hasDetails)
                                            @foreach($data['details'] as $detail)
                                                <tr x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
                                                    class="border-b border-gray-100 bg-white hover:bg-blue-50/50 transition-colors">
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-gray-400 italic"></td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-gray-700"
                                                        style="padding-left: {{ 2 + ($data['level'] - 1) * 1 }}rem">
                                                        <span class="text-indigo-400 mr-1">↳</span> {{ $detail->description }} 
                                                        <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold uppercase">{{ $detail->submission?->unit?->name ?? '-' }}</span>
                                                    </td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-right font-medium text-gray-600">
                                                        {{ number_format($detail->nominal_request, 0, ',', '.') }}
                                                    </td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-right text-gray-300">-</td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-gray-600">
                                                        {{ $detail->validator->name ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-1.5 text-[11px] text-gray-600">
                                                        {{ $detail->creator->name ?? '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>