<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Review Usulan RBA') }} - {{ $submission->header->year }} ({{ $submission->header->period->name }})
            </h2>
            <div class="flex space-x-2">
                @if($submission->status_submission === 'Pending Supervisor')
                    <form action="{{ route('supervisor.submissions.validate', $submission) }}" method="POST" onsubmit="return confirm('Validasi usulan ini?')">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                            Validasi & Lanjutkan
                        </button>
                    </form>
                @endif
                <a href="{{ route('supervisor.submissions.index') }}" class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Kembali</a>
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

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Usulan</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Pagu Global</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Pagu</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">PDF</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">Validasi</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">History</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" x-ref="tbody">
                                @forelse($submission->details as $detail)
                                    @php
                                        $paguValue = isset($pagus[$detail->account_code_id]) ? $pagus[$detail->account_code_id]->nominal_pagu : 0;
                                    @endphp
                                    <tr x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
                                        data-usulan="{{ $detail->nominal_request }}"
                                        data-pagu="{{ $paguValue }}"
                                        data-account-id="{{ $detail->account_code_id }}">
                                        <td class="px-4 py-2 text-sm">{{ $detail->accountCode->code }} - {{ $detail->accountCode->name }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $detail->description }}</td>
                                        <td class="px-4 py-2 text-sm text-right">Rp {{ number_format($detail->nominal_request, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            @if($paguValue > 0)
                                                Rp {{ number_format($paguValue, 0, ',', '.') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if($paguValue > 0)
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
                                                <a href="{{ Storage::url($latest->file_path) }}" target="_blank" class="text-blue-600 hover:underline text-xs">
                                                    PDF V{{ $latest->version_number }}
                                                </a>
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
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 italic">Belum ada rincian belanja.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
