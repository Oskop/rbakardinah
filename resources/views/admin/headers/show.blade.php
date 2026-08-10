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

                        <div class="mb-4 flex flex-wrap gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase flex items-center">Status Unit:</span>
                            @foreach($header->submissions as $submission)
                                <span class="px-2 py-1 rounded-full text-[10px] font-bold border
                                        {{ $submission->status_submission === 'Draft' ? 'bg-gray-50 text-gray-600 border-gray-200' : '' }}
                                        {{ $submission->status_submission === 'Pending Supervisor' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : '' }}
                                        {{ $submission->status_submission === 'Validated' ? 'bg-green-50 text-green-700 border-green-200' : '' }}
                                    ">
                                    {{ $submission->unit->name }}: {{ $submission->status_submission }}
                                </span>
                            @endforeach
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
                                                        <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold uppercase">{{ $detail->submission->unit->name }}</span>
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