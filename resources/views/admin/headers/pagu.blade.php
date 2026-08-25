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
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg text-rose-800 text-sm font-medium flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Summary Cards -->
            @php
                $totalRekening = $accountCodes->count();
                $ditetapkanCount = $existingPagus->count();
                $belumDitetapkanCount = $totalRekening - $ditetapkanCount;
                $totalPaguNominal = $existingPagus->sum('nominal_pagu');
                $totalUsulanAll = $totalRequests->sum('total');
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
                    <div class="text-[11px] text-amber-600 mt-0.5">Masih terbuka untuk usulan</div>
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
                            Setiap baris rekening memiliki status dan tombol simpan tersendiri. Menginput nominal 0 dan menekan tombol <strong>Simpan</strong> akan menetapkan pagu Rp 0 dan langsung mengunci rekening tersebut.
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
                                        $totalReq = $totalRequests[$code->id]->total ?? 0;
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition-colors">
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
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded-lg shadow transition ease-in-out duration-150">
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