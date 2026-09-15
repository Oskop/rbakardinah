<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center gap-2.5">
                    <span>📦</span>
                    <span>{{ __('Rencana Kebutuhan Barang Milik Daerah (RKBMD)') }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Fasilitas permohonan pengadaan barang dari sub-unit ke Operator Pengusul RBA (Permendagri No. 108 Tahun 2016)
                </p>
            </div>
            <a href="{{ route('operator.rkbmd.create') }}"
                class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-sm hover:shadow transition-all duration-150 gap-2">
                <span>✍️</span>
                <span>Buat Permohonan Baru</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        activeTab: '{{ request('tab', $isProposer && $incomingSubmissions->isNotEmpty() ? 'incoming' : 'mine') }}',
        switchTab(tab) {
            this.activeTab = tab;
            if (window.adjustRkbmdTables) {
                window.adjustRkbmdTables();
            }
        }
    }">
        <div class="w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl shadow-sm text-sm font-semibold flex items-center gap-2">
                    <span>✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl shadow-sm text-sm font-semibold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Dedicated Filter & Search Toolbar -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Filter & Pencarian Permohonan RKBMD</h3>
                            <p class="text-[11px] text-slate-400">Saring permohonan berdasarkan kata kunci, status, dan rentang tanggal pengajuan</p>
                        </div>
                    </div>
                    <button type="button" id="btn-reset-filters"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5 transition-colors">
                        <span>🔄</span>
                        <span>Reset Semua Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Filter 1: Pencarian Bebas -->
                    <div>
                        <label for="filter-search" class="block text-xs font-bold text-slate-700 mb-1.5">Pencarian Cepat</label>
                        <div class="relative">
                            <input type="text" id="filter-search" value="{{ $search ?? '' }}" placeholder="No. tiket, judul, pemohon..."
                                class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 pl-8">
                            <span class="absolute left-2.5 top-2.5 text-slate-400">🔍</span>
                        </div>
                    </div>

                    <!-- Filter 2: Status Permohonan -->
                    <div>
                        <label for="filter-status" class="block text-xs font-bold text-slate-700 mb-1.5">Status Permohonan</label>
                        <select id="filter-status" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Status</option>
                            <option value="Diajukan" {{ ($status ?? '') === 'Diajukan' ? 'selected' : '' }}>Diajukan</option>
                            <option value="Dialihkan" {{ ($status ?? '') === 'Dialihkan' ? 'selected' : '' }}>Dialihkan</option>
                            <option value="Dipenuhi" {{ ($status ?? '') === 'Dipenuhi' ? 'selected' : '' }}>Dipenuhi</option>
                            <option value="Dipenuhi Sebagian" {{ ($status ?? '') === 'Dipenuhi Sebagian' ? 'selected' : '' }}>Dipenuhi Sebagian</option>
                            <option value="Substitusi" {{ ($status ?? '') === 'Substitusi' ? 'selected' : '' }}>Substitusi</option>
                            <option value="Optimalisasi" {{ ($status ?? '') === 'Optimalisasi' ? 'selected' : '' }}>Optimalisasi</option>
                            <option value="Ditolak" {{ ($status ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>

                    <!-- Filter 3: Tanggal Mulai -->
                    <div>
                        <label for="filter-start-date" class="block text-xs font-bold text-slate-700 mb-1.5">Mulai Tanggal</label>
                        <input type="date" id="filter-start-date" value="{{ $startDate ?? '' }}"
                            class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                    </div>

                    <!-- Filter 4: Tanggal Selesai -->
                    <div>
                        <label for="filter-end-date" class="block text-xs font-bold text-slate-700 mb-1.5">Sampai Tanggal</label>
                        <input type="date" id="filter-end-date" value="{{ $endDate ?? '' }}"
                            class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                    </div>
                </div>
            </div>

            <!-- Tab Switcher Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-2 flex items-center gap-2">
                <button type="button" @click="switchTab('mine')"
                    :class="activeTab === 'mine' ? 'bg-indigo-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium'"
                    class="flex-1 py-2.5 px-4 rounded-xl text-xs transition-all duration-150 flex items-center justify-center gap-2">
                    <span>📤</span>
                    <span>Permohonan Saya</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]"
                        :class="activeTab === 'mine' ? 'bg-indigo-700/80 text-white' : 'bg-slate-200 text-slate-700'">
                        {{ $mySubmissions->count() }}
                    </span>
                </button>

                @if($isProposer)
                    <button type="button" @click="switchTab('incoming')"
                        :class="activeTab === 'incoming' ? 'bg-indigo-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium'"
                        class="flex-1 py-2.5 px-4 rounded-xl text-xs transition-all duration-150 flex items-center justify-center gap-2">
                        <span>📥</span>
                        <span>Permohonan Masuk (Perlu Tindak Lanjut)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeTab === 'incoming' ? 'bg-indigo-700/80 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $incomingSubmissions->count() }}
                        </span>
                    </button>

                    <button type="button" @click="switchTab('forwarded')"
                        :class="activeTab === 'forwarded' ? 'bg-indigo-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium'"
                        class="flex-1 py-2.5 px-4 rounded-xl text-xs transition-all duration-150 flex items-center justify-center gap-2">
                        <span>↪️</span>
                        <span>Permohonan Dialihkan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeTab === 'forwarded' ? 'bg-indigo-700/80 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $forwardedSubmissions->count() }}
                        </span>
                    </button>
                @endif
            </div>

            <!-- TAB 1: PERMOHONAN SAYA -->
            <div x-show="activeTab === 'mine'" x-transition class="space-y-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                            <div>
                                <h3 class="font-bold text-base text-slate-800">Daftar Permohonan yang Diajukan</h3>
                                <p class="text-xs text-slate-400">Pantau proses tanggapan atau pengalihan permohonan kebutuhan barang sub-unit Anda</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table id="table-my-rkbmd" class="min-w-full divide-y divide-gray-200 stripe hover">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Tiket / Tanggal</th>
                                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Perihal Permohonan</th>
                                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Operator Tujuan</th>
                                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jumlah Item</th>
                                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($mySubmissions as $sub)
                                        <tr data-date="{{ $sub->created_at->format('Y-m-d') }}" data-status="{{ $sub->status }}">
                                            <td class="px-5 py-4 whitespace-nowrap" data-order="{{ $sub->created_at->timestamp }}">
                                                <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">
                                                    {{ $sub->nomor_permohonan }}
                                                </span>
                                                <div class="text-[11px] text-gray-500 mt-1">
                                                    Tahun {{ $sub->year }} • {{ $sub->created_at->format('d/m/Y H:i') }}
                                                </div>
                                            </td>

                                            <td class="px-5 py-4">
                                                <div class="text-sm font-semibold text-gray-900">{{ $sub->title }}</div>
                                                @if($sub->notes)
                                                    <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $sub->notes }}</div>
                                                @endif
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <div class="text-xs font-bold text-gray-800">{{ $sub->targetOperator?->name ?? '-' }}</div>
                                                <div class="text-[11px] text-slate-500">{{ $sub->targetOperator?->unit?->name }}</div>
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700">
                                                {{ $sub->items->count() }} Jenis Barang
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap text-center" data-search="{{ $sub->status }}">
                                                @php
                                                    $statusClasses = [
                                                        'Diajukan' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                        'Dialihkan' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                        'Dipenuhi' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                        'Dipenuhi Sebagian' => 'bg-teal-100 text-teal-800 border-teal-200',
                                                        'Substitusi' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                                        'Optimalisasi' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                                        'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                    ];
                                                    $cls = $statusClasses[$sub->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                                @endphp
                                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $cls }}">
                                                    {{ $sub->status }}
                                                </span>
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap text-right text-xs space-x-1.5">
                                                @if($sub->canEditSubmission(Auth::user()))
                                                    <a href="{{ route('operator.rkbmd.edit', $sub) }}"
                                                        class="inline-flex items-center px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg font-bold border border-amber-200 transition-colors gap-1">
                                                        <span>✏️</span> Edit
                                                    </a>
                                                @endif
                                                <a href="{{ route('operator.rkbmd.show', $sub) }}"
                                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold border border-indigo-200 transition-colors">
                                                    Lihat Detail →
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PERMOHONAN MASUK (KHUSUS OPERATOR PENGUSUL) -->
            @if($isProposer)
                <div x-show="activeTab === 'incoming'" x-transition class="space-y-4">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                        <div class="p-6 text-gray-900">
                            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                                <div>
                                    <h3 class="font-bold text-base text-slate-800">Daftar Permohonan Masuk ke Anda</h3>
                                    <p class="text-xs text-slate-400">Permohonan barang yang ditujukan ke Anda untuk ditanggapi atau dialihkan ke PIC lain</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table id="table-incoming-rkbmd" class="min-w-full divide-y divide-gray-200 stripe hover">
                                    <thead class="bg-gray-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Tiket / Tanggal</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Pemohon / Asal Sub-Unit</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Perihal Permohonan</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jumlah Item</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                            <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi Tindak Lanjut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($incomingSubmissions as $inSub)
                                            <tr data-date="{{ $inSub->created_at->format('Y-m-d') }}" data-status="{{ $inSub->status }}">
                                                <td class="px-5 py-4 whitespace-nowrap" data-order="{{ $inSub->created_at->timestamp }}">
                                                    <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">
                                                        {{ $inSub->nomor_permohonan }}
                                                    </span>
                                                    <div class="text-[11px] text-gray-500 mt-1">
                                                        Tahun {{ $inSub->year }} • {{ $inSub->created_at->format('d/m/Y H:i') }}
                                                    </div>
                                                    @if($inSub->status === 'Dialihkan')
                                                        <div class="mt-1">
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                ↪️ Alihan
                                                            </span>
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap">
                                                    <div class="text-xs font-bold text-gray-900">{{ $inSub->applicant?->name ?? '-' }}</div>
                                                    <div class="text-[11px] text-indigo-600 font-semibold mt-0.5">
                                                        📌 {{ $inSub->subUnit?->name ?? ($inSub->unit?->name ?? '-') }}
                                                    </div>
                                                </td>

                                                <td class="px-5 py-4">
                                                    <div class="text-sm font-semibold text-gray-900">{{ $inSub->title }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $inSub->notes }}</div>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700">
                                                    {{ $inSub->items->count() }} Item
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-center" data-search="{{ $inSub->status }}">
                                                    @php
                                                        $statusClasses = [
                                                            'Diajukan' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                            'Dialihkan' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                            'Dipenuhi' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                            'Dipenuhi Sebagian' => 'bg-teal-100 text-teal-800 border-teal-200',
                                                            'Substitusi' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                                            'Optimalisasi' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                                            'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                        ];
                                                        $cls = $statusClasses[$inSub->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                                    @endphp
                                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $cls }}">
                                                        {{ $inSub->status }}
                                                    </span>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                                    <a href="{{ route('operator.rkbmd.show', $inSub) }}"
                                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-xs transition-colors">
                                                        Telaah & Tindak Lanjut →
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PERMOHONAN DIALIHKAN (KHUSUS OPERATOR PENGUSUL) -->
                <div x-show="activeTab === 'forwarded'" x-transition class="space-y-4">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                        <div class="p-6 text-gray-900">
                            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                                <div>
                                    <h3 class="font-bold text-base text-slate-800 flex items-center gap-2">
                                        <span>↪️</span>
                                        <span>Daftar Permohonan yang Anda Alihkan</span>
                                    </h3>
                                    <p class="text-xs text-slate-400">Pantau perkembangan status berkas yang telah Anda teruskan / alihkan ke Operator Pengusul lain</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table id="table-forwarded-rkbmd" class="min-w-full divide-y divide-gray-200 stripe hover">
                                    <thead class="bg-gray-50/80">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Tiket / Tanggal</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Pemohon / Asal Sub-Unit</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Perihal Permohonan</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Dialihkan Kepada</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jumlah Item</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status Terkini</th>
                                            <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($forwardedSubmissions as $fSub)
                                            <tr data-date="{{ $fSub->created_at->format('Y-m-d') }}" data-status="{{ $fSub->status }}">
                                                <td class="px-5 py-4 whitespace-nowrap" data-order="{{ $fSub->created_at->timestamp }}">
                                                    <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">
                                                        {{ $fSub->nomor_permohonan }}
                                                    </span>
                                                    <div class="text-[11px] text-gray-500 mt-1">
                                                        Tahun {{ $fSub->year }} • {{ $fSub->created_at->format('d/m/Y H:i') }}
                                                    </div>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap">
                                                    <div class="text-xs font-bold text-gray-900">{{ $fSub->applicant?->name ?? '-' }}</div>
                                                    <div class="text-[11px] text-indigo-600 font-semibold mt-0.5">
                                                        📌 {{ $fSub->subUnit?->name ?? ($fSub->unit?->name ?? '-') }}
                                                    </div>
                                                </td>

                                                <td class="px-5 py-4">
                                                    <div class="text-sm font-semibold text-gray-900">{{ $fSub->title }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $fSub->notes }}</div>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap">
                                                    <div class="text-xs font-bold text-amber-900 flex items-center gap-1">
                                                        <span>👤</span>
                                                        <span>{{ $fSub->targetOperator?->name ?? 'Operator Lain' }}</span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                                        {{ $fSub->targetOperator?->unit?->name }} {{ $fSub->targetOperator?->subUnit ? '(' . $fSub->targetOperator->subUnit->name . ')' : '' }}
                                                    </div>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700">
                                                    {{ $fSub->items->count() }} Item
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-center" data-search="{{ $fSub->status }}">
                                                    @php
                                                        $statusClasses = [
                                                            'Diajukan' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                            'Dialihkan' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                            'Dipenuhi' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                            'Dipenuhi Sebagian' => 'bg-teal-100 text-teal-800 border-teal-200',
                                                            'Substitusi' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                                            'Optimalisasi' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                                            'Ditolak' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                        ];
                                                        $cls = $statusClasses[$fSub->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                                    @endphp
                                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $cls }}">
                                                        {{ $fSub->status }}
                                                    </span>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                                    <a href="{{ route('operator.rkbmd.show', $fSub) }}"
                                                        class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold border border-slate-300 transition-colors gap-1 shadow-2xs">
                                                        <span>👁️</span>
                                                        <span>Lihat Detail & Pantau →</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.3/css/dataTables.tailwindcss.css">
        <style>
            div.dt-container div.dt-layout-row {
                margin-bottom: 0.75rem;
            }
            .dt-search {
                display: none; /* Menyembunyikan input search bawaan, digantikan oleh filter toolbar terpadu */
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.0.3/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/2.0.3/js/dataTables.tailwindcss.js"></script>
        <script>
            $(document).ready(function() {
                const dataTableLang = {
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ s/d _END_ dari total _TOTAL_ permohonan",
                    infoEmpty: "Menampilkan 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    emptyTable: "Belum ada data permohonan RKBMD pada daftar ini.",
                    zeroRecords: "Tidak ditemukan data permohonan yang sesuai filter / pencarian.",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                };

                const tableMy = $('#table-my-rkbmd').DataTable({
                    responsive: true,
                    order: [[0, 'desc']], // Urutkan berdasarkan kolom 0 (Tanggal/No. Tiket) descending
                    language: dataTableLang,
                    columnDefs: [
                        { orderable: false, targets: [5] } // Kolom Aksi tidak dapat disortir
                    ]
                });

                let tableIncoming = null;
                if ($('#table-incoming-rkbmd').length) {
                    tableIncoming = $('#table-incoming-rkbmd').DataTable({
                        responsive: true,
                        order: [[0, 'desc']],
                        language: dataTableLang,
                        columnDefs: [
                            { orderable: false, targets: [5] }
                        ]
                    });
                }

                let tableForwarded = null;
                if ($('#table-forwarded-rkbmd').length) {
                    tableForwarded = $('#table-forwarded-rkbmd').DataTable({
                        responsive: true,
                        order: [[0, 'desc']],
                        language: dataTableLang,
                        columnDefs: [
                            { orderable: false, targets: [6] }
                        ]
                    });
                }

                // Custom DataTables Filter: Date Range & Status
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const rowNode = settings.aoData[dataIndex].nTr;
                    if (!rowNode) return true;

                    const rowDate = $(rowNode).attr('data-date');
                    const rowStatus = $(rowNode).attr('data-status');

                    const minDate = $('#filter-start-date').val();
                    const maxDate = $('#filter-end-date').val();
                    const selectedStatus = $('#filter-status').val();

                    if (minDate && rowDate && rowDate < minDate) {
                        return false;
                    }
                    if (maxDate && rowDate && rowDate > maxDate) {
                        return false;
                    }
                    if (selectedStatus && rowStatus !== selectedStatus) {
                        return false;
                    }

                    return true;
                });

                function redrawAllTables() {
                    tableMy.draw();
                    if (tableIncoming) tableIncoming.draw();
                    if (tableForwarded) tableForwarded.draw();
                }

                // Event Listeners: Filter Tanggal & Status
                $('#filter-start-date, #filter-end-date, #filter-status').on('change', function() {
                    redrawAllTables();
                });

                // Event Listener: Pencarian Cepat
                $('#filter-search').on('keyup input', function() {
                    const searchVal = $(this).val();
                    tableMy.search(searchVal).draw();
                    if (tableIncoming) tableIncoming.search(searchVal).draw();
                    if (tableForwarded) tableForwarded.search(searchVal).draw();
                });

                // Inisialisasi awal pencarian jika sudah ada nilai search dari URL
                if ($('#filter-search').val()) {
                    const initialSearch = $('#filter-search').val();
                    tableMy.search(initialSearch).draw();
                    if (tableIncoming) tableIncoming.search(initialSearch).draw();
                    if (tableForwarded) tableForwarded.search(initialSearch).draw();
                }

                // Tombol Reset Filter
                $('#btn-reset-filters').on('click', function() {
                    $('#filter-search').val('');
                    $('#filter-status').val('');
                    $('#filter-start-date').val('');
                    $('#filter-end-date').val('');

                    tableMy.search('');
                    if (tableIncoming) tableIncoming.search('');
                    if (tableForwarded) tableForwarded.search('');

                    redrawAllTables();
                });

                // Fungsi global penyesuaian lebar kolom saat tab Alpine berubah
                window.adjustRkbmdTables = function() {
                    setTimeout(() => {
                        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    }, 50);
                };
            });
        </script>
    @endpush
</x-app-layout>
