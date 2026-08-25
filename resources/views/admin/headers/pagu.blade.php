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

    <div class="py-8">
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

            <!-- Summary Cards -->
            @php
                $totalRekening = $accountCodes->count();
                $ditetapkanCount = $existingPagus->count();
                $belumDitetapkanCount = $totalRekening - $ditetapkanCount;
                $totalPaguNominal = $existingPagus->sum('nominal_pagu');
                $totalUsulanAll = $requestStats->sum('total_nominal');
                $totalUnvalidatedAll = $requestStats->sum('unvalidated_count');
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Rekening</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totalRekening }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Daftar kode rekening aktif</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 shadow-sm">
                    <div class="text-xs text-emerald-700 font-semibold uppercase tracking-wider">Sudah Ditetapkan</div>
                    <div class="text-2xl font-bold text-emerald-700 mt-1">{{ $ditetapkanCount }}</div>
                    <div class="text-[11px] text-emerald-600 mt-0.5">Terkunci untuk pengusulan</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-amber-100 bg-amber-50/20 shadow-sm">
                    <div class="text-xs text-amber-700 font-semibold uppercase tracking-wider">Belum Ditetapkan</div>
                    <div class="text-2xl font-bold text-amber-700 mt-1">{{ $belumDitetapkanCount }}</div>
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
                    <div class="text-xl font-black text-indigo-900 mt-1">Rp {{ number_format($totalPaguNominal, 0, ',', '.') }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Total Usulan: Rp {{ number_format($totalUsulanAll, 0, ',', '.') }}</div>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <h3 class="text-base font-bold text-gray-800">Daftar Rekening Belanja & Penetapan Pagu</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Setiap baris rekening memiliki status dan tombol simpan tersendiri. 
                            <strong>Prasyarat:</strong> Seluruh usulan dari Operator pada rekening tersebut wajib telah divalidasi oleh masing-masing Supervisor sebelum pagu dapat disimpan.
                        </p>
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
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition-colors {{ $hasUnvalidated ? 'bg-rose-50/20' : '' }}">
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
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <form action="{{ route('admin.headers.pagu.store', $header) }}" method="POST" class="flex items-center gap-2 flex-1">
                                                    @csrf
                                                    <input type="hidden" name="account_code_id" value="{{ $code->id }}">
                                                    <div class="relative rounded-md shadow-sm flex-1">
                                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
                                                            <span class="text-gray-500 text-xs font-semibold">Rp</span>
                                                        </div>
                                                        <input type="number" name="nominal_pagu" min="0" step="any" required
                                                            value="{{ $isEstablished ? (float)$paguRecord->nominal_pagu : '' }}"
                                                            placeholder="0"
                                                            class="block w-full rounded-lg border-gray-300 pl-8 pr-2 py-1.5 text-xs text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 font-semibold">
                                                    </div>
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 {{ $hasUnvalidated ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} active:opacity-90 text-white font-bold text-xs rounded-lg shadow transition ease-in-out duration-150"
                                                        title="{{ $hasUnvalidated ? 'Ada usulan belum divalidasi oleh supervisor' : 'Simpan penetapan pagu' }}">
                                                        <span>💾 Simpan</span>
                                                    </button>
                                                </form>

                                                @if($isEstablished)
                                                    <form action="{{ route('admin.headers.pagu.destroy', [$header, $code]) }}" method="POST"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin membatalkan penetapan pagu untuk rekening {{ $code->code }} ({{ $code->name }})?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1.5 rounded hover:bg-rose-50 transition"
                                                            title="Batalkan penetapan pagu rekening ini">
                                                            Batal
                                                        </button>
                                                    </form>
                                                @endif
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
    </div>
</x-app-layout>