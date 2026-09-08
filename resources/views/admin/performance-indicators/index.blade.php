<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>🎯</span>
                    <span>{{ __('Manajemen Indikator Kinerja RSUD Kardinah') }}</span>
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Kelola data master indikator kinerja rumah sakit dan pantau target berkala 5 tahun perencanaan.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-indicator-modal'))"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Indikator Baru</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8" x-data="performanceIndicatorsManager()">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Notifications -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Toolbar: Filter Status, Rentang 5 Tahun & Search -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5">
                <form method="GET" action="{{ route('admin.performance-indicators.index') }}" class="space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Status Filter Pills -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider me-1">Status:</span>
                            <a href="{{ route('admin.performance-indicators.index', array_merge(request()->query(), ['status' => 'all'])) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $status === 'all' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                Semua ({{ $totalCount }})
                            </a>
                            <a href="{{ route('admin.performance-indicators.index', array_merge(request()->query(), ['status' => 'active'])) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $status === 'active' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                🟢 Aktif ({{ $activeCount }})
                            </a>
                            <a href="{{ route('admin.performance-indicators.index', array_merge(request()->query(), ['status' => 'inactive'])) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $status === 'inactive' ? 'bg-gray-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                ⚪ Nonaktif ({{ $inactiveCount }})
                            </a>
                        </div>

                        <!-- Rentang 5 Tahun Controller -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Periode Target (5 Tahun):</span>
                            
                            <!-- Prev 5 Years Button -->
                            <a href="{{ route('admin.performance-indicators.index', array_merge(request()->query(), ['start_year' => $startYear - 5])) }}"
                               title="5 Tahun Sebelumnya ({{ $startYear - 5 }} - {{ $startYear - 1 }})"
                               class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors">
                                ◀
                            </a>

                            <select name="start_year" onchange="this.form.submit()"
                                    class="text-xs font-bold rounded-xl border-slate-200 bg-slate-50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                                @foreach($availableYears as $y)
                                    <option value="{{ $y }}" {{ $startYear === $y ? 'selected' : '' }}>
                                        {{ $y }} — {{ $y + 4 }} (5 Kolom)
                                    </option>
                                @endforeach
                            </select>

                            <!-- Next 5 Years Button -->
                            <a href="{{ route('admin.performance-indicators.index', array_merge(request()->query(), ['start_year' => $startYear + 5])) }}"
                               title="5 Tahun Berikutnya ({{ $startYear + 5 }} - {{ $startYear + 9 }})"
                               class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors">
                                ▶
                            </a>
                        </div>
                    </div>

                    <!-- Search & Reset Filter Bar -->
                    <div class="flex flex-col sm:flex-row items-center gap-3 pt-3 border-t border-slate-100">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="relative flex-1 w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input type="text" name="search" value="{{ $search }}"
                                   placeholder="Cari indikator kinerja, kode, kategori teks bebas, atau deskripsi..."
                                   class="w-full text-xs pl-9 rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                🔍 Cari
                            </button>
                            @if(!empty($search) || $status !== 'all' || $startYear !== now()->year)
                                <a href="{{ route('admin.performance-indicators.index') }}"
                                   class="w-full sm:w-auto px-3 py-2 text-xs text-rose-600 hover:text-rose-800 font-bold hover:underline text-center">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Main Matrix Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-700 border-b border-slate-200">
                                <th class="py-3.5 px-3 text-center font-extrabold w-12 uppercase tracking-wider">No</th>
                                <th class="py-3.5 px-3 font-extrabold w-28 uppercase tracking-wider">Kode & Status</th>
                                <th class="py-3.5 px-4 font-extrabold min-w-[240px] uppercase tracking-wider">Indikator Kinerja</th>
                                <th class="py-3.5 px-3 font-extrabold w-28 uppercase tracking-wider">Kategori / Satuan</th>
                                
                                <!-- 5 Kolom Tahun -->
                                @foreach($years as $yr)
                                    <th class="py-3.5 px-3 text-center font-extrabold w-28 uppercase tracking-wider bg-indigo-50/40 border-l border-slate-200/80">
                                        <div class="text-indigo-700 font-black text-sm">{{ $yr }}</div>
                                        <span class="text-[10px] text-indigo-500 font-normal">Target Th.</span>
                                    </th>
                                @endforeach

                                <th class="py-3.5 px-4 text-center font-extrabold w-40 uppercase tracking-wider border-l border-slate-200">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($indicators as $index => $indicator)
                                @php
                                    $rowClass = !$indicator->is_active ? 'bg-slate-50/60 opacity-85' : 'hover:bg-indigo-50/20';
                                @endphp
                                <tr class="transition-colors {{ $rowClass }}">
                                    <!-- No -->
                                    <td class="py-3.5 px-3 text-center text-slate-400 font-medium">
                                        {{ $indicators->firstItem() + $index }}
                                    </td>

                                    <!-- Kode & Status Badge -->
                                    <td class="py-3.5 px-3">
                                        <div class="font-bold text-slate-800 text-xs">{{ $indicator->code ?: '-' }}</div>
                                        <div class="mt-1">
                                            @if($indicator->is_active)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Nama & Deskripsi Indikator -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 text-sm leading-snug">
                                            {{ $indicator->name }}
                                        </div>
                                        @if($indicator->description)
                                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed line-clamp-2" title="{{ $indicator->description }}">
                                                {{ $indicator->description }}
                                            </p>
                                        @endif
                                    </td>

                                    <!-- Kategori (Teks Bebas) & Satuan -->
                                    <td class="py-3.5 px-3">
                                        <div class="font-medium text-slate-700">
                                            {{ $indicator->category ?: '-' }}
                                        </div>
                                        @if($indicator->unit)
                                            <div class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                                Satuan: <span class="text-slate-600 font-bold">{{ $indicator->unit }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 5 Kolom Target Tahunan -->
                                    @foreach($years as $yr)
                                        @php
                                            $target = $indicator->getTargetByYear($yr);
                                        @endphp
                                        <td class="py-2 px-2 text-center border-l border-slate-200/80 align-middle">
                                            @if($target)
                                                <div class="p-1.5 rounded-xl hover:bg-white hover:shadow-2xs transition-all group flex flex-col items-center justify-center">
                                                    <!-- Target Value (Click to Edit) -->
                                                    <button type="button"
                                                            @click="openTargetModal({{ $indicator->id }}, '{{ addslashes($indicator->name) }}', '{{ $indicator->code }}', {{ $yr }}, '{{ addslashes($target->target_value) }}', {{ $target->current_version }})"
                                                            title="Klik untuk mengubah nilai target tahun {{ $yr }}"
                                                            class="font-extrabold text-indigo-700 hover:text-indigo-900 text-xs px-2 py-1 rounded-lg hover:bg-indigo-50 transition-colors cursor-pointer w-full truncate max-w-[100px]">
                                                        {{ $target->target_value }}
                                                    </button>

                                                    <!-- Version Badge & History Button -->
                                                    <div class="flex items-center gap-1 mt-0.5">
                                                        <button type="button"
                                                                @click="openHistoryModal({{ $indicator->id }}, {{ $yr }})"
                                                                title="Lihat riwayat perubahan (Versi {{ $target->current_version }})"
                                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors cursor-pointer {{ $target->current_version > 1 ? 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                                            <span>V{{ $target->current_version }}</span>
                                                            @if($target->current_version > 1)
                                                                <span class="text-[9px]">📜</span>
                                                            @endif
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <!-- Nilai Kosong: Tombol Isi Cepat -->
                                                <button type="button"
                                                        @click="openTargetModal({{ $indicator->id }}, '{{ addslashes($indicator->name) }}', '{{ $indicator->code }}', {{ $yr }}, '', 0)"
                                                        title="Isi target untuk tahun {{ $yr }}"
                                                        class="px-2 py-1 border border-dashed border-slate-300 hover:border-indigo-400 text-slate-400 hover:text-indigo-600 rounded-lg text-[11px] font-semibold transition-all hover:bg-indigo-50/30 cursor-pointer">
                                                    + Isi
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Kolom Aksi -->
                                    <td class="py-3 px-3 border-l border-slate-200 align-middle">
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Edit Indikator Parent -->
                                            <button type="button"
                                                    @click="openEditIndicatorModal({{ json_encode($indicator) }})"
                                                    title="Edit Data Indikator"
                                                    class="p-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-800 rounded-lg transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>

                                            <!-- Batch Target 5 Tahun Sekaligus -->
                                            <button type="button"
                                                    @click="openBatchTargetModal({{ json_encode($indicator) }}, {{ json_encode($years) }})"
                                                    title="Kelola Target 5 Kolom Sekaligus"
                                                    class="p-1.5 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 hover:text-emerald-800 rounded-lg transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                            </button>

                                            <!-- Toggle Status Aktif / Nonaktif -->
                                            <form action="{{ route('admin.performance-indicators.toggle-status', $indicator) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin {{ $indicator->is_active ? 'menonaktifkan' : 'mengaktifkan kembali' }} indikator kinerja ini?');">
                                                @csrf
                                                @if($indicator->is_active)
                                                    <button type="submit" title="Nonaktifkan Indikator (Arsipkan)"
                                                            class="p-1.5 text-amber-600 bg-amber-50 hover:bg-amber-100 hover:text-amber-800 rounded-lg transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </button>
                                                @else
                                                    <button type="submit" title="Aktifkan Kembali Indikator"
                                                            class="p-1.5 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 hover:text-emerald-800 rounded-lg transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </button>
                                                @endif
                                            </form>

                                            <!-- Hapus (Soft Delete) -->
                                            <form action="{{ route('admin.performance-indicators.destroy', $indicator) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus indikator kinerja ini? Data akan disimpan secara aman (soft delete) tanpa menghapus permanen.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus Indikator (Soft Delete)"
                                                        class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-100 hover:text-rose-800 rounded-lg transition-colors cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 5 + count($years) }}" class="py-12 text-center text-slate-400">
                                        <div class="w-16 h-16 mx-auto mb-3 text-slate-300">
                                            🎯
                                        </div>
                                        <p class="font-bold text-sm text-slate-600">Belum ada indikator kinerja yang ditemukan.</p>
                                        <p class="text-xs text-slate-400 mt-1">Silakan klik tombol "Tambah Indikator Baru" untuk memulai perencanaan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($indicators->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $indicators->links() }}
                    </div>
                @endif
            </div>

            <!-- MODAL 1: Tambah / Edit Indikator Kinerja Parent -->
            <div x-show="indicatorModalOpen" x-cloak style="display: none;"
                 class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeIndicatorModal()"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">
                        <!-- Header -->
                        <div class="bg-slate-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base shadow-2xs">
                                    🎯
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900" x-text="isEditIndicator ? 'Edit Indikator Kinerja' : 'Tambah Indikator Kinerja Baru'"></h3>
                                    <p class="text-[11px] text-gray-500">Definisi master indikator kinerja rumah sakit</p>
                                </div>
                            </div>
                            <button type="button" @click="closeIndicatorModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                ✕
                            </button>
                        </div>

                        <!-- Form -->
                        <form :action="indicatorFormUrl" method="POST">
                            @csrf
                            <template x-if="isEditIndicator">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <div class="p-6 space-y-4 text-xs">
                                <!-- Kode & Urutan -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Kode Indikator</label>
                                        <input type="text" name="code" x-model="indicatorForm.code" placeholder="Contoh: IK-01"
                                               class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Nomor Urutan Tampil</label>
                                        <input type="number" name="order" x-model="indicatorForm.order" min="0" placeholder="0"
                                               class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                    </div>
                                </div>

                                <!-- Nama Indikator -->
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Nama Indikator Kinerja <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name" x-model="indicatorForm.name" required placeholder="Contoh: Kepatuhan Hand Hygiene Tenaga Medis"
                                           class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                </div>

                                <!-- Kategori (TEKS BEBAS) & Satuan -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Kategori / Bidang <span class="text-slate-400 font-normal">(Teks Bebas)</span></label>
                                        <input type="text" name="category" x-model="indicatorForm.category"
                                               placeholder="Contoh: Pelayanan Medik, Keuangan, Mutu RS, SDM, dsb."
                                               class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                        <p class="text-[10px] text-slate-400 mt-1">Bebas ketik kategori apa pun</p>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Satuan Ukuran</label>
                                        <input type="text" name="unit" x-model="indicatorForm.unit" placeholder="Contoh: %, Hari, Skor, Predikat, Kasus"
                                               class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                        <p class="text-[10px] text-slate-400 mt-1">Satuan pengukur target</p>
                                    </div>
                                </div>

                                <!-- Definisi Operasional / Deskripsi -->
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Definisi Operasional / Formulasi</label>
                                    <textarea name="description" x-model="indicatorForm.description" rows="3"
                                              placeholder="Jelaskan definisi operasional, formulasi penghitungan, atau keterangan penting..."
                                              class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2"></textarea>
                                </div>

                                <!-- Status Aktif Checkbox -->
                                <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                                    <input type="checkbox" id="ind_is_active" name="is_active" value="1" x-model="indicatorForm.is_active"
                                           class="rounded border-slate-300 text-indigo-600 shadow-2xs focus:ring-indigo-500">
                                    <label for="ind_is_active" class="font-bold text-slate-700 cursor-pointer">
                                        Aktif (Siap digunakan dan dipantau target tahunannya)
                                    </label>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button type="button" @click="closeIndicatorModal()"
                                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition-all cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all cursor-pointer">
                                    Simpan Indikator
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL 2: Input / Ubah Cepat Nilai Target Tahunan (Single Year) -->
            <div x-show="targetModalOpen" x-cloak style="display: none;"
                 class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeTargetModal()"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                        <div class="bg-indigo-50/80 px-6 py-4 border-b border-indigo-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🎯</span>
                                <div>
                                    <h3 class="text-sm font-bold text-indigo-950">Kelola Target Indikator</h3>
                                    <p class="text-[11px] text-indigo-700">Tahun Anggaran: <span class="font-black" x-text="targetModalData.year"></span></p>
                                </div>
                            </div>
                            <button type="button" @click="closeTargetModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">✕</button>
                        </div>

                        <form :action="targetFormUrl" method="POST">
                            @csrf
                            <div class="p-6 space-y-4 text-xs">
                                <!-- Card Ringkasan Konteks -->
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                                    <div class="font-bold text-slate-800 text-sm" x-text="targetModalData.indicatorName"></div>
                                    <div class="text-[11px] text-slate-500">
                                        Kode: <span class="font-semibold text-slate-700" x-text="targetModalData.indicatorCode || '-'"></span>
                                    </div>
                                    <template x-if="targetModalData.currentVersion > 0">
                                        <div class="pt-1 border-t border-slate-200 flex justify-between items-center text-[11px]">
                                            <span class="text-slate-500">Nilai Saat Ini: <strong class="text-indigo-600" x-text="targetModalData.currentValue"></strong></span>
                                            <span class="px-2 py-0.5 bg-sky-100 text-sky-800 rounded font-bold text-[10px]" x-text="'Versi ' + targetModalData.currentVersion"></span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Input Nilai Target Baru -->
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">
                                        Nilai Target Tahun <span x-text="targetModalData.year"></span> <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="target_value" x-model="targetModalData.targetValue" required
                                           placeholder="Contoh: 100%, 85, Paripurna, Sesuai SPM, dsb."
                                           class="w-full text-xs font-bold rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                    <p class="text-[10px] text-slate-400 mt-1">Teks bebas ringkas (angka, persentase, atau teks mutu).</p>
                                </div>

                                <!-- Input Alasan / Catatan Perubahan -->
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">
                                        Alasan / Catatan Perubahan <span class="text-slate-400 font-normal" x-text="targetModalData.currentVersion > 0 ? '(Direkomendasikan)' : '(Opsional)'"></span>
                                    </label>
                                    <textarea name="change_note" x-model="targetModalData.changeNote" rows="2"
                                              placeholder="Contoh: Penyesuaian target mengikuti Renstra Revisi Tahun 2025 sesuai SK Direktur..."
                                              class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2"></textarea>
                                </div>

                                <template x-if="targetModalData.currentVersion > 0">
                                    <div class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-xl border border-amber-200">
                                        ℹ️ Pengeditan nilai ini akan otomatis dicatat sebagai <strong>Versi <span x-text="targetModalData.currentVersion + 1"></span></strong> pada audit trail riwayat target.
                                    </div>
                                </template>
                            </div>

                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button type="button" @click="closeTargetModal()"
                                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm cursor-pointer">
                                    Simpan Target
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL 3: Batch Update Target 5 Tahun Sekaligus -->
            <div x-show="batchModalOpen" x-cloak style="display: none;"
                 class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeBatchModal()"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100">
                        <div class="bg-emerald-50/80 px-6 py-4 border-b border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">📊</span>
                                <div>
                                    <h3 class="text-sm font-bold text-emerald-950">Kelola Target 5 Tahun Sekaligus</h3>
                                    <p class="text-[11px] text-emerald-700" x-text="batchData.indicatorName"></p>
                                </div>
                            </div>
                            <button type="button" @click="closeBatchModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">✕</button>
                        </div>

                        <form :action="batchFormUrl" method="POST">
                            @csrf
                            <div class="p-6 space-y-4 text-xs">
                                <p class="text-slate-500">
                                    Isi atau sesuaikan target untuk 5 tahun berikut. Nilai yang diubah akan dicatat riwayat versinya secara otomatis.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                    <template x-for="yr in batchData.years" :key="yr">
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                            <div class="font-black text-indigo-700 text-sm mb-1 text-center" x-text="yr"></div>
                                            <input type="text" :name="'targets[' + yr + ']'" x-model="batchData.targets[yr]"
                                                   placeholder="Target"
                                                   class="w-full text-xs font-bold text-center rounded-lg border-slate-200 bg-white focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-2">
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Catatan Perubahan (Opsional)</label>
                                    <input type="text" name="change_note" placeholder="Contoh: Pembaruan rencana strategis 5 tahunan..."
                                           class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button type="button" @click="closeBatchModal()"
                                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm cursor-pointer">
                                    Simpan Semua Target
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MODAL 4: Dialog Riwayat Versi Target (Audit Trail / Versioning) -->
            <div x-show="historyModalOpen" x-cloak style="display: none;"
                 class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeHistoryModal()"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100">
                        <!-- Header -->
                        <div class="bg-slate-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-base shadow-2xs">
                                    📜
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Riwayat Versi Target Indikator</h3>
                                    <p class="text-[11px] text-gray-500">
                                        <span x-text="historyData.indicatorName"></span> (<span class="font-bold text-indigo-600" x-text="'Tahun ' + historyData.year"></span>)
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="closeHistoryModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg cursor-pointer">✕</button>
                        </div>

                        <!-- Content / Timeline -->
                        <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4 text-xs">
                            <template x-if="historyLoading">
                                <div class="py-8 text-center text-slate-400">
                                    <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
                                    <p>Memuat riwayat pengubahan target...</p>
                                </div>
                            </template>

                            <template x-if="!historyLoading && historyData.histories && historyData.histories.length > 0">
                                <div class="relative pl-6 border-l-2 border-indigo-100 space-y-6">
                                    <template x-for="(item, idx) in historyData.histories" :key="item.id">
                                        <div class="relative">
                                            <!-- Timeline Dot -->
                                            <div class="absolute -left-[31px] top-0 w-4 h-4 rounded-full border-2 border-white shadow-xs"
                                                 :class="idx === 0 ? 'bg-indigo-600 ring-4 ring-indigo-50' : 'bg-slate-300'"></div>

                                            <div class="p-3.5 rounded-xl border transition-all"
                                                 :class="idx === 0 ? 'bg-indigo-50/40 border-indigo-200' : 'bg-slate-50 border-slate-200'">
                                                <!-- Header Item -->
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="font-black px-2 py-0.5 rounded text-[10px]"
                                                          :class="idx === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700'"
                                                          x-text="'Versi ' + item.version_number + (idx === 0 ? ' (Terkini)' : '')"></span>
                                                    <span class="text-[10px] text-slate-400 font-medium" x-text="item.created_at_formatted"></span>
                                                </div>

                                                <!-- Perubahan Nilai -->
                                                <div class="flex items-center gap-2 my-2">
                                                    <template x-if="item.old_value !== null">
                                                        <div class="flex items-center gap-2">
                                                            <span class="line-through text-slate-400 font-semibold" x-text="item.old_value"></span>
                                                            <span class="text-slate-400 font-bold">➔</span>
                                                        </div>
                                                    </template>
                                                    <span class="font-black text-sm text-indigo-700" x-text="item.new_value"></span>
                                                </div>

                                                <!-- Catatan / Alasan -->
                                                <template x-if="item.change_note">
                                                    <p class="text-[11px] text-slate-600 italic bg-white/80 p-2 rounded-lg border border-slate-100 mb-2" x-text="'“' + item.change_note + '”'"></p>
                                                </template>

                                                <!-- Pengubah -->
                                                <div class="text-[10px] text-slate-400 flex items-center gap-1">
                                                    <span>👤 Diubah oleh:</span>
                                                    <strong class="text-slate-700" x-text="item.user_name"></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!historyLoading && (!historyData.histories || historyData.histories.length === 0)">
                                <div class="py-6 text-center text-slate-400">
                                    <p>Belum ada rekaman riwayat untuk target ini.</p>
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-end">
                            <button type="button" @click="closeHistoryModal()"
                                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script Alpine.js Component -->
    @push('scripts')
    <script>
        function performanceIndicatorsManager() {
            return {
                // State Modal 1: Indikator Parent
                indicatorModalOpen: false,
                isEditIndicator: false,
                indicatorFormUrl: '{{ route('admin.performance-indicators.store') }}',
                indicatorForm: {
                    id: null,
                    code: '',
                    name: '',
                    category: '',
                    unit: '',
                    description: '',
                    order: 0,
                    is_active: true,
                },

                // State Modal 2: Target Single Year
                targetModalOpen: false,
                targetFormUrl: '',
                targetModalData: {
                    indicatorId: null,
                    indicatorName: '',
                    indicatorCode: '',
                    year: null,
                    targetValue: '',
                    currentValue: '',
                    currentVersion: 0,
                    changeNote: '',
                },

                // State Modal 3: Batch Targets
                batchModalOpen: false,
                batchFormUrl: '',
                batchData: {
                    indicatorId: null,
                    indicatorName: '',
                    years: [],
                    targets: {},
                },

                // State Modal 4: History Versioning
                historyModalOpen: false,
                historyLoading: false,
                historyData: {
                    indicatorName: '',
                    year: null,
                    histories: [],
                },

                init() {
                    window.addEventListener('open-indicator-modal', () => {
                        this.openAddIndicatorModal();
                    });
                },

                // Methods Modal 1: Indikator Parent
                openAddIndicatorModal() {
                    this.isEditIndicator = false;
                    this.indicatorFormUrl = '{{ route('admin.performance-indicators.store') }}';
                    this.indicatorForm = {
                        id: null,
                        code: '',
                        name: '',
                        category: '',
                        unit: '',
                        description: '',
                        order: 0,
                        is_active: true,
                    };
                    this.indicatorModalOpen = true;
                },

                openEditIndicatorModal(indicator) {
                    this.isEditIndicator = true;
                    this.indicatorFormUrl = '{{ url('admin/performance-indicators') }}/' + indicator.id;
                    this.indicatorForm = {
                        id: indicator.id,
                        code: indicator.code || '',
                        name: indicator.name || '',
                        category: indicator.category || '',
                        unit: indicator.unit || '',
                        description: indicator.description || '',
                        order: indicator.order || 0,
                        is_active: Boolean(indicator.is_active),
                    };
                    this.indicatorModalOpen = true;
                },

                closeIndicatorModal() {
                    this.indicatorModalOpen = false;
                },

                // Methods Modal 2: Target Single Year
                openTargetModal(indicatorId, indicatorName, indicatorCode, year, currentVal, currentVer) {
                    this.targetFormUrl = '{{ url('admin/performance-indicators') }}/' + indicatorId + '/targets/' + year;
                    this.targetModalData = {
                        indicatorId: indicatorId,
                        indicatorName: indicatorName,
                        indicatorCode: indicatorCode,
                        year: year,
                        targetValue: currentVal || '',
                        currentValue: currentVal || '',
                        currentVersion: currentVer || 0,
                        changeNote: '',
                    };
                    this.targetModalOpen = true;
                },

                closeTargetModal() {
                    this.targetModalOpen = false;
                },

                // Methods Modal 3: Batch Targets
                openBatchTargetModal(indicator, yearsList) {
                    this.batchFormUrl = '{{ url('admin/performance-indicators') }}/' + indicator.id + '/targets-batch';
                    
                    const targetsMap = {};
                    yearsList.forEach(yr => {
                        const found = (indicator.targets || []).find(t => t.year == yr);
                        targetsMap[yr] = found ? found.target_value : '';
                    });

                    this.batchData = {
                        indicatorId: indicator.id,
                        indicatorName: indicator.name,
                        years: yearsList,
                        targets: targetsMap,
                    };
                    this.batchModalOpen = true;
                },

                closeBatchModal() {
                    this.batchModalOpen = false;
                },

                // Methods Modal 4: History Versioning
                async openHistoryModal(indicatorId, year) {
                    this.historyLoading = true;
                    this.historyModalOpen = true;
                    this.historyData = {
                        indicatorName: '',
                        year: year,
                        histories: [],
                    };

                    try {
                        const res = await fetch('{{ url('admin/performance-indicators') }}/' + indicatorId + '/targets/' + year + '/history');
                        const data = await res.json();
                        if (data.success) {
                            this.historyData = {
                                indicatorName: data.indicator_name,
                                year: data.year,
                                histories: data.histories || [],
                            };
                        } else {
                            alert(data.message || 'Gagal memuat riwayat.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Terjadi kesalahan saat memuat riwayat target.');
                    } finally {
                        this.historyLoading = false;
                    }
                },

                closeHistoryModal() {
                    this.historyModalOpen = false;
                }
            };
        }
        window.performanceIndicatorsManager = performanceIndicatorsManager;
        if (window.Alpine) {
            window.Alpine.data('performanceIndicatorsManager', performanceIndicatorsManager);
        }
        document.addEventListener('alpine:init', () => {
            if (window.Alpine) {
                window.Alpine.data('performanceIndicatorsManager', performanceIndicatorsManager);
            }
        });
    </script>
    @endpush
</x-app-layout>
