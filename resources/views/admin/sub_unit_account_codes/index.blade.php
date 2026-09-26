<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-100 text-indigo-700 rounded-lg text-lg">🔗</span>
                    {{ __('Manajemen Mapping Rekening Sub-Unit') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">Konfigurasi hak dan kewenangan pengusulan nomor rekening belanja per sub-unit kerja operasional</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openBulkModal()"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition shadow-sm cursor-pointer">
                    <span>⚡</span> <span class="ml-1.5">Bulk Assign Rekening</span>
                </button>
                <button type="button" onclick="openCreateModal()"
                    class="inline-flex items-center px-3.5 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-sm cursor-pointer">
                    <span>➕</span> <span class="ml-1.5">Tambah Mapping</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alerts -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">✅</span>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm font-bold">&times;</button>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">⚠️</span>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm font-bold">&times;</button>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider mb-1">Terdapat kesalahan pengisian:</p>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center gap-4">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl text-2xl">
                        📊
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Mapping</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ number_format($totalMappings, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-slate-400">Relasi rekening ke unit</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center gap-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl text-2xl">
                        🏛️
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sub-Unit Terpetakan</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $totalSubUnits }} <span class="text-xs font-normal text-slate-400">Unit</span></p>
                        <p class="text-[11px] text-slate-400">Dari total {{ $subUnits->count() }} sub-unit</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl text-2xl">
                        💳
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Rekening Terpetakan</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $totalAccounts }} <span class="text-xs font-normal text-slate-400">Rek</span></p>
                        <p class="text-[11px] text-slate-400">Nomor rekening unik</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 flex items-center gap-4">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-xl text-2xl">
                        📅
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tahun Anggaran</p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $years->first() ?? '2027' }}</p>
                        <p class="text-[11px] text-slate-400">Acuan RBA berjalan</p>
                    </div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Filter Data Mapping</h3>
                            <p class="text-[11px] text-slate-400">Saring relasi rekening berdasarkan sub-unit, kelompok belanja, atau tahun</p>
                        </div>
                    </div>
                    <button type="button" id="btn-reset-filters"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5 transition-colors cursor-pointer">
                        <span>🔄</span>
                        <span>Reset Semua Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Filter Sub-Unit -->
                    <div>
                        <label for="filter-subunit" class="block text-xs font-bold text-slate-700 mb-1.5">Sub-Unit Kerja</label>
                        <select id="filter-subunit" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Sub-Unit</option>
                            @foreach($subUnits as $su)
                                <option value="{{ $su->name }}">{{ $su->name }} ({{ $su->unit?->name ?? 'Tanpa Induk' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Kelompok Belanja -->
                    <div>
                        <label for="filter-kelompok" class="block text-xs font-bold text-slate-700 mb-1.5">Kelompok Belanja</label>
                        <select id="filter-kelompok" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Kelompok Belanja</option>
                            @foreach($kelompokBelanjas as $kb)
                                <option value="{{ $kb->name }}">{{ $kb->kode }} - {{ $kb->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Tahun Anggaran -->
                    <div>
                        <label for="filter-year" class="block text-xs font-bold text-slate-700 mb-1.5">Tahun Anggaran</label>
                        <select id="filter-year" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Tahun</option>
                            @foreach($years as $yr)
                                <option value="{{ $yr }}">{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="mapping-table" class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50/80 text-slate-600">
                                <tr>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider w-12">No</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider">Sub-Unit Kerja</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider">Kode Rekening</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider">Uraian Rekening</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider">Kelompok</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider">Keterangan Khusus</th>
                                    <th scope="col" class="px-4 py-3.5 text-center text-xs font-extrabold uppercase tracking-wider">Tahun</th>
                                    <th scope="col" class="px-4 py-3.5 text-right text-xs font-extrabold uppercase tracking-wider w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($mappings as $index => $item)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-400 font-mono text-center">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="px-6 py-3.5 text-sm" data-search="{{ $item->subUnit?->name }}" data-filter="{{ $item->subUnit?->name }}">
                                            <div class="font-bold text-slate-800">{{ $item->subUnit?->name ?? '-' }}</div>
                                            @if($item->subUnit?->unit)
                                                <div class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                                    <span>🏢</span> {{ $item->subUnit->unit->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs font-mono font-bold text-indigo-700">
                                            {{ $item->accountCode?->code ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3.5 text-xs text-slate-700 font-medium">
                                            {{ $item->accountCode?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-xs" data-search="{{ $item->accountCode?->kelompokBelanja?->name }}" data-filter="{{ $item->accountCode?->kelompokBelanja?->name }}">
                                            @php
                                                $kbKode = $item->accountCode?->kelompokBelanja?->kode;
                                                $kbName = $item->accountCode?->kelompokBelanja?->name ?? '-';
                                                $badgeStyle = match(true) {
                                                    str_starts_with($kbKode ?? '', '5.1.01') => 'bg-amber-100 text-amber-800 border-amber-200',
                                                    str_starts_with($kbKode ?? '', '5.1.02') => 'bg-blue-100 text-blue-800 border-blue-200',
                                                    default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeStyle }}">
                                                {{ $kbName }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 text-xs text-slate-500 italic">
                                            {{ $item->keterangan_khusus ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-center text-xs font-bold text-slate-600 font-mono" data-search="{{ $item->fiscal_year }}" data-filter="{{ $item->fiscal_year }}">
                                            <span class="px-2 py-0.5 bg-slate-100 rounded-md border border-slate-200 text-slate-700">
                                                {{ $item->fiscal_year ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right text-xs font-medium">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" 
                                                    onclick="openEditModal({{ $item->id }}, {{ $item->sub_unit_id }}, {{ $item->account_code_id }}, '{{ addslashes($item->fiscal_year) }}', '{{ addslashes($item->keterangan_khusus ?? '') }}')"
                                                    class="inline-flex items-center p-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 rounded-lg transition-colors cursor-pointer"
                                                    title="Edit Mapping">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <form action="{{ route('admin.sub-unit-account-codes.destroy', $item) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus mapping rekening ini dari {{ addslashes($item->subUnit?->name ?? 'sub-unit') }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                        class="inline-flex items-center p-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 rounded-lg transition-colors cursor-pointer"
                                                        title="Hapus Mapping">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-400 italic">
                                            Belum ada data mapping rekening sub-unit. Silakan klik tombol "Tambah Mapping" atau "Bulk Assign Rekening".
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL 1: Tambah Mapping Satuan -->
    <div id="modal-create" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeCreateModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.sub-unit-account-codes.store') }}" method="POST">
                    @csrf
                    <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">➕</span>
                            <h3 class="font-bold text-base" id="modal-title">Tambah Mapping Rekening</h3>
                        </div>
                        <button type="button" onclick="closeCreateModal()" class="text-indigo-200 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Sub Unit -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Sub-Unit Kerja <span class="text-rose-500">*</span></label>
                            <select name="sub_unit_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                <option value="">-- Pilih Sub-Unit Kerja --</option>
                                @foreach($subUnits as $su)
                                    <option value="{{ $su->id }}">{{ $su->name }} ({{ $su->unit?->name ?? 'Tanpa Induk' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Account Code -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor Rekening Belanja <span class="text-rose-500">*</span></label>
                            <select name="account_code_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                <option value="">-- Pilih Nomor Rekening --</option>
                                @foreach($accountCodes as $ac)
                                    <option value="{{ $ac->id }}">[{{ $ac->code }}] {{ $ac->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Fiscal Year -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                            <input type="text" name="fiscal_year" value="2027" required class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 font-mono">
                        </div>

                        <!-- Keterangan Khusus -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan Khusus / Peruntukan RSUD (Opsional)</label>
                            <textarea name="keterangan_khusus" rows="2" placeholder="Contoh: Honorarium Pengajar Kordik / Bahan Habis Pakai Laboratorium" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 rounded-lg text-xs font-bold text-white hover:bg-indigo-700 cursor-pointer shadow-sm">
                            Simpan Mapping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Bulk Assign Rekening -->
    <div id="modal-bulk" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-bulk-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeBulkModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form action="{{ route('admin.sub-unit-account-codes.bulk-store') }}" method="POST">
                    @csrf
                    <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">⚡</span>
                            <h3 class="font-bold text-base" id="modal-bulk-title">Bulk Assign Rekening ke Sub-Unit</h3>
                        </div>
                        <button type="button" onclick="closeBulkModal()" class="text-emerald-200 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Sub Unit -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Target Sub-Unit Kerja <span class="text-rose-500">*</span></label>
                                <select name="sub_unit_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                                    <option value="">-- Pilih Sub-Unit Kerja --</option>
                                    @foreach($subUnits as $su)
                                        <option value="{{ $su->id }}">{{ $su->name }} ({{ $su->unit?->name ?? 'Tanpa Induk' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Fiscal Year -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                                <input type="text" name="fiscal_year" value="2027" required class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 font-mono">
                            </div>
                        </div>

                        <!-- Keterangan Khusus -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan Khusus Bersama (Opsional)</label>
                            <input type="text" name="keterangan_khusus" placeholder="Catatan peruntukan berlaku untuk semua rekening terpilih" class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2">
                        </div>

                        <!-- Checklist Rekening with Quick Search -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Pilih Nomor Rekening Belanja <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="toggleSelectAllBulk(true)" class="text-[11px] text-emerald-600 hover:underline font-bold cursor-pointer">Pilih Semua Tampil</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" onclick="toggleSelectAllBulk(false)" class="text-[11px] text-slate-500 hover:underline font-bold cursor-pointer">Batal Pilih</button>
                                </div>
                            </div>

                            <input type="text" id="bulk-search-box" placeholder="Ketik kode atau nama rekening untuk menyaring..."
                                class="w-full text-xs rounded-xl border-slate-200 mb-2 py-2 px-3 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500">

                            <div id="bulk-checkbox-container" class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl p-3 space-y-1.5 bg-slate-50/50">
                                @foreach($accountCodes as $ac)
                                    <label class="flex items-center gap-2.5 p-2 bg-white rounded-lg border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/40 transition cursor-pointer bulk-account-item"
                                        data-text="{{ strtolower($ac->code . ' ' . $ac->name) }}">
                                        <input type="checkbox" name="account_code_ids[]" value="{{ $ac->id }}" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                        <div class="text-xs">
                                            <span class="font-mono font-bold text-indigo-700">[{{ $ac->code }}]</span>
                                            <span class="text-slate-800 font-medium ml-1">{{ $ac->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" onclick="closeBulkModal()" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 rounded-lg text-xs font-bold text-white hover:bg-emerald-700 cursor-pointer shadow-sm">
                            Simpan Semua Terpilih
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Edit Mapping -->
    <div id="modal-edit" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-edit-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="form-edit-mapping" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-slate-800 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">✏️</span>
                            <h3 class="font-bold text-base" id="modal-edit-title">Edit Mapping Rekening</h3>
                        </div>
                        <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Sub Unit -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Sub-Unit Kerja <span class="text-rose-500">*</span></label>
                            <select id="edit-sub-unit-id" name="sub_unit_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                @foreach($subUnits as $su)
                                    <option value="{{ $su->id }}">{{ $su->name }} ({{ $su->unit?->name ?? 'Tanpa Induk' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Account Code -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor Rekening Belanja <span class="text-rose-500">*</span></label>
                            <select id="edit-account-code-id" name="account_code_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                @foreach($accountCodes as $ac)
                                    <option value="{{ $ac->id }}">[{{ $ac->code }}] {{ $ac->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Fiscal Year -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun Anggaran <span class="text-rose-500">*</span></label>
                            <input type="text" id="edit-fiscal-year" name="fiscal_year" required class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 font-mono">
                        </div>

                        <!-- Keterangan Khusus -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan Khusus / Peruntukan RSUD</label>
                            <textarea id="edit-keterangan-khusus" name="keterangan_khusus" rows="2" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-slate-800 rounded-lg text-xs font-bold text-white hover:bg-slate-900 cursor-pointer shadow-sm">
                            Perbarui Mapping
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#mapping-table').DataTable({
                responsive: true,
                language: {
                    search: "Cari Rekening / Sub-Unit:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ mapping",
                    infoEmpty: "Menampilkan 0 data",
                    infoFiltered: "(disaring dari _MAX_ total mapping)",
                    zeroRecords: "Tidak ada data mapping yang sesuai kriteria pencarian",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [7] }
                ]
            });

            // Filter Sub Unit (Kolom index 1)
            $('#filter-subunit').on('change', function() {
                table.column(1).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            // Filter Kelompok Belanja (Kolom index 4)
            $('#filter-kelompok').on('change', function() {
                table.column(4).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            // Filter Tahun (Kolom index 6)
            $('#filter-year').on('change', function() {
                table.column(6).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            // Reset Button
            $('#btn-reset-filters').on('click', function() {
                $('#filter-subunit').val('');
                $('#filter-kelompok').val('');
                $('#filter-year').val('');
                table.columns().search('').draw();
            });

            // Quick search in Bulk modal
            $('#bulk-search-box').on('keyup', function() {
                var q = $(this).val().toLowerCase();
                $('.bulk-account-item').each(function() {
                    var text = $(this).data('text');
                    if (text.indexOf(q) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });

        function openCreateModal() {
            $('#modal-create').removeClass('hidden');
        }

        function closeCreateModal() {
            $('#modal-create').addClass('hidden');
        }

        function openBulkModal() {
            $('#modal-bulk').removeClass('hidden');
        }

        function closeBulkModal() {
            $('#modal-bulk').addClass('hidden');
        }

        function toggleSelectAllBulk(select) {
            $('.bulk-account-item:visible input[type="checkbox"]').prop('checked', select);
        }

        function openEditModal(id, subUnitId, accountCodeId, year, ket) {
            var url = "{{ url('admin/sub-unit-account-codes') }}/" + id;
            $('#form-edit-mapping').attr('action', url);
            $('#edit-sub-unit-id').val(subUnitId);
            $('#edit-account-code-id').val(accountCodeId);
            $('#edit-fiscal-year').val(year);
            $('#edit-keterangan-khusus').val(ket);
            $('#modal-edit').removeClass('hidden');
        }

        function closeEditModal() {
            $('#modal-edit').addClass('hidden');
        }
    </script>
    @endpush
</x-app-layout>
