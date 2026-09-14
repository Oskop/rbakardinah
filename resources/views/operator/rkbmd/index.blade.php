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

    <div class="py-8" x-data="{ activeTab: '{{ $isProposer && $incomingSubmissions->isNotEmpty() ? 'incoming' : 'mine' }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <!-- Tab Switcher Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-2 flex items-center gap-2">
                <button type="button" @click="activeTab = 'mine'"
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
                    <button type="button" @click="activeTab = 'incoming'"
                        :class="activeTab === 'incoming' ? 'bg-indigo-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium'"
                        class="flex-1 py-2.5 px-4 rounded-xl text-xs transition-all duration-150 flex items-center justify-center gap-2">
                        <span>📥</span>
                        <span>Permohonan Masuk (Perlu Ditindaklanjuti)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeTab === 'incoming' ? 'bg-indigo-700/80 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $incomingSubmissions->count() }}
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
                                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Tiket</th>
                                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Perihal Permohonan</th>
                                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Operator Tujuan</th>
                                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jumlah Item</th>
                                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($mySubmissions as $sub)
                                        <tr>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">
                                                    {{ $sub->nomor_permohonan }}
                                                </span>
                                                <div class="text-[11px] text-gray-400 mt-1">
                                                    Tahun {{ $sub->year }} • {{ $sub->created_at->format('d M Y') }}
                                                </div>
                                            </td>

                                            <td class="px-5 py-4">
                                                <div class="text-sm font-semibold text-gray-900">{{ $sub->title }}</div>
                                                @if($sub->notes)
                                                    <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $sub->notes }}</div>
                                                @endif
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <div class="text-xs font-bold text-gray-800">{{ $sub->targetOperator->name }}</div>
                                                <div class="text-[11px] text-slate-500">{{ $sub->targetOperator->unit?->name }}</div>
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700">
                                                {{ $sub->items->count() }} Jenis Barang
                                            </td>

                                            <td class="px-5 py-4 whitespace-nowrap text-center">
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

                                            <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                                <a href="{{ route('operator.rkbmd.show', $sub) }}"
                                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold border border-indigo-200 transition-colors">
                                                    Lihat Detail →
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-8 text-center text-xs text-gray-400">
                                                Belum ada permohonan RKBMD yang Anda ajukan. Silakan klik tombol "Buat Permohonan Baru".
                                            </td>
                                        </tr>
                                    @endforelse
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
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Tiket</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Pemohon / Asal Sub-Unit</th>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Perihal Permohonan</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jumlah Item</th>
                                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                            <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi Tindak Lanjut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($incomingSubmissions as $inSub)
                                            <tr>
                                                <td class="px-5 py-4 whitespace-nowrap">
                                                    <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">
                                                        {{ $inSub->nomor_permohonan }}
                                                    </span>
                                                    @if($inSub->status === 'Dialihkan')
                                                        <div class="mt-1">
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                ↪️ Alihan
                                                            </span>
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap">
                                                    <div class="text-xs font-bold text-gray-900">{{ $inSub->applicant->name }}</div>
                                                    <div class="text-[11px] text-indigo-600 font-semibold mt-0.5">
                                                        📌 {{ $inSub->subUnit?->name ?? $inSub->unit?->name }}
                                                    </div>
                                                </td>

                                                <td class="px-5 py-4">
                                                    <div class="text-sm font-semibold text-gray-900">{{ $inSub->title }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $inSub->notes }}</div>
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-center text-xs font-bold text-slate-700">
                                                    {{ $inSub->items->count() }} Item
                                                </td>

                                                <td class="px-5 py-4 whitespace-nowrap text-center">
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
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-5 py-8 text-center text-xs text-gray-400">
                                                    Tidak ada permohonan RKBMD masuk yang sedang menunggu tindakan Anda saat ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
