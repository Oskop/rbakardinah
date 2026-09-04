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

                                // Modal Latar Belakang
                                bgModalOpen: false,
                                modalOperatorName: '',
                                modalOperatorNip: '',
                                modalUnitName: '',
                                modalBackgroundText: '',
                                showBackground(opName, opNip, unitName, text) {
                                    this.modalOperatorName = opName;
                                    this.modalOperatorNip = opNip || '-';
                                    this.modalUnitName = unitName;
                                    this.modalBackgroundText = text || 'Belum ada isi latar belakang.';
                                    this.bgModalOpen = true;
                                },

                                // Modal Dokumen Pokok (KAK / RAK / RTP)
                                docModalOpen: false,
                                activeDocType: 'KAK',
                                modalDocsData: {},
                                showDocuments(opName, opNip, unitName, docsData, defaultType = 'KAK') {
                                    this.modalOperatorName = opName;
                                    this.modalOperatorNip = opNip || '-';
                                    this.modalUnitName = unitName;
                                    this.modalDocsData = docsData || {};
                                    this.activeDocType = defaultType;
                                    this.docModalOpen = true;
                                },

                                // Modal PDF Lampiran Usulan Belanja
                                proposalModalOpen: false,
                                proposalSearch: '',
                                modalProposalDetails: [],
                                showProposalPdfs(opName, opNip, unitName, detailsList) {
                                    this.modalOperatorName = opName;
                                    this.modalOperatorNip = opNip || '-';
                                    this.modalUnitName = unitName;
                                    this.modalProposalDetails = detailsList || [];
                                    this.proposalSearch = '';
                                    this.proposalModalOpen = true;
                                },

                                get filteredProposalDetails() {
                                    if (!this.proposalSearch) {
                                        return this.modalProposalDetails;
                                    }
                                    const q = this.proposalSearch.toLowerCase();
                                    return this.modalProposalDetails.filter(d => 
                                        (d.account_code && d.account_code.toLowerCase().includes(q)) ||
                                        (d.account_name && d.account_name.toLowerCase().includes(q)) ||
                                        (d.description && d.description.toLowerCase().includes(q))
                                    );
                                },

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

                                                                <!-- Latar Belakang Status (Clickable to View Content) -->
                                                                <td class="px-3 py-2.5 text-center">
                                                                    @if($op['has_background'])
                                                                        <button type="button" 
                                                                            @click="showBackground('{{ addslashes($op['operator']->name) }}', '{{ addslashes($op['operator']->nip ?? '') }}', '{{ addslashes($m['unit']?->name ?? 'Unit') }}', {{ json_encode($op['background_text']) }})"
                                                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300 hover:shadow-xs transition-all cursor-pointer group"
                                                                            title="Klik untuk melihat isi latar belakang">
                                                                            <span class="text-emerald-600">✓</span>
                                                                            <span>Sudah Diisi</span>
                                                                            <span class="text-[10px] opacity-70 group-hover:opacity-100 ml-0.5">👁️</span>
                                                                        </button>
                                                                    @else
                                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
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
                                                                        <button type="button" 
                                                                            @click="showDocuments('{{ addslashes($op['operator']->name) }}', '{{ addslashes($op['operator']->nip ?? '') }}', '{{ addslashes($m['unit']?->name ?? 'Unit') }}', {{ json_encode($op['documents_data'], JSON_UNESCAPED_SLASHES) }}, 'KAK')"
                                                                            class="px-1.5 py-0.5 rounded text-[9px] font-bold transition-all cursor-pointer hover:shadow-xs {{ $op['has_kak'] ? 'bg-green-100 text-green-800 border border-green-300 hover:bg-green-200' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}"
                                                                            title="Klik untuk melihat dokumen KAK ({{ $op['documents_data']['KAK']['versions_count'] }} versi)">
                                                                            KAK @if($op['has_kak'])<span class="text-[8px]">👁️</span>@endif
                                                                        </button>
                                                                        <button type="button" 
                                                                            @click="showDocuments('{{ addslashes($op['operator']->name) }}', '{{ addslashes($op['operator']->nip ?? '') }}', '{{ addslashes($m['unit']?->name ?? 'Unit') }}', {{ json_encode($op['documents_data'], JSON_UNESCAPED_SLASHES) }}, 'RAK')"
                                                                            class="px-1.5 py-0.5 rounded text-[9px] font-bold transition-all cursor-pointer hover:shadow-xs {{ $op['has_rak'] ? 'bg-green-100 text-green-800 border border-green-300 hover:bg-green-200' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}"
                                                                            title="Klik untuk melihat dokumen RAK ({{ $op['documents_data']['RAK']['versions_count'] }} versi)">
                                                                            RAK @if($op['has_rak'])<span class="text-[8px]">👁️</span>@endif
                                                                        </button>
                                                                        <button type="button" 
                                                                            @click="showDocuments('{{ addslashes($op['operator']->name) }}', '{{ addslashes($op['operator']->nip ?? '') }}', '{{ addslashes($m['unit']?->name ?? 'Unit') }}', {{ json_encode($op['documents_data'], JSON_UNESCAPED_SLASHES) }}, 'RTP')"
                                                                            class="px-1.5 py-0.5 rounded text-[9px] font-bold transition-all cursor-pointer hover:shadow-xs {{ $op['has_rtp'] ? 'bg-green-100 text-green-800 border border-green-300 hover:bg-green-200' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}"
                                                                            title="Klik untuk melihat dokumen RTP ({{ $op['documents_data']['RTP']['versions_count'] }} versi)">
                                                                            RTP @if($op['has_rtp'])<span class="text-[8px]">👁️</span>@endif
                                                                        </button>
                                                                    </div>
                                                                    <div class="text-[10px] text-gray-400 mt-0.5 font-medium">{{ $op['mandatory_docs_count'] }}/3 Terunggah</div>
                                                                </td>

                                                                <!-- PDF Lampiran Usulan Belanja -->
                                                                <td class="px-3 py-2.5 text-center font-medium">
                                                                    @if($op['total_details_count'] > 0)
                                                                        <button type="button"
                                                                            @click="showProposalPdfs('{{ addslashes($op['operator']->name) }}', '{{ addslashes($op['operator']->nip ?? '') }}', '{{ addslashes($m['unit']?->name ?? 'Unit') }}', {{ json_encode($op['proposal_details_data'], JSON_UNESCAPED_SLASHES) }})"
                                                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer hover:shadow-xs group {{ $op['details_with_pdf_count'] === $op['total_details_count'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100 hover:border-amber-300' }}"
                                                                            title="Klik untuk melihat daftar usulan dan riwayat versi PDF lampiran">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                                            <span>{{ $op['details_with_pdf_count'] }}/{{ $op['total_details_count'] }} PDF</span>
                                                                            <span class="text-[10px] opacity-70 group-hover:opacity-100">👁️</span>
                                                                        </button>
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

                            <!-- Modal Detail Latar Belakang Operator -->
                            <div x-show="bgModalOpen" 
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="bgModalOpen = false"
                                class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <!-- Backdrop -->
                                    <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="bgModalOpen = false"></div>

                                    <!-- Modal Container -->
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                    <div x-show="bgModalOpen" 
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                                        
                                        <!-- Modal Header -->
                                        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-4 flex items-center justify-between text-white">
                                            <div class="flex items-center gap-2.5">
                                                <span class="p-1.5 bg-white/10 rounded-lg text-lg">📄</span>
                                                <div>
                                                    <h3 class="text-sm font-bold text-white leading-tight">Latar Belakang Usulan RBA</h3>
                                                    <p class="text-[11px] text-slate-300 mt-0.5">
                                                        <span x-text="modalUnitName"></span> &bull; Operator: <span class="font-semibold text-white" x-text="modalOperatorName"></span> (<span x-text="modalOperatorNip"></span>)
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="bgModalOpen = false" 
                                                class="text-slate-400 hover:text-white rounded-lg p-1 transition-colors text-xl font-bold leading-none">&times;</button>
                                        </div>

                                        <!-- Modal Body (Background Text) -->
                                        <div class="p-6 space-y-4">
                                            <div class="flex items-center justify-between text-xs text-slate-500 pb-2 border-b border-slate-100">
                                                <span class="font-bold uppercase tracking-wider text-[10px] text-indigo-700">Narasi Latar Belakang Usulan</span>
                                                <span class="text-[11px] italic text-slate-400">Diinput oleh Operator</span>
                                            </div>
                                            
                                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 max-h-96 overflow-y-auto shadow-inner text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line font-sans"
                                                x-text="modalBackgroundText">
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="bg-slate-100/80 px-6 py-3 border-t border-slate-200 flex justify-end">
                                            <button type="button" @click="bgModalOpen = false"
                                                class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-sm hover:shadow transition-all">
                                                Tutup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Detail Dokumen Pokok (KAK, RAK, RTP) dengan Versioning -->
                            <div x-show="docModalOpen" 
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="docModalOpen = false"
                                class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <!-- Backdrop -->
                                    <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="docModalOpen = false"></div>

                                    <!-- Modal Container -->
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                    <div x-show="docModalOpen" 
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                                        
                                        <!-- Modal Header -->
                                        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-4 flex items-center justify-between text-white">
                                            <div class="flex items-center gap-2.5">
                                                <span class="p-1.5 bg-white/10 rounded-lg text-lg">📑</span>
                                                <div>
                                                    <h3 class="text-sm font-bold text-white leading-tight">Dokumen Pokok RBA (KAK / RAK / RTP)</h3>
                                                    <p class="text-[11px] text-slate-300 mt-0.5">
                                                        <span x-text="modalUnitName"></span> &bull; Operator: <span class="font-semibold text-white" x-text="modalOperatorName"></span> (<span x-text="modalOperatorNip"></span>)
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="docModalOpen = false" 
                                                class="text-slate-400 hover:text-white rounded-lg p-1 transition-colors text-xl font-bold leading-none">&times;</button>
                                        </div>

                                        <!-- Tabs Nav: KAK, RAK, RTP -->
                                        <div class="bg-slate-100 px-6 pt-3 flex border-b border-slate-200 gap-2 overflow-x-auto">
                                            <template x-for="dType in ['KAK', 'RAK', 'RTP']" :key="dType">
                                                <button type="button" 
                                                    @click="activeDocType = dType"
                                                    class="px-3.5 py-2 text-xs font-bold rounded-t-xl transition-all border-t border-x flex items-center gap-1.5 whitespace-nowrap"
                                                    :class="activeDocType === dType 
                                                        ? 'bg-white text-indigo-700 border-slate-200 -mb-px shadow-xs' 
                                                        : 'bg-transparent text-slate-500 border-transparent hover:text-slate-800 hover:bg-slate-200/50'">
                                                    <span x-text="dType === 'KAK' ? 'Kerangka Acuan Kerja (KAK)' : (dType === 'RAK' ? 'Rencana Anggaran Kas (RAK)' : 'Rencana Tindak Pengendalian (RTP)')"></span>
                                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                                                        :class="(modalDocsData[dType]?.versions_count || 0) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                                                        x-text="(modalDocsData[dType]?.versions_count || 0) + ' versi'">
                                                    </span>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Modal Body (Version List) -->
                                        <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                                            <template x-if="modalDocsData[activeDocType]?.versions && modalDocsData[activeDocType].versions.length > 0">
                                                <div class="space-y-3">
                                                    <div class="text-xs text-slate-500 font-medium">
                                                        Riwayat revisi dokumen <strong class="text-indigo-700 font-bold" x-text="activeDocType"></strong> yang telah diunggah:
                                                    </div>
                                                    <template x-for="(ver, index) in modalDocsData[activeDocType].versions" :key="ver.version_number">
                                                        <div class="p-4 rounded-xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                                            :class="index === 0 ? 'bg-indigo-50/50 border-indigo-200 shadow-xs' : 'bg-slate-50 border-slate-200'">
                                                            <div>
                                                                <div class="flex items-center gap-2">
                                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                                                                        :class="index === 0 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-200 text-slate-700'">
                                                                        Versi <span x-text="ver.version_number"></span>
                                                                        <template x-if="index === 0">
                                                                            <span> (Terbaru)</span>
                                                                        </template>
                                                                    </span>
                                                                    <span class="text-xs text-slate-500" x-text="ver.created_at"></span>
                                                                </div>
                                                                <div class="text-xs text-slate-600 mt-1.5 flex items-center gap-1">
                                                                    <span>Diunggah oleh:</span>
                                                                    <strong class="text-slate-800 font-semibold" x-text="ver.uploaded_by"></strong>
                                                                </div>
                                                            </div>

                                                            <a :href="ver.file_url" target="_blank"
                                                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-sm flex-shrink-0"
                                                                :class="index === 0 ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-700 hover:bg-slate-800'">
                                                                <span>🌐 Buka PDF</span>
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                            </a>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            <template x-if="!modalDocsData[activeDocType]?.versions || modalDocsData[activeDocType].versions.length === 0">
                                                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                                                    <span class="text-3xl block mb-2 opacity-50">📂</span>
                                                    <h4 class="text-sm font-bold text-slate-700">Belum Ada Dokumen</h4>
                                                    <p class="text-xs text-slate-400 mt-1">Dokumen <span class="font-bold uppercase" x-text="activeDocType"></span> belum diunggah oleh operator untuk usulan RBA ini.</p>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="bg-slate-100/80 px-6 py-3 border-t border-slate-200 flex justify-end">
                                            <button type="button" @click="docModalOpen = false"
                                                class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-sm hover:shadow transition-all">
                                                Tutup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Daftar PDF Lampiran Usulan Belanja dengan Versioning -->
                            <div x-show="proposalModalOpen" 
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="proposalModalOpen = false"
                                class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <!-- Backdrop -->
                                    <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="proposalModalOpen = false"></div>

                                    <!-- Modal Container -->
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                    <div x-show="proposalModalOpen" 
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                                        
                                        <!-- Modal Header -->
                                        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-4 flex items-center justify-between text-white">
                                            <div class="flex items-center gap-2.5">
                                                <span class="p-1.5 bg-white/10 rounded-lg text-lg">📑</span>
                                                <div>
                                                    <h3 class="text-sm font-bold text-white leading-tight">PDF Lampiran Usulan Belanja</h3>
                                                    <p class="text-[11px] text-slate-300 mt-0.5">
                                                        <span x-text="modalUnitName"></span> &bull; Operator: <span class="font-semibold text-white" x-text="modalOperatorName"></span> (<span x-text="modalOperatorNip"></span>)
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="proposalModalOpen = false" 
                                                class="text-slate-400 hover:text-white rounded-lg p-1 transition-colors text-xl font-bold leading-none">&times;</button>
                                        </div>

                                        <!-- Subheader Filter & Search -->
                                        <div class="bg-slate-100 px-6 py-3 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="text-xs text-slate-600 font-medium">
                                                Menampilkan <strong class="text-indigo-700" x-text="filteredProposalDetails.length"></strong> dari <span x-text="modalProposalDetails.length"></span> rincian usulan belanja
                                            </div>
                                            <div class="relative">
                                                <input type="text" x-model="proposalSearch" placeholder="Cari kode rekening / uraian..." 
                                                    class="text-xs border-slate-300 rounded-lg pl-8 pr-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 w-60 shadow-xs bg-white">
                                                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Modal Body (Proposal Details with Attachments) -->
                                        <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                                            <template x-for="item in filteredProposalDetails" :key="item.id">
                                                <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs space-y-3">
                                                    <!-- Item Header -->
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200" x-text="item.account_code"></span>
                                                            <h5 class="text-xs font-bold text-gray-900" x-text="item.account_name"></h5>
                                                        </div>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border self-start sm:self-auto"
                                                            :class="item.status_class" x-text="item.status_label"></span>
                                                    </div>

                                                    <!-- Description & Nominal -->
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                                        <div class="text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-100 flex-1">
                                                            <div class="font-semibold text-slate-800" x-text="item.description"></div>
                                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                                Vol: <span class="font-semibold text-slate-700" x-text="item.volume"></span> <span x-text="item.satuan"></span> &times; Rp <span x-text="Number(item.harga_satuan).toLocaleString('id-ID')"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right sm:pl-4">
                                                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nominal Usulan</div>
                                                            <div class="text-sm font-extrabold text-indigo-700 font-mono">
                                                                Rp <span x-text="Number(item.nominal_request).toLocaleString('id-ID')"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Versioning PDF Attachments List -->
                                                    <div class="pt-2 border-t border-slate-100">
                                                        <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2 flex items-center justify-between">
                                                            <span>Riwayat Versi PDF Lampiran (<span x-text="item.attachments_count"></span> versi):</span>
                                                        </div>

                                                        <template x-if="item.attachments && item.attachments.length > 0">
                                                            <div class="space-y-2">
                                                                <template x-for="(att, aIdx) in item.attachments" :key="att.version_number">
                                                                    <div class="flex items-center justify-between p-2.5 rounded-lg border text-xs"
                                                                        :class="aIdx === 0 ? 'bg-indigo-50/40 border-indigo-200' : 'bg-slate-50 border-slate-200'">
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                                                                :class="aIdx === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700'">
                                                                                V<span x-text="att.version_number"></span>
                                                                                <template x-if="aIdx === 0">
                                                                                    <span> (Terbaru)</span>
                                                                                </template>
                                                                            </span>
                                                                            <span class="text-slate-500 text-[11px]" x-text="att.created_at"></span>
                                                                            <span class="text-slate-400 hidden sm:inline">&bull;</span>
                                                                            <span class="text-slate-600 text-[11px] hidden sm:inline">Oleh: <strong x-text="att.uploaded_by"></strong></span>
                                                                        </div>
                                                                        <a :href="att.file_url" target="_blank"
                                                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold text-white transition-all shadow-2xs"
                                                                            :class="aIdx === 0 ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-700 hover:bg-slate-800'">
                                                                            <span>🌐 Buka PDF</span>
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                                        </a>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>

                                                        <template x-if="!item.attachments || item.attachments.length === 0">
                                                            <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                                                                <span>⚠️</span>
                                                                <span>Belum ada file PDF yang dilampirkan oleh operator untuk rincian belanja ini.</span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="filteredProposalDetails.length === 0">
                                                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                                                    <span class="text-3xl block mb-2 opacity-50">🔍</span>
                                                    <h4 class="text-sm font-bold text-slate-700">Tidak Ada Rincian Usulan Ditemukan</h4>
                                                    <p class="text-xs text-slate-400 mt-1" x-text="proposalSearch ? 'Tidak ada usulan belanja yang cocok dengan kata kunci pencarian.' : 'Operator ini belum menginput rincian belanja pada periode RBA ini.'"></p>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="bg-slate-100/80 px-6 py-3 border-t border-slate-200 flex justify-end">
                                            <button type="button" @click="proposalModalOpen = false"
                                                class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-sm hover:shadow transition-all">
                                                Tutup
                                            </button>
                                        </div>
                                    </div>
                                </div>
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