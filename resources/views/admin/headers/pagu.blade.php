<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Penetapan Pagu Per Nomor Rekening') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Tahun Anggaran: <strong class="text-gray-700">{{ $header->year }}</strong> | Periode: <strong class="text-gray-700">{{ $header->period->name }}</strong>
                </p>
            </div>
            <a href="{{ route('admin.headers.show', $header) }}"
                class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg border border-gray-300 transition">
                ← Kembali ke Detail RBA
            </a>
        </div>
    </x-slot>

    @php
        $totalRekening = $accountCodes->count();
        $ditetapkanCount = $existingPagus->count();
        $belumDitetapkanCount = $totalRekening - $ditetapkanCount;
        $totalPaguNominal = $existingPagus->sum('nominal_pagu');
        $totalUsulanAll = $requestStats->sum('total_nominal');
        $totalUnvalidatedAll = $requestStats->sum('unvalidated_count');
    @endphp

    <div class="py-8" x-data="paguManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg text-rose-800 text-sm font-medium flex items-start justify-between shadow-sm">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="leading-relaxed">
                            {!! session('error') !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Summary Cards (Reactive) -->
            <div id="summary-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Rekening</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1" x-text="stats.total_rekening">{{ $totalRekening }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Daftar kode rekening aktif</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 shadow-sm">
                    <div class="text-xs text-emerald-700 font-semibold uppercase tracking-wider">Sudah Ditetapkan</div>
                    <div class="text-2xl font-bold text-emerald-700 mt-1" x-text="stats.ditetapkan_count">{{ $ditetapkanCount }}</div>
                    <div class="text-[11px] text-emerald-600 mt-0.5">Terkunci untuk pengusulan</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-amber-100 bg-amber-50/20 shadow-sm">
                    <div class="text-xs text-amber-700 font-semibold uppercase tracking-wider">Belum Ditetapkan</div>
                    <div class="text-2xl font-bold text-amber-700 mt-1" x-text="stats.belum_ditetapkan_count">{{ $belumDitetapkanCount }}</div>
                    <div class="text-[11px] text-amber-600 mt-0.5">
                        @if($totalUnvalidatedAll > 0)
                            <span class="text-rose-600 font-bold">⚠️ {{ $totalUnvalidatedAll }} usulan pending validasi</span>
                        @else
                            <span class="text-emerald-600 font-semibold">Siap ditetapkan pagu</span>
                        @endif
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-indigo-100 bg-indigo-50/20 shadow-sm">
                    <div class="text-xs text-indigo-700 font-semibold uppercase tracking-wider">Total Pagu Ditetapkan</div>
                    <div class="text-xl font-black text-indigo-900 mt-1" x-text="stats.total_pagu_formatted">Rp {{ number_format($totalPaguNominal, 0, ',', '.') }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Total Usulan: Rp {{ number_format($totalUsulanAll, 0, ',', '.') }}</div>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-800">Daftar Rekening Belanja & Penetapan Pagu</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Simpan dan batalkan pagu secara instan tanpa memuat ulang halaman.
                            </p>
                        </div>
                        <!-- Instant Search Bar -->
                        <div class="w-full sm:w-80">
                            <div class="relative rounded-lg shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" x-model="searchQuery"
                                    placeholder="Cari kode/nama rekening..."
                                    class="block w-full rounded-lg border-gray-300 pl-9 pr-8 py-1.5 text-xs text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''"
                                    class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 text-xs">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Nomor & Nama Rekening
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Total Usulan (Operator)
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider min-w-[200px]">
                                        Status Validasi Supervisor
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Status Penetapan
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-80">
                                        Nominal Pagu & Aksi Simpan
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($accountCodes as $code)
                                    @php
                                        $isEstablished = $existingPagus->has($code->id);
                                        $paguRecord = $isEstablished ? $existingPagus->get($code->id) : null;
                                        $stats = $requestStats->get($code->id);
                                        $totalReq = $stats->total_nominal ?? 0;
                                        $totalCount = $stats->total_count ?? 0;
                                        $valCount = $stats->validated_count ?? 0;
                                        $unvalCount = $stats->unvalidated_count ?? 0;
                                        $unvalItems = $unvalidatedGrouped->get($code->id, collect());
                                        $hasUnvalidated = $unvalCount > 0;
                                        $searchKeywords = strtolower($code->code . ' ' . $code->name . ' ' . ($code->kelompokBelanja->kode ?? '') . ' ' . ($code->kelompokBelanja->name ?? ''));
                                    @endphp
                                    <tr id="row-{{ $code->id }}"
                                        data-search="{{ $searchKeywords }}"
                                        x-show="matchSearch($el)"
                                        class="hover:bg-slate-50/80 transition-colors {{ $hasUnvalidated ? 'bg-rose-50/20' : '' }}">
                                        
                                        <td class="px-4 py-3 text-sm">
                                            <div class="font-bold text-gray-900">{{ $code->code }}</div>
                                            <div class="text-xs text-gray-600">{{ $code->name }}</div>
                                            @if($code->kelompokBelanja)
                                                <div class="text-[10px] text-indigo-600 font-semibold mt-0.5">
                                                    [{{ $code->kelompokBelanja->kode }} - {{ $code->kelompokBelanja->name }}]
                                                </div>
                                            @endif
                                        </td>
                                        
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-700">
                                            Rp {{ number_format($totalReq, 0, ',', '.') }}
                                            <div class="text-[10px] text-gray-400 font-normal">({{ $totalCount }} rincian)</div>
                                        </td>

                                        <td class="px-4 py-3 text-sm">
                                            @if($totalCount === 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600">
                                                    Belum Ada Usulan
                                                </span>
                                            @elseif(!$hasUnvalidated)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    ✅ Divalidasi ({{ $valCount }}/{{ $totalCount }})
                                                </span>
                                            @else
                                                <div class="space-y-1.5">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                        ⚠️ {{ $unvalCount }} Usulan Belum Divalidasi
                                                    </span>
                                                    <div class="text-[10px] space-y-1">
                                                        @foreach($unvalItems as $item)
                                                            @php
                                                                $opName = $item->creator?->name ?? 'Operator';
                                                                $unitName = $item->submission?->unit?->name ?? '-';
                                                                $unitId = $item->submission?->unit_id;
                                                                $spvs = isset($supervisorsByUnit[$unitId]) ? $supervisorsByUnit[$unitId]->pluck('name')->toArray() : [];
                                                                $spvStr = !empty($spvs) ? implode(', ', $spvs) : 'Supervisor Unit';
                                                            @endphp
                                                            <div class="p-1.5 rounded bg-rose-50 border border-rose-200 text-rose-950 leading-tight">
                                                                <div class="font-semibold text-rose-900">• "{{ Str::limit($item->description, 35) }}"</div>
                                                                <div class="mt-0.5 text-gray-600">
                                                                    <strong>Operator:</strong> {{ $opName }} ({{ $unitName }})
                                                                </div>
                                                                <div class="text-indigo-800 font-medium">
                                                                    <strong>Supervisor:</strong> {{ $spvStr }}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                            <div id="status-badge-{{ $code->id }}">
                                                @if($isEstablished)
                                                    <div class="inline-flex flex-col items-center">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            Sudah Ditetapkan
                                                        </span>
                                                        <span class="text-[10px] text-gray-400 mt-1">
                                                            {{ $paguRecord->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                        ⏳ Belum Ditetapkan
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <form @submit.prevent="savePagu($event, {{ $code->id }})"
                                                    action="{{ route('admin.headers.pagu.store', $header) }}"
                                                    method="POST"
                                                    class="flex items-center gap-2 flex-1">
                                                    @csrf
                                                    <input type="hidden" name="account_code_id" value="{{ $code->id }}">
                                                    <div class="relative rounded-md shadow-sm flex-1">
                                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
                                                            <span class="text-gray-500 text-xs font-semibold">Rp</span>
                                                        </div>
                                                        <input type="number" name="nominal_pagu" min="0" step="any" required
                                                            id="nominal-input-{{ $code->id }}"
                                                            value="{{ $isEstablished ? (float)$paguRecord->nominal_pagu : '' }}"
                                                            placeholder="0"
                                                            class="block w-full rounded-lg border-gray-300 pl-8 pr-2 py-1.5 text-xs text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 font-semibold">
                                                    </div>
                                                    <button type="submit"
                                                        :disabled="loadingRows[{{ $code->id }}]"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 {{ $hasUnvalidated ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} disabled:opacity-50 text-white font-bold text-xs rounded-lg shadow transition ease-in-out duration-150"
                                                        title="{{ $hasUnvalidated ? 'Ada usulan belum divalidasi oleh supervisor' : 'Simpan penetapan pagu' }}">
                                                        <template x-if="loadingRows[{{ $code->id }}]">
                                                            <span class="animate-spin text-xs">⏳</span>
                                                        </template>
                                                        <template x-if="!loadingRows[{{ $code->id }}]">
                                                            <span>💾 Simpan</span>
                                                        </template>
                                                    </button>
                                                </form>

                                                <div id="batal-container-{{ $code->id }}">
                                                    @if($isEstablished)
                                                        <button type="button"
                                                            @click="cancelPagu({{ $code->id }}, '{{ $code->code }} ({{ $code->name }})', '{{ route('admin.headers.pagu.destroy', [$header, $code]) }}')"
                                                            :disabled="loadingRows[{{ $code->id }}]"
                                                            class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1.5 rounded hover:bg-rose-50 transition"
                                                            title="Batalkan penetapan pagu rekening ini">
                                                            Batal
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($hasUnvalidated)
                                                <div class="text-[10px] text-rose-600 font-medium mt-1">
                                                    ⚠️ Butuh validasi supervisor sebelum simpan
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Toast Notifications Container -->
        <div class="fixed top-5 right-5 z-50 space-y-3 w-96 max-w-full pointer-events-none">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.visible"
                    x-transition:enter="transform ease-out duration-300 transition"
                    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto p-4 rounded-xl shadow-xl border flex items-start gap-3 backdrop-blur-md"
                    :class="{
                        'bg-emerald-50/95 border-emerald-300 text-emerald-900': toast.type === 'success',
                        'bg-rose-50/95 border-rose-300 text-rose-900': toast.type === 'error',
                        'bg-amber-50/95 border-amber-300 text-amber-900': toast.type === 'warning'
                    }">
                    <span class="text-lg" x-text="toast.type === 'success' ? '✅' : (toast.type === 'error' ? '❌' : '⚠️')"></span>
                    <div class="flex-1 text-xs leading-relaxed font-medium" x-html="toast.message"></div>
                    <button type="button" @click="removeToast(toast.id)" class="text-gray-400 hover:text-gray-700 text-sm font-bold">×</button>
                </div>
            </template>
        </div>

        <!-- Supervisor Validation Warning Modal -->
        <div x-show="warningModal.show" style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="warningModal.show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="warningModal.show = false"
                    class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="warningModal.show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                    
                    <div class="bg-rose-600 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2 font-bold text-sm">
                            <span>⚠️</span>
                            <span>Validasi Supervisor Belum Lengkap</span>
                        </div>
                        <button type="button" @click="warningModal.show = false" class="text-rose-200 hover:text-white text-xl font-bold">×</button>
                    </div>

                    <div class="p-6 text-xs text-gray-700 space-y-4">
                        <div class="leading-relaxed" x-html="warningModal.message"></div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="warningModal.show = false"
                            class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-xs font-bold transition">
                            Mengerti
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function paguManager() {
            return {
                searchQuery: '',
                loadingRows: {},
                toasts: [],
                stats: {
                    total_rekening: {{ $totalRekening }},
                    ditetapkan_count: {{ $ditetapkanCount }},
                    belum_ditetapkan_count: {{ $belumDitetapkanCount }},
                    total_pagu_formatted: 'Rp {{ number_format($totalPaguNominal, 0, ',', '.') }}'
                },
                warningModal: {
                    show: false,
                    message: ''
                },
                matchSearch(el) {
                    if (!this.searchQuery || this.searchQuery.trim() === '') return true;
                    const query = this.searchQuery.toLowerCase().trim();
                    const text = el.getAttribute('data-search') || '';
                    return text.includes(query);
                },
                showToast(message, type = 'success') {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, message, type, visible: true });
                    setTimeout(() => {
                        this.removeToast(id);
                    }, 4000);
                },
                removeToast(id) {
                    const toast = this.toasts.find(t => t.id === id);
                    if (toast) {
                        toast.visible = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300);
                    }
                },
                async savePagu(event, accountId) {
                    const form = event.target;
                    const formData = new FormData(form);
                    const actionUrl = form.getAttribute('action');
                    
                    this.loadingRows[accountId] = true;

                    try {
                        const response = await fetch(actionUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            // Update Stats
                            if (result.data && result.data.stats) {
                                this.stats.ditetapkan_count = result.data.stats.ditetapkan_count;
                                this.stats.belum_ditetapkan_count = result.data.stats.belum_ditetapkan_count;
                                this.stats.total_pagu_formatted = result.data.stats.total_pagu_formatted;
                            }

                            // Update Badge in DOM
                            const badgeEl = document.getElementById('status-badge-' + accountId);
                            if (badgeEl) {
                                badgeEl.innerHTML = `
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Sudah Ditetapkan
                                        </span>
                                        <span class="text-[10px] text-gray-400 mt-1">
                                            ${result.data.updated_at}
                                        </span>
                                    </div>
                                `;
                            }

                            // Render Batal button if not exists
                            const batalContainer = document.getElementById('batal-container-' + accountId);
                            if (batalContainer && result.data.destroy_url) {
                                const row = document.getElementById('row-' + accountId);
                                const codeName = row ? row.querySelector('.font-bold').innerText : 'rekening ini';
                                batalContainer.innerHTML = `
                                    <button type="button"
                                        onclick="window.dispatchPaguCancel(${accountId}, '${codeName}', '${result.data.destroy_url}')"
                                        class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1.5 rounded hover:bg-rose-50 transition"
                                        title="Batalkan penetapan pagu rekening ini">
                                        Batal
                                    </button>
                                `;
                            }

                            this.showToast(result.message, 'success');
                        } else {
                            if (response.status === 422 && result.message) {
                                this.warningModal.message = result.message;
                                this.warningModal.show = true;
                            } else {
                                this.showToast(result.message || 'Gagal menyimpan pagu.', 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Error saving pagu:', error);
                        this.showToast('Terjadi kesalahan jaringan atau server.', 'error');
                    } finally {
                        this.loadingRows[accountId] = false;
                    }
                },
                async cancelPagu(accountId, accountName, destroyUrl) {
                    if (!confirm(`Apakah Anda yakin ingin membatalkan penetapan pagu untuk rekening ${accountName}?`)) {
                        return;
                    }

                    this.loadingRows[accountId] = true;

                    try {
                        const response = await fetch(destroyUrl, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            // Update Stats
                            if (result.data && result.data.stats) {
                                this.stats.ditetapkan_count = result.data.stats.ditetapkan_count;
                                this.stats.belum_ditetapkan_count = result.data.stats.belum_ditetapkan_count;
                                this.stats.total_pagu_formatted = result.data.stats.total_pagu_formatted;
                            }

                            // Update Badge in DOM
                            const badgeEl = document.getElementById('status-badge-' + accountId);
                            if (badgeEl) {
                                badgeEl.innerHTML = `
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                        ⏳ Belum Ditetapkan
                                    </span>
                                `;
                            }

                            // Remove Batal button
                            const batalContainer = document.getElementById('batal-container-' + accountId);
                            if (batalContainer) {
                                batalContainer.innerHTML = '';
                            }

                            this.showToast(result.message, 'success');
                        } else {
                            this.showToast(result.message || 'Gagal membatalkan pagu.', 'error');
                        }
                    } catch (error) {
                        console.error('Error cancelling pagu:', error);
                        this.showToast('Terjadi kesalahan jaringan atau server.', 'error');
                    } finally {
                        this.loadingRows[accountId] = false;
                    }
                }
            };
        }

        // Global bridge for dynamically inserted Batal buttons
        window.dispatchPaguCancel = function(accountId, accountName, destroyUrl) {
            const manager = Alpine.$data(document.querySelector('[x-data]'));
            if (manager && manager.cancelPagu) {
                manager.cancelPagu(accountId, accountName, destroyUrl);
            }
        };
    </script>
    @endpush
</x-app-layout>