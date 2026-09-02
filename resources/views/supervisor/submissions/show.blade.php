<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Review Usulan RBA') }} - {{ $submission->header->year }} ({{ $submission->header->period->name }})
            </h2>
            <div class="flex space-x-2 items-center" x-data="{ openPrintModal: false, printType: 'usulan', operatorScope: 'all', selectedOperators: [] }">
                <!-- Button Trigger Cetak Rincian -->
                <button @click="openPrintModal = true" type="button"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded text-sm inline-flex items-center gap-1.5 shadow-sm transition-all">
                    <span>🖨️ Cetak Rincian Usulan / RBA Final</span>
                </button>

                @if($submission->status_submission === 'Pending Supervisor')
                    <form action="{{ route('supervisor.submissions.validate', $submission) }}" method="POST" onsubmit="return confirm('Validasi usulan ini?')">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                            Validasi & Lanjutkan
                        </button>
                    </form>
                @endif
                <a href="{{ route('supervisor.submissions.index') }}" class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Kembali</a>

                <!-- Modal Konfigurasi Cetak Supervisor -->
                <div x-show="openPrintModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="openPrintModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="openPrintModal = false"></div>

                        <div x-show="openPrintModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">
                            
                            <form :action="printType === 'final' ? '{{ route('supervisor.submissions.print-preview-final', $submission->id) }}' : '{{ route('supervisor.submissions.print-preview', $submission->id) }}'" method="GET" target="_blank" @submit="openPrintModal = false">
                                <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-4 flex items-center justify-between text-white">
                                    <h3 class="text-base font-bold flex items-center gap-2">
                                        <span>🖨️ Opsi Konfigurasi Cetak Supervisor</span>
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

                                    <!-- 3. Opsi Filter Operator -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">3. Filter Operator Penyusun</label>
                                        <div class="space-y-2 mb-3">
                                            <label class="flex items-center gap-2 text-xs font-semibold text-gray-800 cursor-pointer">
                                                <input type="radio" x-model="operatorScope" value="all" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Cetak Semua Operator (Akumulasi Unit)</span>
                                            </label>
                                            <label class="flex items-center gap-2 text-xs font-semibold text-gray-800 cursor-pointer">
                                                <input type="radio" x-model="operatorScope" value="selected" class="text-emerald-600 focus:ring-emerald-500">
                                                <span>Pilih Operator Spesifik (1 atau Banyak)</span>
                                            </label>
                                        </div>

                                        <!-- List Checkbox Operator (Hanya jika operatorScope == 'selected') -->
                                        <div x-show="operatorScope === 'selected'" x-transition class="bg-gray-50 border border-gray-200 rounded-xl p-3 max-h-48 overflow-y-auto space-y-2">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-200 text-[11px] font-bold text-gray-500">
                                                <span>PILIH OPERATOR:</span>
                                                <div class="space-x-2">
                                                    <button type="button" @click="selectedOperators = [{{ $operators->pluck('id')->join(',') }}]" class="text-emerald-600 hover:underline">Pilih Semua</button>
                                                    <span>|</span>
                                                    <button type="button" @click="selectedOperators = []" class="text-red-600 hover:underline">Reset</button>
                                                </div>
                                            </div>
                                            @forelse($operators as $op)
                                                <label class="flex items-center gap-2 text-xs text-gray-700 py-1 px-1.5 hover:bg-white rounded cursor-pointer transition-colors">
                                                    <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}" x-model="selectedOperators" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span><strong>{{ $op->name }}</strong> <span class="text-gray-400">({{ $op->email }})</span></span>
                                                </label>
                                            @empty
                                                <p class="text-xs text-gray-500 italic text-center py-2">Belum ada operator terdaftar di unit ini.</p>
                                            @endforelse
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded text-sm font-semibold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm font-semibold shadow-sm animate-pulse">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Latar Belakang RBA per Operator Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80 mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-extrabold text-base text-gray-900">Latar Belakang RBA per Operator</h3>
                                <p class="text-xs text-slate-500">Rincian justifikasi dan latar belakang usulan belanja dari masing-masing operator aktif di unit ini</p>
                            </div>
                        </div>
                        <div class="text-xs font-semibold px-3 py-1 bg-slate-100 text-slate-700 rounded-full">
                            {{ $operators->count() }} Operator Aktif
                        </div>
                    </div>

                    <div class="space-y-4">
                        @forelse($operators as $op)
                            @php
                                $opBg = isset($operatorBackgrounds) ? $operatorBackgrounds->get($op->id) : null;
                            @endphp
                            <div class="border rounded-xl overflow-hidden transition-all {{ $opBg ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50' }}">
                                <div class="p-4 flex items-center justify-between bg-white border-b border-inherit">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 font-black text-sm flex items-center justify-center shadow-inner">
                                            {{ strtoupper(substr($op->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                                <span>{{ $op->name }}</span>
                                                @if($op->nip)
                                                    <span class="text-[11px] font-normal font-mono text-gray-500">({{ $op->nip }})</span>
                                                @endif
                                            </h4>
                                            <p class="text-[11px] text-gray-400">{{ $op->email }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        @if($opBg)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                                <span>✓</span>
                                                <span>Latar Belakang Terisi</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                                <span>⚠️</span>
                                                <span>Belum Mengisi</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-4">
                                    @if($opBg)
                                        <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed bg-white p-3.5 rounded-lg border border-slate-200">
                                            {{ $opBg->background }}
                                        </div>
                                        @if($opBg->updated_at)
                                            <div class="mt-2 text-[11px] text-gray-400 text-right">
                                                Terakhir diperbarui: {{ $opBg->updated_at->format('d M Y, H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 italic py-2">
                                            Operator ini belum mengisi data latar belakang untuk periode usulan ini.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic text-center py-4">Belum ada operator aktif terdaftar pada unit kerja ini.</p>
                        @endforelse

                        {{-- Fallback jika ada Latar Belakang lama yang tersimpan langsung di submission --}}
                        @if((!isset($operatorBackgrounds) || $operatorBackgrounds->isEmpty()) && !empty($submission->background))
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                                <h5 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-1">Latar Belakang Unit (Data Tersimpan):</h5>
                                <p class="text-sm text-blue-950 whitespace-pre-wrap">{{ $submission->background }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div x-data="{ 
                        search: '',
                        formatIDR(val) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        },
                        get totals() {
                            let rows = Array.from(this.$refs.tbody.querySelectorAll('tr[data-usulan]'));
                            let filtered = rows.filter(tr => {
                                if (!this.search) return true;
                                return tr.innerText.toLowerCase().includes(this.search.toLowerCase());
                            });
                            
                            let totalUsulan = filtered.reduce((acc, tr) => acc + parseFloat(tr.dataset.usulan || 0), 0);
                            
                            let accountsSeen = new Set();
                            let totalPagu = filtered.reduce((acc, tr) => {
                                let id = tr.dataset.accountId;
                                if (id && !accountsSeen.has(id)) {
                                    accountsSeen.add(id);
                                    return acc + parseFloat(tr.dataset.pagu || 0);
                                }
                                return acc;
                            }, 0);
                            
                            return { usulan: totalUsulan, pagu: totalPagu };
                        }
                    }">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <h3 class="font-bold text-lg text-gray-800">Daftar Rincian Belanja (Review)</h3>
                                <div class="relative">
                                    <input x-model="search" type="text" placeholder="Cari rincian..." 
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-64 pl-8">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-6 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <div class="flex space-x-6">
                                    <div class="text-right">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Usulan</p>
                                        <p class="text-lg font-black text-indigo-600 leading-none mt-1" x-text="formatIDR(totals.usulan)"></p>
                                    </div>
                                    <div class="text-right border-l border-gray-300 pl-6">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Pagu</p>
                                        <p class="text-lg font-black text-green-600 leading-none mt-1" x-text="formatIDR(totals.pagu)"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm my-4">
                            <table class="min-w-[1200px] w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">AWAL</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Volume</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Pagu No.Rek.</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Pagu</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">PDF</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">Validasi</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">History</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" x-ref="tbody">
                                @forelse($submission->details as $detail)
                                    @php
                                        $isPaguEstablished = isset($pagus[$detail->account_code_id]);
                                        $paguValue = $isPaguEstablished ? (float)$pagus[$detail->account_code_id]->nominal_pagu : 0;
                                        $awalValue = isset($previousPagus[$detail->account_code_id]) ? (float)$previousPagus[$detail->account_code_id]->nominal_pagu : 0;
                                    @endphp
                                    <tr x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
                                        data-usulan="{{ $detail->nominal_request }}"
                                        data-pagu="{{ $paguValue }}"
                                        data-account-id="{{ $detail->account_code_id }}">
                                        <td class="px-4 py-2 text-sm">{{ $detail->accountCode->code }} - {{ $detail->accountCode->name }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            {{ $detail->description }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right font-semibold text-gray-700">
                                            @if($awalValue > 0)
                                                Rp {{ number_format($awalValue, 0, ',', '.') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            {{ number_format($detail->volume, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            {{ $detail->satuan }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">Rp {{ number_format($detail->nominal_request, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            @if($isPaguEstablished)
                                                Rp {{ number_format($paguValue, 0, ',', '.') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if($isPaguEstablished)
                                                @php 
                                                    $total = $headerTotals[$detail->account_code_id]->total ?? 0;
                                                    $isExceeding = $total > $paguValue;
                                                    $hasRevision = $detail->hasUploadedRevision();
                                                @endphp
                                                @if($isExceeding)
                                                    <span class="text-red-600 font-bold text-xs">⚠️ OVER</span>
                                                    @if(!$hasRevision)
                                                        <div class="text-[9px] text-red-500 font-semibold">(⚠ Butuh PDF Baru)</div>
                                                    @else
                                                        <div class="text-[9px] text-green-600 font-semibold">(✓ PDF Penyesuaian)</div>
                                                    @endif
                                                @else
                                                    <span class="text-green-600 text-xs font-medium">Tercover</span>
                                                @endif
                                            @else
                                                <span class="text-yellow-600 italic text-xs">Pagu Belum Diset</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">
                                            @php $latest = $detail->latestAttachment(); @endphp
                                            @if($latest)
                                                @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($latest->file_path))
                                                    <a href="{{ Storage::url($latest->file_path) }}" target="_blank" class="text-blue-600 hover:underline text-xs font-bold">
                                                        PDF V{{ $latest->version_number }}
                                                    </a>
                                                @else
                                                    <span class="text-amber-600 font-bold text-[10px] bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded cursor-help" title="File PDF fisik tidak ditemukan di storage server. Minta Operator unggah ulang.">
                                                        ⚠️ File Tidak Ditemukan (V{{ $latest->version_number }})
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">
                                            <div class="flex flex-col items-center space-y-1">
                                                <div class="flex space-x-1">
                                                    <!-- Validation Toggle -->
                                                    @if($detail->isExceedingPagu() && !$detail->hasUploadedRevision())
                                                        <button type="button" disabled class="inline-flex items-center px-2 py-1 border rounded text-[10px] font-bold bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed font-medium text-center" title="Operator belum mengunggah PDF revisi baru">
                                                            ⏳ Valid (Butuh PDF Baru)
                                                        </button>
                                                    @else
                                                        <form action="{{ route('supervisor.details.toggle-validation', $detail) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-2 py-1 border rounded text-[10px] font-bold transition
                                                                {{ $detail->is_validated 
                                                                    ? 'bg-green-100 text-green-800 border-green-300 hover:bg-green-200' 
                                                                    : 'bg-gray-100 text-gray-800 border-gray-300 hover:bg-gray-200' }}">
                                                                {{ $detail->is_validated ? '✅ Valid' : '⏳ Valid' }}
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <!-- Rejection Button -->
                                                    @if(!$detail->is_validated)
                                                        <form action="{{ route('supervisor.details.reject', $detail) }}" method="POST" id="reject-form-{{ $detail->id }}">
                                                            @csrf
                                                            <input type="hidden" name="rejection_reason" id="rejection-reason-{{ $detail->id }}">
                                                            <button type="button" onclick="confirmRejection({{ $detail->id }})" 
                                                                class="inline-flex items-center px-2 py-1 border rounded text-[10px] font-bold transition
                                                                {{ $detail->is_rejected 
                                                                    ? 'bg-red-200 text-red-900 border-red-400' 
                                                                    : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}">
                                                                {{ $detail->is_rejected ? '❌ Ditolak' : '✖ Tolak' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>

                                                @if($detail->is_validated)
                                                    <div class="text-[9px] text-green-700 font-medium">
                                                        Divalidasi: {{ $detail->validator?->name }}
                                                    </div>
                                                @elseif($detail->is_rejected)
                                                    <div class="text-[9px] text-red-700 font-medium max-w-[120px] truncate" title="{{ $detail->rejection_reason }}">
                                                        Alasan: {{ $detail->rejection_reason }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">
                                            <a href="{{ route('history.show', $detail) }}" class="text-gray-600 hover:text-indigo-600 text-[10px] font-bold border rounded px-2 py-1">
                                                📜 Logs
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-8 text-center text-gray-500 italic">Belum ada rincian belanja.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dokumen Pendukung (KAK, RAK, RTP) Section for Supervisor per Operator -->
            @php
                $isLocked = $submission->header->status_global === 'Locked';
            @endphp

            @if($isLocked)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="font-bold text-lg text-gray-800 mb-2">Dokumen Realisasi & Penyesuaian (KAK, RAK, RTP) Per Operator</h3>
                        <p class="text-xs text-gray-500 mb-6">Berikut adalah dokumen KAK, RAK, dan RTP yang diunggah oleh masing-masing Operator (Unit Bawahan) di bawah naungan unit Anda.</p>

                        @forelse($operators as $op)
                            @php
                                $opDocsMap = isset($documents[$op->id]) ? $documents[$op->id]->keyBy('type') : collect();
                            @endphp
                            <div class="mb-8 p-5 bg-gray-50/70 rounded-xl border border-gray-200">
                                <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-gray-200">
                                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-gray-800">{{ $op->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $op->email }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    @foreach(['KAK', 'RAK', 'RTP'] as $docType)
                                        @php
                                            $doc = $opDocsMap->get($docType);
                                            $latestVersion = $doc ? $doc->latestVersion : null;
                                        @endphp
                                        <div class="bg-white p-4 rounded-lg border border-gray-200 flex flex-col justify-between shadow-sm">
                                            <div>
                                                <div class="flex justify-between items-center mb-3">
                                                    <h5 class="font-bold text-sm text-gray-700">Dokumen {{ $docType }}</h5>
                                                    @if($latestVersion)
                                                        <span class="bg-green-100 text-green-800 text-[10px] px-2 py-0.5 rounded-full font-bold">
                                                            V{{ $latestVersion->version_number }}
                                                        </span>
                                                    @else
                                                        <span class="bg-red-100 text-red-800 text-[10px] px-2 py-0.5 rounded-full font-bold">
                                                            Belum Diunggah
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($latestVersion)
                                                    <div class="mb-4">
                                                        @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($latestVersion->file_path))
                                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($latestVersion->file_path) }}" target="_blank"
                                                                class="text-indigo-600 hover:underline text-xs font-semibold inline-flex items-center space-x-1">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                </svg>
                                                                <span>Unduh Versi Terbaru (V{{ $latestVersion->version_number }})</span>
                                                            </a>
                                                        @else
                                                            <span class="text-amber-600 font-bold text-xs bg-amber-50 border border-amber-200 px-2 py-1 rounded inline-flex items-center space-x-1 cursor-help" title="File PDF fisik tidak ditemukan di storage server. Minta Operator unggah ulang.">
                                                                <span>⚠️ File fisik tidak ditemukan</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        Diunggah oleh: <strong class="text-gray-700">{{ $latestVersion->uploader->name }}</strong>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        Pada: {{ $latestVersion->created_at->timezone('Asia/Jakarta')->format('d M Y - H:i') }} WIB
                                                    </div>
                                                @else
                                                    <p class="text-xs text-gray-400 italic">Operator belum mengunggah dokumen ini.</p>
                                                @endif
                                            </div>

                                            @if($doc)
                                                <div class="mt-4 pt-3 border-t text-center">
                                                    <a href="{{ route('submissions.documents.history', ['submission' => $submission->id, 'type' => $docType, 'user_id' => $op->id]) }}" 
                                                        class="text-[10px] text-gray-500 hover:text-indigo-600 font-semibold underline">
                                                        Lihat Riwayat Versi
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic">Belum ada Operator di bawah unit kerja ini.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function confirmRejection(id) {
            let reason = prompt("Masukkan alasan penolakan rincian ini:");
            if (reason && reason.trim() !== "") {
                document.getElementById('rejection-reason-' + id).value = reason;
                document.getElementById('reject-form-' + id).submit();
            } else if (reason !== null) {
                alert("Alasan penolakan wajib diisi.");
            }
        }
    </script>
</x-app-layout>
