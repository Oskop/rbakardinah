<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-indigo-100 text-indigo-700 rounded-xl shadow-xs">🌳</span>
                    <span>{{ __('Bagan Struktur Organisasi RSUD Kardinah') }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Hierarki Tata Kelola SIPAKAR: Direksi (Eselon II) → Bagian/Bidang (Eselon III) → Sub-Unit (Eselon IV/Non) → Pegawai Personal (SSO SIMRS)</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.sub-units.index') }}"
                    class="inline-flex items-center px-3.5 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold shadow-xs transition-colors">
                    🏛️ Kelola Sub-Unit
                </a>
                <a href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors">
                    👥 Kelola Pegawai
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ 
        searchTerm: '', 
        selectedUnit: 'all',
        expandAll: true,
        expandedUnits: {
            @foreach($units as $u)
                {{ $u->id }}: true,
            @endforeach
        },
        toggleUnit(id) {
            this.expandedUnits[id] = !this.expandedUnits[id];
        },
        setAll(state) {
            this.expandAll = state;
            for (let id in this.expandedUnits) {
                this.expandedUnits[id] = state;
            }
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Stats Banner -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold">🏛️</div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Unit Induk</div>
                        <div class="text-lg font-black text-slate-800">{{ $stats['total_units'] }} <span class="text-[10px] font-normal text-slate-400">Bagian/Bid</span></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">📑</div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Sub-Unit Kerja</div>
                        <div class="text-lg font-black text-slate-800">{{ $stats['total_sub_units'] }} <span class="text-[10px] font-normal text-slate-400">Satker</span></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">👥</div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Total Pegawai</div>
                        <div class="text-lg font-black text-slate-800">{{ $stats['total_users'] }} <span class="text-[10px] font-normal text-slate-400">User</span></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold">👔</div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Supervisor</div>
                        <div class="text-lg font-black text-slate-800">{{ $stats['total_supervisors'] }} <span class="text-[10px] font-normal text-slate-400">Kabag/Kabid</span></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">⚙️</div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Operator Satker</div>
                        <div class="text-lg font-black text-slate-800">{{ $stats['total_operators'] }} <span class="text-[10px] font-normal text-slate-400">Personil</span></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold">🏥</div>
                    <div>
                        <div class="text-xs font-medium text-slate-400">Akun SSO SIMRS</div>
                        <div class="text-lg font-black text-slate-800">{{ $stats['total_sso_users'] }} <span class="text-[10px] font-normal text-slate-400">Tersinkron</span></div>
                    </div>
                </div>
            </div>

            <!-- Toolbar Pencarian & Kontrol Tree -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-4 sm:p-5 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-1 w-full sm:w-auto items-center gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            🔍
                        </span>
                        <input type="text" x-model="searchTerm" placeholder="Cari nama pegawai, NIP, sub-unit, atau bagian..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                    </div>
                    <select x-model="selectedUnit" class="text-xs rounded-xl border-slate-200 bg-slate-50/50 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">Semua Bagian / Bidang</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button type="button" @click="setAll(true)"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors">
                        ➕ Buka Semua
                    </button>
                    <button type="button" @click="setAll(false)"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors">
                        ➖ Tutup Semua
                    </button>
                </div>
            </div>

            <!-- LEVEL 1: PIMPINAN EKSEKUTIF / DIREKSI RSUD KARDINAH -->
            <div class="bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl pointer-events-none select-none">🏥</div>
                
                <div class="flex items-center justify-between border-b border-indigo-800/80 pb-4 mb-6">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-indigo-500/20 text-indigo-300 rounded-xl border border-indigo-400/30 text-lg">👑</span>
                        <div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">Level 1 • Eselon II</span>
                            <h3 class="text-lg font-black text-white mt-0.5">Dewan Direksi & Administrator Sistem</h3>
                        </div>
                    </div>
                    <span class="text-xs text-indigo-300 font-medium">Penanggung Jawab Tertinggi RSUD Kardinah</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @forelse($direksi as $d)
                        <div class="bg-white/10 hover:bg-white/15 backdrop-blur-xs border border-white/10 rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="w-9 h-9 rounded-xl bg-indigo-400/20 border border-indigo-300/30 flex items-center justify-center text-sm font-bold text-indigo-200">
                                    {{ strtoupper(substr($d->name, 0, 2)) }}
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $d->auth_provider === 'simrs_oidc' ? 'bg-purple-500/30 text-purple-200 border border-purple-400/30' : 'bg-slate-700/60 text-slate-300' }}">
                                    {{ $d->auth_provider === 'simrs_oidc' ? '🏥 SSO SIMRS' : '🔐 Lokal' }}
                                </span>
                            </div>
                            <div class="mt-3">
                                <h4 class="font-bold text-sm text-white leading-tight">{{ $d->name }}</h4>
                                <div class="text-xs text-indigo-200 font-medium mt-0.5">{{ $d->jabatan ?? $d->role }}</div>
                                @if($d->nip)
                                    <div class="text-[11px] text-indigo-300/80 font-mono mt-1 flex items-center gap-1">
                                        <span>🪪</span> NIP. {{ $d->nip }}
                                    </div>
                                @endif
                                <div class="text-[11px] text-indigo-300/70 truncate mt-0.5">{{ $d->email }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-4 text-center py-4 text-xs text-indigo-300 italic">Belum ada user administrator terdaftar.</div>
                    @endforelse
                </div>
            </div>

            <!-- GARIS PENGHUBUNG HIERARKI -->
            <div class="flex flex-col items-center justify-center my-2">
                <div class="w-0.5 h-6 bg-indigo-300"></div>
                <div class="px-4 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-widest bg-indigo-100 text-indigo-800 border border-indigo-200 shadow-2xs">
                    Membawahi 6 Unit Induk Penganggaran RBA (Eselon III)
                </div>
                <div class="w-0.5 h-6 bg-indigo-300"></div>
            </div>

            <!-- LEVEL 2 & 3: UNIT INDUK (ESELON III) DENGAN SUB-UNIT OPERASIONAL -->
            <div class="space-y-6">
                @foreach($units as $unit)
                    <div x-show="(selectedUnit === 'all' || selectedUnit == '{{ $unit->id }}') && (searchTerm === '' || '{{ strtolower($unit->name) }}'.includes(searchTerm.toLowerCase()) || '{{ strtolower($unit->subUnits->pluck('name')->join(' ')) }}'.includes(searchTerm.toLowerCase()) || '{{ strtolower($unit->users->pluck('name')->join(' ')) }}'.includes(searchTerm.toLowerCase()))"
                        class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden transition-all duration-200">
                        
                        <!-- Unit Header (Level 2) -->
                        <div class="bg-gradient-to-r from-slate-50 via-indigo-50/40 to-slate-50 p-5 sm:p-6 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer select-none"
                            @click="toggleUnit({{ $unit->id }})">
                            
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-xl font-bold shadow-md shadow-indigo-600/20 shrink-0">
                                    🏢
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 font-mono">
                                            {{ $unit->code }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Level 2 • Eselon III (Poros Dokumen RBA)
                                        </span>
                                    </div>
                                    <h3 class="text-xl font-black text-slate-800 mt-1 flex items-center gap-2">
                                        <span>{{ $unit->name }}</span>
                                    </h3>
                                    <!-- Supervisor in Charge -->
                                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-600">
                                        <span>👔 Supervisor (Kepala):</span>
                                        @php
                                            $supervisor = $unit->users->where('role', 'Supervisor')->first();
                                        @endphp
                                        @if($supervisor)
                                            <span class="font-bold text-indigo-700">{{ $supervisor->name }}</span>
                                            @if($supervisor->nip)
                                                <span class="text-slate-400 font-mono">(NIP. {{ $supervisor->nip }})</span>
                                            @endif
                                            @if($supervisor->auth_provider === 'simrs_oidc')
                                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-purple-50 text-purple-700 font-bold border border-purple-200">🏥 SSO</span>
                                            @endif
                                        @else
                                            <span class="text-amber-600 italic font-medium">⚠️ Belum ada Supervisor ditugaskan</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 self-end md:self-center">
                                <div class="text-right">
                                    <div class="text-xs font-bold text-slate-700">{{ $unit->subUnits->count() }} Sub-Unit Kerja</div>
                                    <div class="text-[11px] text-slate-400">{{ $unit->users->where('role', 'Operator')->count() }} Operator Aktif</div>
                                </div>
                                <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-sm transform transition-transform duration-200"
                                    :class="{'rotate-180': expandedUnits[{{ $unit->id }}]}">
                                    ▼
                                </span>
                            </div>
                        </div>

                        <!-- Sub-Units & Employees Grid (Level 3 & 4) -->
                        <div x-show="expandedUnits[{{ $unit->id }}]" x-collapse class="p-6 bg-slate-50/40 border-t border-slate-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @forelse($unit->subUnits as $subUnit)
                                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:shadow-md transition-shadow duration-200 flex flex-col justify-between"
                                        x-show="searchTerm === '' || '{{ strtolower($subUnit->name) }}'.includes(searchTerm.toLowerCase()) || '{{ strtolower($subUnit->users->pluck('name')->join(' ')) }}'.includes(searchTerm.toLowerCase())">
                                        
                                        <!-- Sub Unit Header -->
                                        <div>
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                                    {{ $subUnit->type === 'Sub Bagian' || $subUnit->type === 'Sub Bidang' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                                                        ($subUnit->type === 'Instalasi' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                                                        ($subUnit->type === 'Komite' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200')) }}">
                                                    {{ $subUnit->type ?? 'Unit' }}
                                                </span>
                                                <span class="text-[10px] font-mono text-slate-400">{{ $subUnit->code ?? '' }}</span>
                                            </div>

                                            <h4 class="font-bold text-sm text-slate-900 mt-2 flex items-center gap-1.5">
                                                <span>📌</span>
                                                <span>{{ $subUnit->name }}</span>
                                            </h4>
                                        </div>

                                        <!-- Assigned Employees (Level 4 - Akun Personal SSO) -->
                                        <div class="mt-4 pt-3 border-t border-slate-100">
                                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2 flex items-center justify-between">
                                                <span>Personil Terdaftar ({{ $subUnit->users->count() }})</span>
                                                <a href="{{ route('admin.users.create') }}" class="text-indigo-600 hover:underline">➕ Tambah</a>
                                            </div>

                                            @if($subUnit->users->isEmpty())
                                                <div class="text-xs text-slate-400 italic py-1 text-center bg-slate-50 rounded-lg">
                                                    Belum ada personil SSO ditugaskan
                                                </div>
                                            @else
                                                <div class="space-y-1.5">
                                                    @foreach($subUnit->users as $u)
                                                        <div class="p-2 bg-slate-50 hover:bg-indigo-50/50 rounded-xl text-xs flex items-center justify-between gap-2 border border-slate-100 transition-colors">
                                                            <div class="truncate">
                                                                <div class="font-bold text-slate-800 truncate flex items-center gap-1">
                                                                    <span>{{ $u->name }}</span>
                                                                    @if($u->auth_provider === 'simrs_oidc')
                                                                        <span class="text-[9px] px-1 py-0.2 bg-purple-100 text-purple-800 rounded font-bold" title="Login SSO SIMRS">SSO</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-[11px] text-slate-500 truncate">
                                                                    {{ $u->jabatan ?? ($u->nip ? 'NIP: ' . $u->nip : $u->role) }}
                                                                </div>
                                                            </div>
                                                            <a href="{{ route('admin.users.edit', $u) }}" class="text-indigo-600 hover:text-indigo-800 p-1" title="Edit User">
                                                                ✏️
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-3 text-center py-6 text-xs text-slate-400 italic">
                                        Belum ada sub-unit operasional di bawah unit ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
