<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Workboard RBA') }} - {{ $submission->header->year }} ({{ $submission->header->period->name }})
            </h2>
            <div class="flex space-x-2 items-center" x-data="{ openPrint: false, openPrintModal: false, printType: 'usulan', grouping: 'account', sortBy: 'account_code', sortDir: 'asc' }">
                <!-- Dropdown Cetak RBA -->
                <div class="relative">
                    <button @click="openPrint = !openPrint" @click.away="openPrint = false" type="button"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded text-sm inline-flex items-center gap-1.5 shadow-sm transition-all">
                        <span>🖨️ Cetak Rincian</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Dropdown Menu -->
                    <div x-show="openPrint" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 z-50 py-2 divide-y divide-gray-100"
                        style="display: none;">
                        
                        <!-- Pilihan Kustomisasi Cetak (Buka Modal) -->
                        <div class="p-2 border-b border-gray-100 bg-slate-50/80 rounded-t-xl">
                            <button type="button" @click="openPrint = false; openPrintModal = true"
                                class="w-full text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 py-1.5 px-2.5 rounded-lg flex items-center justify-between transition-colors shadow-2xs cursor-pointer">
                                <span class="flex items-center gap-1.5">
                                    <span>⚙️</span>
                                    <span>Kustomisasi Urutan Kolom...</span>
                                </span>
                                <span class="text-[10px] bg-indigo-200 text-indigo-900 px-1 py-0.5 rounded font-mono">PILIH</span>
                            </button>
                        </div>

                        <!-- Kategori 1: Usulan Rincian Belanja -->
                        <div class="px-3 py-2">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">1. Usulan Rincian Belanja</div>
                            <div class="flex flex-col gap-1">
                                <a href="{{ route('operator.submissions.print-preview', ['submission' => $submission->id, 'include_background' => 1]) }}" target="_blank"
                                    class="text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 px-2 py-1.5 rounded-lg flex items-center justify-between transition-colors">
                                    <span>📄 Cetak Dengan Latar Belakang</span>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded font-mono">HTML</span>
                                </a>
                                <a href="{{ route('operator.submissions.print-preview', ['submission' => $submission->id, 'include_background' => 0]) }}" target="_blank"
                                    class="text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 px-2 py-1.5 rounded-lg flex items-center justify-between transition-colors">
                                    <span>📄 Cetak Tanpa Latar Belakang</span>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded font-mono">HTML</span>
                                </a>
                            </div>
                        </div>

                        <!-- Kategori 2: Rincian Belanja & Pagu (RBA Final) -->
                        <div class="px-3 py-2 border-t border-gray-100">
                            <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider mb-1">2. Rincian Belanja & Pagu (RBA Final)</div>
                            <div class="flex flex-col gap-1">
                                <div class="text-[9.5px] font-semibold text-gray-400 mt-1 uppercase">Format Terkelompok Rekening</div>
                                <a href="{{ route('operator.submissions.print-preview-final', ['submission' => $submission->id, 'include_background' => 1, 'grouping' => 'account']) }}" target="_blank"
                                    class="text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 px-2 py-1 rounded-lg flex items-center justify-between transition-colors">
                                    <span>📊 Terkelompok (Dgn Background)</span>
                                    <span class="text-[9px] bg-indigo-100 text-indigo-800 px-1 py-0.5 rounded font-mono">PAGU</span>
                                </a>
                                <a href="{{ route('operator.submissions.print-preview-final', ['submission' => $submission->id, 'include_background' => 0, 'grouping' => 'account']) }}" target="_blank"
                                    class="text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 px-2 py-1 rounded-lg flex items-center justify-between transition-colors">
                                    <span>📊 Terkelompok (Tanpa Background)</span>
                                    <span class="text-[9px] bg-indigo-100 text-indigo-800 px-1 py-0.5 rounded font-mono">PAGU</span>
                                </a>
                                
                                <div class="text-[9.5px] font-semibold text-emerald-600 mt-1.5 uppercase">Format Per Baris Usulan (Flat)</div>
                                <a href="{{ route('operator.submissions.print-preview-final', ['submission' => $submission->id, 'include_background' => 1, 'grouping' => 'flat']) }}" target="_blank"
                                    class="text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 px-2 py-1 rounded-lg flex items-center justify-between transition-colors">
                                    <span>📋 Baris Datar / Flat (Dgn Background)</span>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded font-mono">FLAT</span>
                                </a>
                                <a href="{{ route('operator.submissions.print-preview-final', ['submission' => $submission->id, 'include_background' => 0, 'grouping' => 'flat']) }}" target="_blank"
                                    class="text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 px-2 py-1 rounded-lg flex items-center justify-between transition-colors">
                                    <span>📋 Baris Datar / Flat (Tanpa Background)</span>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded font-mono">FLAT</span>
                                </a>
                            </div>
                        </div>

                        <!-- Kategori 3: Akses Menu Laporan Lengkap -->
                        <div class="px-3 py-2 bg-slate-50 rounded-b-xl">
                            <a href="{{ route('reports.index', ['submission_id' => $submission->id]) }}"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center justify-between group transition-colors">
                                <span class="flex items-center gap-1.5">
                                    <span>📑</span>
                                    <span>Buka di Menu Laporan</span>
                                </span>
                                <span class="transform group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Modal Konfigurasi Cetak Operator -->
                <div x-show="openPrintModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="openPrintModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="openPrintModal = false"></div>

                        <div x-show="openPrintModal" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">
                            
                            <form :action="printType === 'final' ? '{{ route('operator.submissions.print-preview-final', $submission->id) }}' : '{{ route('operator.submissions.print-preview', $submission->id) }}'" method="GET" target="_blank" @submit="openPrintModal = false">
                                <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-4 flex items-center justify-between text-white">
                                    <h3 class="text-base font-bold flex items-center gap-2">
                                        <span>🖨️ Opsi Konfigurasi Cetak Operator</span>
                                    </h3>
                                    <button type="button" @click="openPrintModal = false" class="text-gray-400 hover:text-white font-bold text-xl cursor-pointer">&times;</button>
                                </div>

                                <div class="p-6 space-y-4">
                                    <!-- 1. Jenis Dokumen Laporan -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. Jenis Dokumen Laporan</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label :class="printType === 'usulan' ? 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-slate-50 text-gray-700 hover:border-emerald-300'" class="flex flex-col gap-1 p-3 rounded-xl border cursor-pointer transition-all">
                                                <div class="flex items-center gap-2">
                                                    <input type="radio" x-model="printType" value="usulan" class="text-emerald-600 focus:ring-emerald-500">
                                                    <span class="text-xs font-bold">Usulan Rincian Belanja</span>
                                                </div>
                                                <span class="text-[10px] text-gray-500 pl-5">Format usulan standar tanpa kolom Pagu Final.</span>
                                            </label>
                                            <label :class="printType === 'final' ? 'border-indigo-500 bg-indigo-50 text-indigo-900 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-slate-50 text-gray-700 hover:border-indigo-300'" class="flex flex-col gap-1 p-3 rounded-xl border cursor-pointer transition-all">
                                                <div class="flex items-center gap-2">
                                                    <input type="radio" x-model="printType" value="final" class="text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-xs font-bold text-indigo-900">Rincian Belanja & Pagu (RBA Final)</span>
                                                </div>
                                                <span class="text-[10px] text-gray-500 pl-5">Format RBA Final bersandingan dengan nominal Pagu.</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 1.5. Opsi Format Rekening (Hanya jika printType === 'final') -->
                                    <div x-show="printType === 'final'" x-transition class="p-3 bg-indigo-50/50 rounded-xl border border-indigo-100">
                                        <label class="block text-xs font-bold text-indigo-950 uppercase tracking-wider mb-2">
                                            Format Tata Letak Rekening RBA Final
                                        </label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="flex items-start gap-2 p-2.5 rounded-lg border cursor-pointer transition"
                                                :class="grouping === 'account' ? 'border-indigo-500 bg-white shadow-xs text-indigo-950 font-bold' : 'border-gray-200 bg-white/60 text-gray-700'">
                                                <input type="radio" name="grouping" value="account" x-model="grouping" class="text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                                <div>
                                                    <div class="text-xs font-bold">Terkelompok Rekening</div>
                                                    <div class="text-[9.5px] text-gray-500 font-normal">Dikelompokkan per akun dengan subtotal.</div>
                                                </div>
                                            </label>
                                            <label class="flex items-start gap-2 p-2.5 rounded-lg border cursor-pointer transition"
                                                :class="grouping === 'flat' ? 'border-indigo-500 bg-white shadow-xs text-indigo-950 font-bold' : 'border-gray-200 bg-white/60 text-gray-700'">
                                                <input type="radio" name="grouping" value="flat" x-model="grouping" class="text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                                <div>
                                                    <div class="text-xs font-bold">Per Baris Usulan (Flat)</div>
                                                    <div class="text-[9.5px] text-gray-500 font-normal">Satu baris per usulan, kode & nama rekening terulang.</div>
                                                </div>
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

                                    <!-- 3. Opsi Pengurutan Kolom -->
                                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                3. Pengurutan Kolom Laporan (Sort By)
                                            </label>
                                            <span class="text-[10px] text-gray-500 font-medium">Bawaan: Nomor Rekening</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-2">
                                                <select name="sort_by" x-model="sortBy" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 bg-white">
                                                    <option value="account_code">🔢 Nomor Rekening Belanja (Default)</option>
                                                    <option value="account_name">🔤 Nama Rekening Belanja</option>
                                                    <option value="description">📝 Uraian & Spesifikasi Belanja</option>
                                                    <option value="nominal_request">💰 Total Usulan Belanja (Rp)</option>
                                                    <template x-if="printType === 'final'">
                                                        <option value="pagu_final">🏷️ Nominal Pagu Final (Rp)</option>
                                                    </template>
                                                    <option value="harga_satuan">💵 Harga Satuan (Rp)</option>
                                                    <option value="volume">📦 Volume Belanja</option>
                                                </select>
                                            </div>
                                            <div>
                                                <select name="sort_dir" x-model="sortDir" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 bg-white">
                                                    <option value="asc">⬆️ Menaik (A-Z / 0-9)</option>
                                                    <option value="desc">⬇️ Menurun (Z-A / 9-0)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                                    <button type="button" @click="openPrintModal = false"
                                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 cursor-pointer">
                                        Batal
                                    </button>
                                    <button type="submit"
                                        class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer flex items-center gap-1.5">
                                        <span>🖨️</span>
                                        <span>Buka Dokumen Cetak</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @php
                    $hasOpenPagu = \App\Models\AccountCode::whereDoesntHave('accountPagus', function($q) use ($submission) {
                        $q->where('rba_header_id', $submission->rba_header_id)->where('nominal_pagu', '>', 0);
                    })->exists();
                @endphp
                
                @if(Auth::user()->isProposer())
                    @if($submission->header->status_global === 'Draft' || $hasOpenPagu)
                        @if(!empty($submission->background))
                            <a href="{{ route('operator.details.create', ['submission_id' => $submission->id]) }}"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow-sm transition">
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
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs" title="Anda berada dalam mode peninjau (tidak memiliki hak pengusulan)">
                        <span>👁️</span>
                        <span>Mode Peninjau (Hanya Lihat)</span>
                    </span>
                @endif
                <a href="{{ route('operator.submissions.index') }}"
                    class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full mx-auto sm:px-6 lg:px-8">
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
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-lg text-gray-800">
                            Latar Belakang RBA Anda <span class="text-xs font-normal text-gray-500 font-mono">({{ Auth::user()->name }})</span>
                        </h3>
                        @if(!empty($myBackground))
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                <span>✓</span>
                                <span>Latar Belakang Anda Tersimpan</span>
                            </span>
                        @endif
                    </div>
                    
                    @if(Auth::user()->isProposer())
                        @if(empty($myBackground))
                            <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Anda belum mengisi data Latar Belakang Anda. Anda **wajib** mengisi Latar Belakang terlebih dahulu sebelum dapat menambahkan rincian belanja.
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
                                    placeholder="Tuliskan latar belakang usulan RBA Anda secara spesifik di sini..." 
                                    {{ $submission->header->status_global === 'Locked' ? 'readonly' : '' }} required>{{ old('background', $myBackground ?? $submission->background) }}</textarea>
                            </div>
                            @if($submission->header->status_global !== 'Locked')
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow-md transition duration-150 ease-in-out">
                                        {{ empty($myBackground) ? 'Simpan Latar Belakang' : 'Perbarui Latar Belakang' }}
                                    </button>
                                </div>
                            @endif
                        </form>
                    @else
                        <div class="mb-4 bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs text-slate-700">
                            @if(!empty($myBackground ?? $submission->background))
                                <p class="whitespace-pre-wrap leading-relaxed">{{ $myBackground ?? $submission->background }}</p>
                            @else
                                <p class="italic text-gray-400">Belum ada catatan latar belakang yang diinputkan untuk akun ini (Mode Peninjau / Read-Only).</p>
                            @endif
                        </div>
                    @endif

                    {{-- Referensi Latar Belakang Rekan Operator Lain --}}
                    @if(isset($otherOperatorBackgrounds) && $otherOperatorBackgrounds->isNotEmpty())
                        <div x-data="{ openOthers: false }" class="mt-6 pt-4 border-t border-gray-200">
                            <button type="button" @click="openOthers = !openOthers" class="flex items-center justify-between w-full text-left text-xs font-bold text-gray-700 hover:text-indigo-600 transition-colors">
                                <span class="flex items-center gap-1.5">
                                    <span>👥</span>
                                    <span>Lihat Latar Belakang Rekan Operator Lain di Unit Ini ({{ $otherOperatorBackgrounds->count() }})</span>
                                </span>
                                <span x-text="openOthers ? '▲ Tutup' : '▼ Lihat'" class="text-[11px] text-indigo-600 font-semibold"></span>
                            </button>
                            
                            <div x-show="openOthers" x-transition class="mt-3 space-y-3">
                                @foreach($otherOperatorBackgrounds as $otherBg)
                                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5">
                                        <div class="flex items-center justify-between mb-1.5 pb-1.5 border-b border-slate-200 text-xs font-bold text-slate-800">
                                            <span>{{ $otherBg->user->name }}</span>
                                            <span class="text-[10px] text-gray-400 font-normal">Diperbarui: {{ $otherBg->updated_at->format('d M Y, H:i') }}</span>
                                        </div>
                                        <p class="text-xs text-gray-600 whitespace-pre-wrap">{{ $otherBg->background }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @php
                $documentsData = ($existingDocuments ?? collect())->map(function($doc) {
                    $latest = $doc->latestVersion;
                    return [
                        'id' => $doc->id,
                        'name' => $doc->document_name,
                        'version' => $latest ? 'V' . $latest->version_number : '-',
                        'filename' => $latest?->original_filename ?? basename($latest?->file_path ?? '-'),
                        'file_url' => $latest && \Illuminate\Support\Facades\Storage::disk('public')->exists($latest->file_path) ? \Illuminate\Support\Facades\Storage::url($latest->file_path) : null,
                        'details_count' => $latest ? $latest->details->count() : 0,
                    ];
                })->values();
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div x-data="{ 
                        search: '',
                        formatIDR(val) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        },
                        uploadModalOpen: false,
                        sourceMode: 'upload',
                        selectedExistingDocId: null,
                        allDocuments: @js($documentsData),
                        get availableExistingDocs() {
                            return this.allDocuments.filter(d => !this.uploadTargetDetail.currentDocId || d.id !== this.uploadTargetDetail.currentDocId);
                        },
                        uploadMode: 'shared_update',
                        targetDetailIds: [],
                        uploadTargetDetail: {
                            id: null,
                            accountName: '',
                            description: '',
                            nominal: '',
                            currentVersion: '',
                            currentDocId: null,
                            documentName: '',
                            sharedDetails: [],
                            uploadUrl: '',
                            isExceeding: false,
                            hasRevision: false,
                        },
                        selectedFileName: '',
                        openUploadModal(detail) {
                            this.uploadTargetDetail = detail;
                            this.selectedFileName = '';
                            this.sourceMode = 'upload';
                            this.selectedExistingDocId = null;
                            this.uploadMode = (detail.sharedDetails && detail.sharedDetails.length > 0) ? 'shared_update' : 'standalone';
                            this.targetDetailIds = [detail.id, ...(detail.sharedDetails ? detail.sharedDetails.map(d => d.id) : [])];
                            this.uploadModalOpen = true;
                        },
                        closeUploadModal() {
                            this.uploadModalOpen = false;
                            this.selectedFileName = '';
                            this.selectedExistingDocId = null;
                            this.sourceMode = 'upload';
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

                        <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm my-4">
                            <table class="min-w-[1200px] w-full divide-y divide-gray-200" id="details-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">AWAL</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Volume</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Total</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Pagu</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Pagu</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">PDF</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
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
                                            <td class="px-4 py-2 text-sm">
                                                {{ $detail->accountCode->code }} - {{ $detail->accountCode->name }}
                                                @if($detail->is_rejected)
                                                    <div class="mt-1 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                                        <strong>Alasan Penolakan:</strong><br>
                                                        {{ $detail->rejection_reason }}
                                                    </div>
                                                @endif
                                            </td>
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
                                                @php 
                                                    $latest = $detail->latestAttachment(); 
                                                    $sharedCount = $latest ? $latest->details->count() : 0;
                                                    $currentDoc = $latest?->document;
                                                    $sharedWithOthers = [];
                                                    if ($latest) {
                                                        $sharedWithOthers = $latest->details
                                                            ->where('id', '!=', $detail->id)
                                                            ->map(function($d) {
                                                                return [
                                                                    'id' => $d->id,
                                                                    'label' => ($d->accountCode?->code ?? '') . ' - ' . \Illuminate\Support\Str::limit($d->description, 35),
                                                                    'nominal' => 'Rp ' . number_format($d->nominal_request, 0, ',', '.'),
                                                                    'status' => $d->is_validated ? 'Valid' : ($d->is_rejected ? 'Tolak' : ($d->is_submitted ? 'Ajuan' : 'Draft')),
                                                                ];
                                                            })->values()->all();
                                                    }
                                                @endphp
                                                @if($latest)
                                                    @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($latest->file_path))
                                                        <div class="flex flex-col items-center">
                                                            <a href="{{ Storage::url($latest->file_path) }}" target="_blank"
                                                                class="text-blue-600 hover:underline text-xs font-bold inline-flex items-center gap-1">
                                                                <span>PDF V{{ $latest->version_number }}</span>
                                                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                            </a>
                                                            @if($sharedCount > 1)
                                                                <span class="inline-flex items-center gap-0.5 text-[9px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded-full mt-0.5 cursor-help"
                                                                      title="Dokumen: {{ $latest->document?->document_name ?? 'Dokumen Bersama' }} (Digunakan bersama oleh {{ $sharedCount }} usulan belanja)">
                                                                    <span>👥 Bersama ({{ $sharedCount }})</span>
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-amber-600 font-bold text-[10px] bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded cursor-help" title="File PDF fisik tidak ditemukan di storage server. Silakan unggah versi baru.">
                                                            ⚠️ Missing (V{{ $latest->version_number }})
                                                        </span>
                                                    @endif
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
                                            </td>
                                            <td class="px-4 py-2 text-sm whitespace-nowrap">
                                                @php
                                                    $isItemLockedByPagu = $isPaguEstablished;
                                                    $isExceeding = $detail->isExceedingPagu();
                                                    $hasRevision = $detail->hasUploadedRevision();
                                                @endphp

                                                @if(!Auth::user()->isProposer())
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-gray-500 font-medium bg-gray-100 px-2 py-0.5 rounded italic">
                                                        <span>👁️</span> Hanya Lihat
                                                    </span>
                                                @elseif($detail->is_validated)
                                                    <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg justify-center shadow-2xs">
                                                        <span>🔒</span>
                                                        <span>Tervalidasi</span>
                                                    </div>
                                                @elseif(!$isItemLockedByPagu)
                                                    <div class="flex items-center gap-1.5">
                                                        <!-- Tombol Edit (Icon Pencil) -->
                                                        <a href="{{ route('operator.details.edit', $detail) }}"
                                                            class="p-1.5 rounded-lg text-indigo-600 hover:text-indigo-900 bg-indigo-50/80 hover:bg-indigo-100 border border-indigo-200 transition-all cursor-pointer shadow-2xs"
                                                            title="Edit Rincian Belanja">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>

                                                        <!-- Tombol Unggah Revisi PDF (Icon Upload Document) -->
                                                        <button type="button"
                                                            @click="openUploadModal({
                                                                id: {{ $detail->id }},
                                                                accountName: @js($detail->accountCode->code . ' - ' . $detail->accountCode->name),
                                                                description: @js($detail->description),
                                                                nominal: @js('Rp ' . number_format($detail->nominal_request, 0, ',', '.')),
                                                                currentVersion: @js($latest ? 'V' . $latest->version_number : '-'),
                                                                currentDocId: {{ $currentDoc?->id ?? 'null' }},
                                                                documentName: @js($currentDoc?->document_name ?? 'Dokumen Usulan Belanja'),
                                                                sharedDetails: @js($sharedWithOthers),
                                                                uploadUrl: @js(route('operator.details.upload-version', $detail)),
                                                                isExceeding: false,
                                                                hasRevision: false
                                                            })"
                                                            class="p-1.5 rounded-lg text-sky-600 hover:text-sky-900 bg-sky-50/80 hover:bg-sky-100 border border-sky-200 transition-all cursor-pointer shadow-2xs"
                                                            title="Unggah Dokumen PDF Revisi">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                            </svg>
                                                        </button>

                                                        <!-- Tombol Ajukan (Icon Paper Airplane) -->
                                                        @if(!$detail->is_submitted || $detail->is_rejected)
                                                            <form action="{{ route('operator.details.submit-item', $detail) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" 
                                                                    class="p-1.5 rounded-lg text-emerald-600 hover:text-emerald-900 bg-emerald-50/80 hover:bg-emerald-100 border border-emerald-200 transition-all cursor-pointer shadow-2xs"
                                                                    title="Ajukan ke Supervisor">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <!-- Tombol Hapus (Icon Trash) -->
                                                        <form action="{{ route('operator.details.destroy', $detail) }}" method="POST" onsubmit="return confirm('Hapus rincian belanja ini?')" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                class="p-1.5 rounded-lg text-rose-600 hover:text-rose-900 bg-rose-50/80 hover:bg-rose-100 border border-rose-200 transition-all cursor-pointer shadow-2xs"
                                                                title="Hapus Rincian Belanja">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    {{-- Item terkunci karena pagu sudah diset oleh Admin --}}
                                                    @if($isExceeding)
                                                        @if(!$hasRevision)
                                                            <div class="flex flex-col items-start gap-1">
                                                                <button type="button"
                                                                    @click="openUploadModal({
                                                                        id: {{ $detail->id }},
                                                                        accountName: @js($detail->accountCode->code . ' - ' . $detail->accountCode->name),
                                                                        description: @js($detail->description),
                                                                        nominal: @js('Rp ' . number_format($detail->nominal_request, 0, ',', '.')),
                                                                        currentVersion: @js($latest ? 'V' . $latest->version_number : '-'),
                                                                        currentDocId: {{ $currentDoc?->id ?? 'null' }},
                                                                        documentName: @js($currentDoc?->document_name ?? 'Dokumen Usulan Belanja'),
                                                                        sharedDetails: @js($sharedWithOthers),
                                                                        uploadUrl: @js(route('operator.details.upload-version', $detail)),
                                                                        isExceeding: true,
                                                                        hasRevision: false
                                                                    })"
                                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-xs transition-all cursor-pointer animate-pulse"
                                                                    title="Wajib Unggah PDF Penyesuaian Pagu">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                    </svg>
                                                                    <span>Upload PDF Revisi</span>
                                                                </button>
                                                                <span class="text-[9px] font-bold text-amber-700">⚠ Melebihi Pagu</span>
                                                            </div>
                                                        @else
                                                            <div class="flex items-center gap-1.5">
                                                                @if(!$detail->is_submitted || $detail->is_rejected)
                                                                    <form action="{{ route('operator.details.submit-item', $detail) }}" method="POST" class="inline">
                                                                        @csrf
                                                                        <button type="submit" 
                                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-xs transition-all cursor-pointer"
                                                                            title="Ajukan ke Supervisor">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                                            </svg>
                                                                            <span>Ajukan</span>
                                                                        </button>
                                                                    </form>
                                                                @endif

                                                                <button type="button"
                                                                    @click="openUploadModal({
                                                                        id: {{ $detail->id }},
                                                                        accountName: @js($detail->accountCode->code . ' - ' . $detail->accountCode->name),
                                                                        description: @js($detail->description),
                                                                        nominal: @js('Rp ' . number_format($detail->nominal_request, 0, ',', '.')),
                                                                        currentVersion: @js($latest ? 'V' . $latest->version_number : '-'),
                                                                        currentDocId: {{ $currentDoc?->id ?? 'null' }},
                                                                        documentName: @js($currentDoc?->document_name ?? 'Dokumen Usulan Belanja'),
                                                                        sharedDetails: @js($sharedWithOthers),
                                                                        uploadUrl: @js(route('operator.details.upload-version', $detail)),
                                                                        isExceeding: true,
                                                                        hasRevision: true
                                                                    })"
                                                                    class="p-1.5 rounded-lg text-sky-600 hover:text-sky-900 bg-sky-50/80 hover:bg-sky-100 border border-sky-200 transition-all cursor-pointer shadow-2xs"
                                                                    title="Unggah Versi PDF Lebih Baru">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="inline-flex flex-col items-center">
                                                            <span class="text-[9px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded border border-slate-200 font-bold uppercase">Pagu Locked</span>
                                                            <span class="text-gray-400 text-[9px] italic mt-0.5">Read Only</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row">
                                            <td colspan="12" class="px-4 py-8 text-center text-gray-500 italic">Belum ada rincian belanja.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Dialog Unggah Revisi Dokumen PDF -->
                        <div x-show="uploadModalOpen"
                             x-cloak
                             style="display: none;"
                             class="fixed inset-0 z-50 overflow-y-auto"
                             aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <!-- Backdrop Blur -->
                            <div x-show="uploadModalOpen"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                                 @click="closeUploadModal()"></div>

                            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                                <div x-show="uploadModalOpen"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">
                                    
                                    <!-- Modal Header -->
                                    <div class="bg-slate-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-base shadow-2xs">
                                                📄
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-bold text-gray-900 leading-tight">Unggah Revisi Dokumen PDF</h3>
                                                <p class="text-[11px] text-gray-500">Pembaruan dokumen pendukung rincian belanja usulan</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="closeUploadModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>

                                    <!-- Form Upload / Ganti Dokumen -->
                                    <form :action="uploadTargetDetail.uploadUrl" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="source_mode" :value="sourceMode">
                                        <input type="hidden" name="upload_mode" :value="uploadMode">
                                        <input type="hidden" name="rba_detail_document_id" :value="selectedExistingDocId">

                                        <div class="p-6 space-y-4">
                                            <!-- Detail Context Card -->
                                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="text-gray-500 font-semibold">Rekening:</span>
                                                    <span class="font-bold text-gray-800 text-right" x-text="uploadTargetDetail.accountName"></span>
                                                </div>
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="text-gray-500 font-semibold">Rincian:</span>
                                                    <span class="font-medium text-gray-800 text-right truncate max-w-[260px]" x-text="uploadTargetDetail.description"></span>
                                                </div>
                                                <div class="flex justify-between items-center pt-1 border-t border-slate-200">
                                                    <span class="text-gray-500 font-semibold">Nominal Usulan:</span>
                                                    <span class="font-black text-indigo-600" x-text="uploadTargetDetail.nominal"></span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-500 font-semibold">Dokumen & Versi Saat Ini:</span>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-semibold text-gray-700 truncate max-w-[160px]" x-text="uploadTargetDetail.documentName"></span>
                                                        <span class="px-2 py-0.5 bg-sky-100 text-sky-800 rounded font-bold text-[10px]" x-text="uploadTargetDetail.currentVersion"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Segmented Control / Pemilih Mode Sumber Dokumen -->
                                            <div class="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200">
                                                <button type="button"
                                                        @click="sourceMode = 'upload'"
                                                        :class="sourceMode === 'upload' ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-semibold'"
                                                        class="flex-1 py-1.5 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                                    <span>📤 Unggah Berkas PDF Revisi</span>
                                                </button>
                                                <button type="button"
                                                        @click="sourceMode = 'existing'"
                                                        :class="sourceMode === 'existing' ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900 font-semibold'"
                                                        class="flex-1 py-1.5 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                                    <span>🔄 Pilih Dokumen Lain</span>
                                                    <span class="px-1.5 py-0.2 bg-indigo-100 text-indigo-800 text-[10px] rounded-full font-black"
                                                          x-text="availableExistingDocs.length"></span>
                                                </button>
                                            </div>

                                            <!-- ================= MODE 1: UNGGAH BERKAS PDF REVISI (ALUR ASLI) ================= -->
                                            <div x-show="sourceMode === 'upload'" class="space-y-4">
                                                <!-- Pilihan Cakupan Revisi jika Dokumen Dipakai Bersama -->
                                                <template x-if="uploadTargetDetail.sharedDetails && uploadTargetDetail.sharedDetails.length > 0">
                                                    <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200 text-xs space-y-3">
                                                        <div class="flex items-center justify-between">
                                                            <span class="font-bold text-indigo-950 flex items-center gap-1.5">
                                                                <span>👥</span> Dokumen Bersama (<span x-text="uploadTargetDetail.sharedDetails.length + 1"></span> usulan)
                                                            </span>
                                                            <span class="text-[10px] text-indigo-700 bg-white px-2 py-0.5 rounded border border-indigo-200 font-semibold truncate max-w-[180px]"
                                                                  x-text="uploadTargetDetail.documentName"></span>
                                                        </div>

                                                        <!-- Mode Selector -->
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <label class="p-2.5 rounded-lg border text-left cursor-pointer transition-all bg-white"
                                                                   :class="uploadMode === 'shared_update' ? 'border-indigo-600 ring-2 ring-indigo-200 bg-indigo-50/40 shadow-2xs' : 'border-gray-200 hover:border-indigo-300'">
                                                                <input type="radio" name="_mode_selector" value="shared_update" x-model="uploadMode" class="sr-only">
                                                                <div class="font-bold text-gray-900 text-xs">Perbarui Bersama</div>
                                                                <p class="text-[10px] text-gray-500 mt-0.5">Unggah revisi baru untuk usulan-usulan terkait</p>
                                                            </label>
                                                            <label class="p-2.5 rounded-lg border text-left cursor-pointer transition-all bg-white"
                                                                   :class="uploadMode === 'standalone' ? 'border-indigo-600 ring-2 ring-indigo-200 bg-indigo-50/40 shadow-2xs' : 'border-gray-200 hover:border-indigo-300'">
                                                                <input type="radio" name="_mode_selector" value="standalone" x-model="uploadMode" class="sr-only">
                                                                <div class="font-bold text-gray-900 text-xs">Pisahkan Dokumen</div>
                                                                <p class="text-[10px] text-gray-500 mt-0.5">Khusus usulan ini saja, usulan lain tidak berubah</p>
                                                            </label>
                                                        </div>

                                                        <!-- Checklist usulan yang ikut jika Perbarui Bersama -->
                                                        <div x-show="uploadMode === 'shared_update'" class="pt-2 border-t border-indigo-100">
                                                            <span class="block text-[11px] font-bold text-gray-700 mb-1.5">
                                                                Pilih usulan yang ikut menggunakan berkas revisi baru:
                                                            </span>
                                                            <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                                                <label class="flex items-center gap-2 text-xs bg-white p-2 rounded border border-gray-200">
                                                                    <input type="checkbox" checked disabled class="text-indigo-600 rounded">
                                                                    <span class="font-semibold text-gray-800" x-text="uploadTargetDetail.description + ' (Usulan ini)'"></span>
                                                                </label>
                                                                <template x-for="item in uploadTargetDetail.sharedDetails" :key="item.id">
                                                                    <label class="flex items-center justify-between gap-2 text-xs bg-white p-2 rounded border border-gray-200 hover:bg-slate-50 cursor-pointer">
                                                                        <div class="flex items-center gap-2">
                                                                            <input type="checkbox" :value="item.id" x-model="targetDetailIds" name="target_detail_ids[]" class="text-indigo-600 rounded focus:ring-indigo-500">
                                                                            <span class="text-gray-800" x-text="item.label"></span>
                                                                        </div>
                                                                        <span class="text-[10px] text-gray-500 font-mono" x-text="item.nominal"></span>
                                                                    </label>
                                                                </template>
                                                            </div>
                                                            <p class="text-[10px] text-gray-500 mt-1 italic">
                                                                * Usulan yang tidak dicentang akan tetap berada pada versi dokumen sebelumnya.
                                                            </p>
                                                        </div>

                                                        <!-- Input Dokumen Baru jika Pisahkan Dokumen -->
                                                        <div x-show="uploadMode === 'standalone'" class="pt-2 border-t border-indigo-100">
                                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                                Nama Dokumen Baru (Opsional):
                                                            </label>
                                                            <input type="text" name="document_name" placeholder="Contoh: Nota Dinas Khusus Pulpen..."
                                                                   class="w-full text-xs border-gray-300 rounded-lg shadow-xs">
                                                            <p class="text-[10px] text-gray-500 mt-1 italic">
                                                                * Usulan ini akan terpisah mandiri dan memiliki riwayat tersendiri tanpa mengubah usulan lainnya.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Alert Khusus jika Over Pagu -->
                                                <template x-if="uploadTargetDetail.isExceeding && !uploadTargetDetail.hasRevision">
                                                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2">
                                                        <span class="text-base">⚠️</span>
                                                        <div class="leading-relaxed">
                                                            <strong>Rincian ini melebihi pagu yang ditetapkan!</strong><br>
                                                            Anda wajib mengunggah berkas PDF penyesuaian baru agar usulan ini dapat diajukan kembali ke Supervisor.
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Dropzone File Picker -->
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                                        Pilih Berkas PDF Revisi Baru <span class="text-rose-500">*</span>
                                                    </label>
                                                    <div class="relative border-2 border-dashed border-gray-300 hover:border-indigo-400 rounded-xl p-6 text-center transition-colors bg-white hover:bg-slate-50/50 cursor-pointer"
                                                         @click="$refs.fileInput.click()">
                                                        <input type="file" name="attachment" x-ref="fileInput" accept=".pdf,application/pdf"
                                                               :required="sourceMode === 'upload'" class="sr-only"
                                                               @change="selectedFileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                        
                                                        <div class="flex flex-col items-center">
                                                            <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2 shadow-2xs">
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                </svg>
                                                            </div>
                                                            <template x-if="!selectedFileName">
                                                                <div>
                                                                    <span class="text-xs font-bold text-indigo-600 hover:text-indigo-700">Pilih berkas PDF</span>
                                                                    <span class="text-xs text-gray-500"> atau seret ke sini</span>
                                                                    <p class="text-[10px] text-gray-400 mt-1">Format PDF, Ukuran Maksimal 10 MB</p>
                                                                </div>
                                                            </template>
                                                            <template x-if="selectedFileName">
                                                                <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200">
                                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                                    <span class="text-xs font-bold truncate max-w-[280px]" x-text="selectedFileName"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-[11px] text-gray-400 italic">
                                                    * Catatan: Mengunggah versi PDF baru akan otomatis mengatur ulang status usulan rincian ini menjadi <strong>Draft</strong> agar dapat ditinjau kembali sebelum diajukan.
                                                </div>
                                            </div>

                                            <!-- ================= MODE 2: PILIH DOKUMEN LAIN YANG ADA ================= -->
                                            <div x-show="sourceMode === 'existing'" x-cloak class="space-y-3">
                                                <div class="text-xs text-indigo-900 bg-indigo-50/80 p-3 rounded-xl border border-indigo-200 flex items-start gap-2">
                                                    <span class="text-base">💡</span>
                                                    <div class="leading-relaxed">
                                                        Pilih dokumen PDF yang sudah pernah diunggah untuk pengajuan ini. Usulan belanja ini akan dialihkan ke dokumen tersebut tanpa perlu mengunggah ulang berkas fisik.
                                                    </div>
                                                </div>

                                                <!-- Daftar Dokumen Eksisting Tersedia -->
                                                <template x-if="availableExistingDocs.length > 0">
                                                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                                        <template x-for="doc in availableExistingDocs" :key="doc.id">
                                                            <label class="relative flex items-center justify-between p-3 rounded-xl border-2 transition-all cursor-pointer bg-white"
                                                                   :class="selectedExistingDocId == doc.id ? 'border-indigo-600 bg-indigo-50/30 shadow-xs ring-1 ring-indigo-200' : 'border-gray-200 hover:border-indigo-300'">
                                                                <div class="flex items-center gap-3">
                                                                    <input type="radio" name="_existing_doc_radio" :value="doc.id"
                                                                           x-model="selectedExistingDocId"
                                                                           class="text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                                                    <div>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="font-bold text-xs text-gray-900" x-text="doc.name"></span>
                                                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-sky-100 text-sky-800" x-text="doc.version"></span>
                                                                        </div>
                                                                        <div class="flex items-center gap-2 mt-0.5 text-[10px] text-gray-500">
                                                                            <span x-text="'📁 ' + doc.filename"></span>
                                                                            <span>•</span>
                                                                            <span class="text-indigo-600 font-semibold" x-text="doc.details_count + ' usulan terikat'"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <template x-if="doc.file_url">
                                                                    <a :href="doc.file_url" target="_blank" @click.stop
                                                                       class="px-2.5 py-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg inline-flex items-center gap-1">
                                                                        <span>Lihat PDF</span>
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                                    </a>
                                                                </template>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </template>

                                                <!-- State saat tidak ada dokumen lain -->
                                                <template x-if="availableExistingDocs.length === 0">
                                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center text-xs text-gray-500">
                                                        <span class="text-xl block mb-1">📂</span>
                                                        Belum ada dokumen PDF lain yang tersedia pada pengajuan ini.<br>
                                                        Silakan gunakan opsi <strong>Unggah Berkas PDF Revisi</strong> di atas.
                                                    </div>
                                                </template>

                                                <div class="text-[11px] text-gray-400 italic">
                                                    * Catatan: Beralih ke dokumen eksisting akan otomatis mengatur ulang status usulan rincian ini menjadi <strong>Draft</strong> agar dapat diajukan kembali ke Supervisor.
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Actions -->
                                        <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-100 flex items-center justify-end gap-2.5">
                                            <button type="button" @click="closeUploadModal()"
                                                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition-all cursor-pointer">
                                                Batal
                                            </button>
                                            
                                            <!-- Tombol Submit Mode Unggah Revisi -->
                                            <template x-if="sourceMode === 'upload'">
                                                <button type="submit"
                                                        :disabled="!selectedFileName"
                                                        :class="!selectedFileName ? 'opacity-50 cursor-not-allowed bg-indigo-400' : 'bg-indigo-600 hover:bg-indigo-700 shadow-sm hover:shadow-md cursor-pointer'"
                                                        class="inline-flex items-center gap-1.5 px-5 py-2 text-white rounded-xl text-xs font-bold transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                                    <span>Simpan & Unggah PDF</span>
                                                </button>
                                            </template>

                                            <!-- Tombol Submit Mode Pilih Dokumen Eksisting -->
                                            <template x-if="sourceMode === 'existing'">
                                                <button type="submit"
                                                        :disabled="!selectedExistingDocId"
                                                        :class="!selectedExistingDocId ? 'opacity-50 cursor-not-allowed bg-indigo-400' : 'bg-indigo-600 hover:bg-indigo-700 shadow-sm hover:shadow-md cursor-pointer'"
                                                        class="inline-flex items-center gap-1.5 px-5 py-2 text-white rounded-xl text-xs font-bold transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    <span>Ganti ke Dokumen Terpilih</span>
                                                </button>
                                            </template>
                                        </div>
                                    </form>
                                </div>
                </div>
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
                                        @if(Auth::user()->isProposer())
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
                                        @else
                                            <div class="mt-2 py-1.5 text-center text-[10px] text-gray-400 italic bg-gray-100 rounded">
                                                Mode Peninjau (Unggah Dinonaktifkan)
                                            </div>
                                        @endif

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

            {{-- Kartu & Modal Berita Acara Asistensi / Desk RBA --}}
            @include('operator.submissions.partials.berita_acara_card')

        </div>
    </div>
</x-app-layout>