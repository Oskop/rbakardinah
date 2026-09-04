<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-800 leading-tight flex items-center gap-2.5">
                    <span class="p-2 bg-indigo-100 text-indigo-700 rounded-xl">🖨️</span>
                    <span>{{ __('Pusat Laporan & Cetak RBA') }}</span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Cetak dokumen resmi RBA (Usulan Belanja & RBA Final dengan Pagu) terintegrasi untuk seluruh tingkatan pengguna.
                </p>
            </div>
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                    @if($role === 'Administrator') bg-purple-100 text-purple-800 border border-purple-200
                    @elseif($role === 'Supervisor') bg-blue-100 text-blue-800 border border-blue-200
                    @else bg-emerald-100 text-emerald-800 border border-emerald-200 @endif">
                    <span class="w-2 h-2 rounded-full @if($role === 'Administrator') bg-purple-500 @elseif($role === 'Supervisor') bg-blue-500 @else bg-emerald-500 @endif"></span>
                    {{ $role }}: {{ $user->name }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-[calc(100vh-140px)]"
        x-data="{
            role: '{{ $role }}',
            selectedId: '{{ $selectedPeriodId ?? '' }}',
            printType: 'usulan',
            includeBackground: '1',
            filterScope: 'all',
            operatorScope: 'all',
            selectedUnits: [],
            selectedOperators: [],

            getActionUrl() {
                if (!this.selectedId) return '#';
                if (this.role === 'Administrator') {
                    return this.printType === 'final'
                        ? '{{ url('/admin/headers') }}/' + this.selectedId + '/print-preview-final'
                        : '{{ url('/admin/headers') }}/' + this.selectedId + '/print-preview';
                } else if (this.role === 'Supervisor') {
                    return this.printType === 'final'
                        ? '{{ url('/supervisor/submissions') }}/' + this.selectedId + '/print-preview-final'
                        : '{{ url('/supervisor/submissions') }}/' + this.selectedId + '/print-preview';
                } else if (this.role === 'Operator') {
                    return this.printType === 'final'
                        ? '{{ url('/operator/submissions') }}/' + this.selectedId + '/print-preview-final'
                        : '{{ url('/operator/submissions') }}/' + this.selectedId + '/print-preview';
                }
                return '#';
            },

            getDetailUrl() {
                if (!this.selectedId) return '#';
                if (this.role === 'Administrator') {
                    return '{{ url('/admin/headers') }}/' + this.selectedId;
                } else if (this.role === 'Supervisor') {
                    return '{{ url('/supervisor/submissions') }}/' + this.selectedId;
                } else if (this.role === 'Operator') {
                    return '{{ url('/operator/submissions') }}/' + this.selectedId;
                }
                return '#';
            }
        }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- KOLOM KIRI: Pemilihan Periode RBA & Informasi -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-600 text-white text-xs font-bold">1</span>
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Pilih Periode RBA</h3>
                            </div>
                            <span class="text-xs text-gray-400 font-medium">
                                @if($role === 'Administrator')
                                    {{ $headers->count() }} Periode Tersedia
                                @else
                                    {{ $submissions->count() }} Berkas Terdaftar
                                @endif
                            </span>
                        </div>

                        <div class="p-5 space-y-3 max-h-[480px] overflow-y-auto">
                            @if($role === 'Administrator')
                                @forelse($headers as $h)
                                    <label class="block p-4 rounded-xl border-2 transition-all cursor-pointer relative"
                                        :class="selectedId == '{{ $h->id }}' ? 'border-indigo-600 bg-indigo-50/40 shadow-sm' : 'border-gray-100 hover:border-gray-200 bg-white hover:bg-slate-50/50'">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-3">
                                                <input type="radio" name="header_selection" value="{{ $h->id }}" x-model="selectedId" class="text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                                <div>
                                                    <div class="font-bold text-sm text-gray-900 flex items-center gap-2">
                                                        <span>TA {{ $h->year }}</span>
                                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                                            {{ $h->period->name ?? 'RBA' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                                        <span>🏢 {{ $h->submissions->count() }} Unit Pengusul</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                @if($h->status_global === 'open')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        Aktif
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                                        Terkunci
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="text-center py-8 text-gray-400 text-xs">
                                        Belum ada periode RBA yang terdaftar di sistem.
                                    </div>
                                @endforelse
                            @else
                                @forelse($submissions as $s)
                                    <label class="block p-4 rounded-xl border-2 transition-all cursor-pointer relative"
                                        :class="selectedId == '{{ $s->id }}' ? 'border-indigo-600 bg-indigo-50/40 shadow-sm' : 'border-gray-100 hover:border-gray-200 bg-white hover:bg-slate-50/50'">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-3">
                                                <input type="radio" name="submission_selection" value="{{ $s->id }}" x-model="selectedId" class="text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                                <div>
                                                    <div class="font-bold text-sm text-gray-900 flex items-center gap-2">
                                                        <span>TA {{ $s->header->year ?? '' }}</span>
                                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                                            {{ $s->header->period->name ?? 'RBA' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <span>🏢 {{ $s->unit->name ?? 'Unit Kerja' }}</span>
                                                        <span class="mx-1">•</span>
                                                        <span>{{ $s->details->count() }} Usulan Belanja</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                @if($s->status_submission === 'validated')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        Divalidasi
                                                    </span>
                                                @elseif($s->status_submission === 'submitted')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                        Submitted
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                                        Draft
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="text-center py-8 text-gray-400 text-xs">
                                        Belum ada usulan RBA untuk unit kerja Anda.
                                    </div>
                                @endforelse
                            @endif
                        </div>

                        <!-- Shortcut Direct Detail Link -->
                        <div class="p-4 bg-slate-50 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-gray-500">Ingin tinjau rincian terlebih dahulu?</span>
                            <a :href="getDetailUrl()" :class="!selectedId ? 'pointer-events-none opacity-40' : ''"
                                class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 group transition">
                                <span>Buka RBA Periode Ini</span>
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Kotak Informasi & Tips Cetak -->
                    <div class="bg-gradient-to-br from-indigo-900 to-slate-900 text-white rounded-2xl p-5 shadow-sm space-y-3">
                        <div class="flex items-center gap-2 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Panduan Cetak Dokumen Resmi</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Laporan akan dibuka di tab baru peramban dengan tata letak cetak resmi (A4 Landscape) lengkap dengan kop surat RSUD Kardinah. Anda dapat langsung mencetak fisik (<kbd class="px-1.5 py-0.5 bg-slate-800 border border-slate-700 rounded text-[10px] font-mono">Ctrl + P</kbd>) atau menyimpannya sebagai berkas PDF melalui peramban.
                        </p>
                    </div>
                </div>

                <!-- KOLOM KANAN: Panel Konfigurasi Cetak & Form Submit -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-bold">2</span>
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Opsi Konfigurasi Cetak</h3>
                            </div>
                            <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Tersinkronisasi Otomatis
                            </span>
                        </div>

                        <!-- Form Pengiriman Cetak -->
                        <form :action="getActionUrl()" method="GET" target="_blank" class="p-6 space-y-6">

                            <!-- 1. Jenis Dokumen Laporan -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">
                                    A. Jenis Dokumen Laporan
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label :class="printType === 'usulan' ? 'border-emerald-500 bg-emerald-50/60 text-emerald-950 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-slate-50 text-gray-700 hover:border-emerald-300'"
                                        class="flex flex-col gap-1.5 p-4 rounded-xl border cursor-pointer transition-all">
                                        <div class="flex items-center gap-2.5">
                                            <input type="radio" x-model="printType" value="usulan" class="text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-xs font-bold">Usulan Rincian Belanja</span>
                                        </div>
                                        <span class="text-[11px] text-gray-500 pl-6 leading-relaxed">
                                            Format standar rincian belanja usulan tanpa menyertakan kolom Pagu Final.
                                        </span>
                                    </label>

                                    <label :class="printType === 'final' ? 'border-indigo-500 bg-indigo-50/60 text-indigo-950 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-slate-50 text-gray-700 hover:border-indigo-300'"
                                        class="flex flex-col gap-1.5 p-4 rounded-xl border cursor-pointer transition-all">
                                        <div class="flex items-center gap-2.5">
                                            <input type="radio" x-model="printType" value="final" class="text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-xs font-bold">Rincian Belanja & Pagu (RBA Final)</span>
                                        </div>
                                        <span class="text-[11px] text-gray-500 pl-6 leading-relaxed">
                                            Format RBA Final bersandingan dengan nominal pagu dan selisih anggaran.
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- 2. Opsi Latar Belakang -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">
                                    B. Lampiran Latar Belakang Sub-Unit
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 hover:border-emerald-500 cursor-pointer bg-slate-50 text-xs font-semibold text-gray-700 transition">
                                        <input type="radio" name="include_background" value="1" checked class="text-emerald-600 focus:ring-emerald-500">
                                        <span>📄 Dengan Latar Belakang</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 hover:border-emerald-500 cursor-pointer bg-slate-50 text-xs font-semibold text-gray-700 transition">
                                        <input type="radio" name="include_background" value="0" class="text-emerald-600 focus:ring-emerald-500">
                                        <span>📑 Tanpa Latar Belakang</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 3. Scope & Filter (Disesuaikan menurut Role) -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">
                                    C. Filter Cakupan Data (Scope)
                                </label>

                                @if($role === 'Administrator')
                                    <!-- Filter Scope Admin -->
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                            <label class="flex items-center gap-2 p-2.5 rounded-lg border text-xs font-semibold cursor-pointer transition"
                                                :class="filterScope === 'all' ? 'border-indigo-500 bg-indigo-50/50 text-indigo-900' : 'border-gray-200 text-gray-700 hover:bg-slate-50'">
                                                <input type="radio" x-model="filterScope" value="all" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Seluruh RSUD</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 rounded-lg border text-xs font-semibold cursor-pointer transition"
                                                :class="filterScope === 'units' ? 'border-indigo-500 bg-indigo-50/50 text-indigo-900' : 'border-gray-200 text-gray-700 hover:bg-slate-50'">
                                                <input type="radio" x-model="filterScope" value="units" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Filter Per Unit</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 rounded-lg border text-xs font-semibold cursor-pointer transition"
                                                :class="filterScope === 'operators' ? 'border-indigo-500 bg-indigo-50/50 text-indigo-900' : 'border-gray-200 text-gray-700 hover:bg-slate-50'">
                                                <input type="radio" x-model="filterScope" value="operators" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Filter Operator</span>
                                            </label>
                                        </div>

                                        <!-- Unit Checklist (Admin) -->
                                        <div x-show="filterScope === 'units'" x-transition class="bg-slate-50 border border-gray-200 rounded-xl p-4 space-y-2.5">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 text-xs font-bold text-gray-600">
                                                <span>PILIH UNIT KERJA:</span>
                                                <div class="space-x-2">
                                                    <button type="button" @click="selectedUnits = [{{ $units->pluck('id')->join(',') }}]" class="text-indigo-600 hover:underline">Pilih Semua</button>
                                                    <span>|</span>
                                                    <button type="button" @click="selectedUnits = []" class="text-red-600 hover:underline">Reset</button>
                                                </div>
                                            </div>
                                            <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                                @foreach($units as $u)
                                                    <label class="flex items-center gap-2.5 text-xs text-gray-700 py-1 px-2 hover:bg-white rounded-lg cursor-pointer transition">
                                                        <input type="checkbox" name="unit_ids[]" value="{{ $u->id }}" x-model="selectedUnits" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                        <span>{{ $u->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Operator Checklist (Admin) -->
                                        <div x-show="filterScope === 'operators'" x-transition class="bg-slate-50 border border-gray-200 rounded-xl p-4 space-y-2.5">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 text-xs font-bold text-gray-600">
                                                <span>PILIH OPERATOR PENYUSUN:</span>
                                                <div class="space-x-2">
                                                    <button type="button" @click="selectedOperators = [{{ $allOperators->pluck('id')->join(',') }}]" class="text-indigo-600 hover:underline">Pilih Semua</button>
                                                    <span>|</span>
                                                    <button type="button" @click="selectedOperators = []" class="text-red-600 hover:underline">Reset</button>
                                                </div>
                                            </div>
                                            <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                                @foreach($allOperators as $op)
                                                    <label class="flex items-center gap-2.5 text-xs text-gray-700 py-1 px-2 hover:bg-white rounded-lg cursor-pointer transition">
                                                        <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}" x-model="selectedOperators" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                        <span><strong>{{ $op->name }}</strong> <span class="text-gray-400">({{ $op->unit->name ?? 'Tanpa Unit' }})</span></span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                @elseif($role === 'Supervisor')
                                    <!-- Filter Scope Supervisor -->
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <label class="flex items-center gap-2.5 text-xs font-semibold text-gray-800 cursor-pointer">
                                                <input type="radio" x-model="operatorScope" value="all" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Cetak Semua Operator (Akumulasi Unit: {{ $user->unit->name ?? '' }})</span>
                                            </label>
                                            <label class="flex items-center gap-2.5 text-xs font-semibold text-gray-800 cursor-pointer">
                                                <input type="radio" x-model="operatorScope" value="selected" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Pilih Operator Spesifik Tertentu</span>
                                            </label>
                                        </div>

                                        <!-- Checklist Operator Supervisor -->
                                        <div x-show="operatorScope === 'selected'" x-transition class="bg-slate-50 border border-gray-200 rounded-xl p-4 space-y-2.5">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 text-xs font-bold text-gray-600">
                                                <span>PILIH OPERATOR UNIT:</span>
                                                <div class="space-x-2">
                                                    <button type="button" @click="selectedOperators = [{{ $allOperators->pluck('id')->join(',') }}]" class="text-indigo-600 hover:underline">Pilih Semua</button>
                                                    <span>|</span>
                                                    <button type="button" @click="selectedOperators = []" class="text-red-600 hover:underline">Reset</button>
                                                </div>
                                            </div>
                                            <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                                @forelse($allOperators as $op)
                                                    <label class="flex items-center gap-2.5 text-xs text-gray-700 py-1 px-2 hover:bg-white rounded-lg cursor-pointer transition">
                                                        <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}" x-model="selectedOperators" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                        <span><strong>{{ $op->name }}</strong> <span class="text-gray-400">({{ $op->email }})</span></span>
                                                    </label>
                                                @empty
                                                    <p class="text-xs text-gray-400 italic text-center py-2">Belum ada operator terdaftar di unit ini.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                @else
                                    <!-- Filter Scope Operator -->
                                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 text-xs text-emerald-900">
                                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <span class="font-bold">Cakupan Otomatis Operator:</span>
                                            <p class="mt-0.5 text-emerald-800">
                                                Dokumen laporan akan dicetak khusus memuat rincian usulan belanja yang Anda susun pada unit <strong>{{ $user->unit->name ?? 'Kerja' }}</strong>.
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Tombol Submit Cetak -->
                            <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="text-xs text-gray-500">
                                    <span x-show="selectedId" class="text-gray-700 font-medium">
                                        Periode terpilih ID: <span class="font-mono font-bold text-indigo-600" x-text="selectedId"></span>
                                    </span>
                                    <span x-show="!selectedId" class="text-red-500 font-medium">
                                        Silakan pilih salah satu periode RBA di sebelah kiri.
                                    </span>
                                </div>

                                <button type="submit" :disabled="!selectedId"
                                    :class="!selectedId ? 'opacity-50 cursor-not-allowed' : 'hover:scale-[1.02] shadow-indigo-200 hover:shadow-indigo-300'"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-bold rounded-xl text-sm shadow-md transition transform active:scale-95 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    <span>Buka Pratinjau Cetak</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
