<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Workboard RBA') }} - {{ $submission->header->year }} ({{ $submission->header->period->name }})
            </h2>
            <div class="flex space-x-2">
                @php
                    $hasOpenPagu = \App\Models\AccountCode::whereDoesntHave('accountPagus', function($q) use ($submission) {
                        $q->where('rba_header_id', $submission->rba_header_id)->where('nominal_pagu', '>', 0);
                    })->exists();
                @endphp
                
                @if($submission->header->status_global === 'Draft' || $hasOpenPagu)
                    @if(!empty($submission->background))
                        <a href="{{ route('operator.details.create', ['submission_id' => $submission->id]) }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                            + Tambah Rincian
                        </a>
                    @else
                        <button disabled
                            title="Silakan isi data Latar Belakang terlebih dahulu"
                            class="bg-gray-400 text-white font-bold py-2 px-4 rounded text-sm cursor-not-allowed">
                            + Tambah Rincian
                        </button>
                    @endif
                @endif
                <a href="{{ route('operator.submissions.index') }}"
                    class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Kembali</a>
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

            <!-- Latar Belakang Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="font-bold text-lg text-gray-800 mb-4">Latar Belakang RBA</h3>
                    
                    @if(empty($submission->background))
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Anda belum mengisi data Latar Belakang. Anda **wajib** mengisi Latar Belakang terlebih dahulu sebelum dapat menambahkan rincian belanja.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('operator.submissions.update-background', $submission) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <textarea name="background" rows="4" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                placeholder="Tuliskan latar belakang RBA secara lengkap di sini..." 
                                {{ $submission->header->status_global === 'Locked' ? 'readonly' : '' }} required>{{ old('background', $submission->background) }}</textarea>
                        </div>
                        @if($submission->header->status_global !== 'Locked')
                            <div class="flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow-md transition duration-150 ease-in-out">
                                    {{ empty($submission->background) ? 'Simpan Latar Belakang' : 'Perbarui Latar Belakang' }}
                                </button>
                            </div>
                        @endif
                    </form>
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
                                <h3 class="font-bold text-lg">Rincian Biaya</h3>
                                <div class="relative">
                                    <input x-model="search" type="text" placeholder="Cari rincian..." 
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-64 pl-8">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-6 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                @if($submission->header->status_global !== 'Draft')
                                    <span class="text-[10px] bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold uppercase whitespace-nowrap">
                                        Submissions Locked
                                    </span>
                                @endif
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

                        <table class="min-w-full divide-y divide-gray-200" id="details-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Usulan (Unit)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Pagu Global</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Pagu</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">PDF</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
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
                                        <td class="px-4 py-2 text-sm">
                                            {{ $detail->accountCode->code }} - {{ $detail->accountCode->name }}
                                            @if($detail->is_rejected)
                                                <div class="mt-1 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                                    <strong>Alasan Penolakan:</strong><br>
                                                    {{ $detail->rejection_reason }}
                                                </div>
                                            @endif
                                        </td>
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
                                                @endphp
                                                @if($total > $paguValue)
                                                    <span class="text-red-600 font-bold text-xs">MELEBIHI PAGU</span>
                                                    <div class="text-[10px] text-red-500 font-medium">(Over: Rp {{ number_format($total - $paguValue, 0, ',', '.') }})</div>
                                                @else
                                                    <span class="text-green-600 font-semibold text-xs whitespace-nowrap">✓ Tercover</span>
                                                @endif
                                            @else
                                                <span class="text-yellow-600 italic text-xs">Pagu Belum Diset</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">
                                            @php $latest = $detail->latestAttachment(); @endphp
                                            @if($latest)
                                                <a href="{{ Storage::url($latest->file_path) }}" target="_blank"
                                                    class="text-blue-600 hover:underline text-xs">
                                                    PDF V{{ $latest->version_number }}
                                                </a>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if($detail->is_validated)
                                                <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-[9px] font-black uppercase">Valid</span>
                                            @elseif($detail->is_rejected)
                                                <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-[9px] font-black uppercase">Tolak</span>
                                            @elseif($detail->is_submitted)
                                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[9px] font-black uppercase">Ajuan</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-gray-100 text-gray-800 rounded-full text-[9px] font-black uppercase">Draft</span>
                                            @endif
                                        </td>                                        <td class="px-4 py-2 text-sm">
                                            <div class="flex flex-col space-y-2">
                                                @php
                                                    $isItemLockedByPagu = $paguValue > 0;
                                                    $isExceeding = $detail->isExceedingPagu();
                                                    $hasRevision = $detail->hasUploadedRevision();
                                                @endphp

                                                @if(!$isItemLockedByPagu && (!$detail->is_submitted || $detail->is_rejected))
                                                    <div class="flex space-x-2">
                                                        <a href="{{ route('operator.details.edit', $detail) }}"
                                                            class="text-indigo-600 hover:text-indigo-900 text-[10px] font-bold border border-indigo-200 px-2 py-1 rounded bg-indigo-50">Edit</a>
                                                        
                                                        <form action="{{ route('operator.details.submit-item', $detail) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" 
                                                                class="text-green-600 hover:text-green-900 text-[10px] font-bold border border-green-200 px-2 py-1 rounded bg-green-50">
                                                                Ajukan
                                                            </button>
                                                        </form>

                                                        <form action="{{ route('operator.details.destroy', $detail) }}" method="POST" onsubmit="return confirm('Hapus rincian ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                class="text-red-600 hover:text-red-900 text-[10px] font-bold border border-red-200 px-2 py-1 rounded bg-red-50">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>

                                                    <form action="{{ route('operator.details.upload-version', $detail) }}"
                                                        method="POST" enctype="multipart/form-data"
                                                        class="flex items-center space-x-1 border-t pt-2 mt-2">
                                                        @csrf
                                                        <input type="file" name="attachment" class="text-[10px] w-24" required>
                                                        <button type="submit"
                                                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-1 px-2 rounded text-[10px] font-bold">Revisi</button>
                                                    </form>
                                                @else
                                                    <div class="flex flex-col items-center space-y-2">
                                                        @if($isItemLockedByPagu)
                                                            @if($isExceeding)
                                                                @if(!$hasRevision)
                                                                    <span class="px-2 py-0.5 bg-red-100 text-red-800 border border-red-200 rounded text-[9px] font-black uppercase text-center block">⚠ Wajib Upload PDF Baru</span>
                                                                    
                                                                    <form action="{{ route('operator.details.upload-version', $detail) }}"
                                                                        method="POST" enctype="multipart/form-data"
                                                                        class="flex items-center space-x-1 border-t pt-2 mt-2">
                                                                        @csrf
                                                                        <input type="file" name="attachment" class="text-[10px] w-24" required>
                                                                        <button type="submit"
                                                                            class="bg-blue-600 hover:bg-blue-700 text-white py-1 px-2 rounded text-[10px] font-bold">Upload</button>
                                                                    </form>
                                                                @else
                                                                    <span class="px-2 py-0.5 bg-green-100 text-green-800 border border-green-200 rounded text-[9px] font-black uppercase text-center block">✓ PDF Penyesuaian Diunggah</span>
                                                                    
                                                                    @if(!$detail->is_submitted || $detail->is_rejected)
                                                                        <form action="{{ route('operator.details.submit-item', $detail) }}" method="POST" class="mt-1">
                                                                            @csrf
                                                                            <button type="submit" 
                                                                                class="text-green-600 hover:text-green-900 text-[10px] font-bold border border-green-200 px-3 py-1 rounded bg-green-50">
                                                                                Ajukan
                                                                            </button>
                                                                        </form>
                                                                    @endif

                                                                    <form action="{{ route('operator.details.upload-version', $detail) }}"
                                                                        method="POST" enctype="multipart/form-data"
                                                                        class="flex items-center space-x-1 border-t pt-2 mt-2">
                                                                        @csrf
                                                                        <input type="file" name="attachment" class="text-[10px] w-24" required>
                                                                        <button type="submit"
                                                                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-1 px-2 rounded text-[10px] font-bold">Revisi</button>
                                                                    </form>
                                                                @endif
                                                            @else
                                                                <span class="text-[9px] bg-red-50 text-red-600 px-1 py-0.5 rounded border border-red-100 font-bold uppercase italic">Pagu Locked</span>
                                                                <span class="text-gray-400 text-[10px] italic font-medium text-center">Read Only</span>
                                                            @endif
                                                        @else
                                                            <span class="text-gray-400 text-[10px] italic font-medium text-center">Read Only</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 italic">Belum ada rincian belanja.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>

            <!-- Dokumen Pendukung (KAK, RAK, RTP) Section -->
            @php
                $isLocked = $submission->header->status_global === 'Locked';
                $docsMap = $submission->documents->keyBy('type');
            @endphp

            @if($isLocked)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="font-bold text-lg text-gray-800 mb-4">Dokumen Realisasi & Penyesuaian (KAK, RAK, RTP)</h3>
                        <p class="text-xs text-gray-500 mb-4">RBA telah dikunci/pagu ditetapkan. Silakan unggah dokumen KAK, RAK, dan RTP versi penyesuaian Anda.</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach(['KAK', 'RAK', 'RTP'] as $docType)
                                @php
                                    $doc = $docsMap->get($docType);
                                    $latestVersion = $doc ? $doc->latestVersion : null;
                                @endphp
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-bold text-sm text-gray-700">Dokumen {{ $docType }}</h4>
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
                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($latestVersion->file_path) }}" target="_blank"
                                                    class="text-indigo-600 hover:underline text-xs font-semibold inline-flex items-center space-x-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <span>Unduh Versi Terbaru (V{{ $latestVersion->version_number }})</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <form action="{{ route('operator.submissions.documents.upload', $submission) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="type" value="{{ $docType }}">
                                            <div class="flex flex-col space-y-2">
                                                <input type="file" name="attachment" accept="application/pdf" class="text-xs w-full" required>
                                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1.5 px-3 rounded text-xs shadow">
                                                    {{ $latestVersion ? 'Unggah Revisi Baru' : 'Unggah Dokumen' }}
                                                </button>
                                            </div>
                                        </form>

                                        @if($doc)
                                            <div class="mt-3 text-center">
                                                <a href="{{ route('submissions.documents.history', ['submission' => $submission->id, 'type' => $docType]) }}" 
                                                    class="text-[10px] text-gray-500 hover:text-indigo-600 font-semibold underline">
                                                    Lihat Riwayat Versi
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>