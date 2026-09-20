@php
    $ba = $myDeskVerification;
    $todayParsed = \App\Models\RbaDeskVerification::parseDateComponents(now());
    $defaultHari = $ba->hari ?? $todayParsed['hari'];
    $defaultTanggal = $ba ? ($ba->tanggal_desk ? $ba->tanggal_desk->format('Y-m-d') : date('Y-m-d')) : date('Y-m-d');
    $defaultSpelled = $ba->tanggal_desk_spelled ?? $todayParsed['tanggal_desk_spelled'];
    $defaultRuang = $ba->ruang_desk ?? 'Ruang RA. Kardinah';
    $defaultSubUnit = $ba->sub_unit_name ?? (Auth::user()->subUnit->name ?? ($submission->unit->name ?? 'Unit'));
    $defaultCatatan = $ba->catatan ?? 'Catatan hasil asistensi/desk terlampir.';
    $defaultIsSipakar = $ba->is_usulan_sipakar ?? 'Ya';
    $defaultKriteria = $ba->kriteria_latar_belakang ?? 'Ya';
    $defaultCatatanPerbaikan = $ba->catatan_perbaikan_latar_belakang ?? '';
    $defaultIsRabUploaded = $ba->is_dokumen_rab_uploaded ?? 'Ya';
    $defaultTim = is_array($ba?->tim_asistensi) && count($ba->tim_asistensi) > 0 
        ? $ba->tim_asistensi 
        : ['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom', 'Nurul L. R., S.I.Pus.'];
    $defaultAnggota = is_array($ba?->anggota_sub_unit) && count($ba->anggota_sub_unit) > 0 
        ? $ba->anggota_sub_unit 
        : [Auth::user()->name];
@endphp

<div x-data="{
    openBaModal: false,
    hari: '{{ addslashes($defaultHari) }}',
    tanggalDesk: '{{ $defaultTanggal }}',
    tanggalSpelled: '{{ addslashes($defaultSpelled) }}',
    ruangDesk: '{{ addslashes($defaultRuang) }}',
    subUnitName: '{{ addslashes($defaultSubUnit) }}',
    catatan: '{{ addslashes($defaultCatatan) }}',
    isUsulanSipakar: '{{ $defaultIsSipakar }}',
    kriteriaLatarBelakang: '{{ $defaultKriteria }}',
    catatanPerbaikan: '{{ addslashes($defaultCatatanPerbaikan) }}',
    isDokumenRabUploaded: '{{ $defaultIsRabUploaded }}',
    timAsistensi: {{ json_encode($defaultTim) }},
    anggotaSubUnit: {{ json_encode($defaultAnggota) }},

    addTimMember() {
        this.timAsistensi.push('');
    },
    removeTimMember(index) {
        if (this.timAsistensi.length > 1) {
            this.timAsistensi.splice(index, 1);
        }
    },
    addAnggotaSubUnit() {
        this.anggotaSubUnit.push('');
    },
    removeAnggotaSubUnit(index) {
        if (this.anggotaSubUnit.length > 1) {
            this.anggotaSubUnit.splice(index, 1);
        }
    },

    terbilang(num) {
        const words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if (num < 12) return words[num];
        if (num < 20) return this.terbilang(num - 10) + ' Belas';
        if (num < 100) return this.terbilang(Math.floor(num / 10)) + ' Puluh ' + this.terbilang(num % 10);
        if (num < 200) return 'Seratus ' + this.terbilang(num - 100);
        if (num < 1000) return this.terbilang(Math.floor(num / 100)) + ' Ratus ' + this.terbilang(num % 100);
        if (num < 2000) return 'Seribu ' + this.terbilang(num - 1000);
        if (num < 1000000) return this.terbilang(Math.floor(num / 1000)) + ' Ribu ' + this.terbilang(num % 1000);
        return String(num);
    },

    onDateChange(val) {
        if (!val) return;
        const d = new Date(val + 'T00:00:00');
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        this.hari = days[d.getDay()];
        const dayWord = this.terbilang(d.getDate()).trim();
        const monthWord = months[d.getMonth() + 1];
        const yearWord = this.terbilang(d.getFullYear()).trim();
        this.tanggalSpelled = `tanggal ${dayWord} Bulan ${monthWord} Tahun ${yearWord}`;
    }
}" class="mt-8">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Card Header --}}
        <div class="p-6 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">📋</span>
                    <h3 class="font-extrabold text-lg tracking-tight">Berita Acara Asistensi / Desk RBA</h3>
                    @if($ba && $ba->latestDocument)
                        <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-xs px-2.5 py-0.5 rounded-full font-bold">
                            V{{ $ba->latestDocument->version_number }} Ditandatangani
                        </span>
                    @elseif($ba)
                        <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 text-xs px-2.5 py-0.5 rounded-full font-bold">
                            Parameter Siap
                        </span>
                    @else
                        <span class="bg-amber-500/20 text-amber-300 border border-amber-400/30 text-xs px-2.5 py-0.5 rounded-full font-bold">
                            Belum Diatur
                        </span>
                    @endif
                </div>
                <p class="text-slate-300 text-xs mt-1 max-w-2xl leading-relaxed">
                    Dokumen pembuktian asistensi dan validasi lapangan oleh Tim Teknis SIPAKAR untuk rincian usulan anggaran unit.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->isProposer())
                    <button type="button" @click="openBaModal = true"
                        class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-3.5 rounded-xl text-xs shadow-md transition">
                        <span>✏️</span>
                        <span>{{ $ba ? 'Ubah Parameter BA' : 'Atur Parameter BA' }}</span>
                    </button>
                @endif

                <a href="{{ route('operator.submissions.berita-acara.print', $submission) }}" target="_blank"
                    class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white font-bold py-2 px-3.5 rounded-xl text-xs border border-white/20 transition">
                    <span>🖨️</span>
                    <span>Cetak Lembar BA</span>
                </a>
            </div>
        </div>

        {{-- Card Content Grid --}}
        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Kolom Kiri: Rincian Parameter BA --}}
            <div class="lg:col-span-7 space-y-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-gray-500 block text-[11px] font-bold uppercase">Waktu & Tempat Desk</span>
                            <span class="font-extrabold text-gray-900">{{ $defaultHari }}, {{ $ba ? ($ba->tanggal_desk ? $ba->tanggal_desk->format('d-m-Y') : date('d-m-Y')) : date('d-m-Y') }}</span>
                            <span class="text-gray-600 block">{{ $defaultRuang }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-[11px] font-bold uppercase">Sub Unit Kerja</span>
                            <span class="font-extrabold text-gray-900">{{ $defaultSubUnit }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200">
                        <span class="text-gray-500 block text-[11px] font-bold uppercase mb-1.5">Evaluasi 3 Kriteria Asistensi</span>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="px-2.5 py-1 rounded-md bg-white border border-gray-200 font-medium text-gray-700">
                                SIPAKAR: <strong>{{ $defaultIsSipakar }}</strong>
                            </span>
                            
                            @if($defaultKriteria === 'Ya')
                                <span class="px-2.5 py-1 rounded-md bg-emerald-50 border border-emerald-200 font-bold text-emerald-800">
                                    Latar Belakang: Memenuhi
                                </span>
                            @elseif($defaultKriteria === 'Perlu Perbaikan')
                                <span class="px-2.5 py-1 rounded-md bg-amber-50 border border-amber-300 font-bold text-amber-800">
                                    ⚠️ Latar Belakang: Perlu Perbaikan
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-md bg-rose-50 border border-rose-200 font-bold text-rose-800">
                                    Latar Belakang: Tidak Memenuhi
                                </span>
                            @endif

                            <span class="px-2.5 py-1 rounded-md bg-white border border-gray-200 font-medium text-gray-700">
                                Dokumen RAB: <strong>{{ $defaultIsRabUploaded }}</strong>
                            </span>
                        </div>

                        @if($defaultKriteria === 'Perlu Perbaikan' && !empty($defaultCatatanPerbaikan))
                            <div class="mt-2.5 p-2.5 bg-amber-50/80 border border-amber-200 rounded-lg text-xs text-amber-900">
                                <span class="font-bold block text-[11px] uppercase tracking-wider text-amber-800">Catatan Perbaikan Latar Belakang:</span>
                                <p class="mt-0.5 whitespace-pre-wrap">{{ $defaultCatatanPerbaikan }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-slate-200 text-xs">
                        <span class="text-gray-500 block text-[11px] font-bold uppercase mb-1">Tim Asistensi Terdaftar</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($defaultTim as $nama)
                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded text-[11px] font-semibold">
                                    {{ $nama }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Pengunggahan Berkas Scan Bertanda Tangan --}}
            <div class="lg:col-span-5 flex flex-col justify-between bg-gray-50 border border-gray-200 rounded-xl p-4">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700">Berkas Scan Ditandatangani</h4>
                        @if($ba && $ba->latestDocument)
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.5 rounded-full font-bold">
                                V{{ $ba->latestDocument->version_number }}
                            </span>
                        @else
                            <span class="bg-gray-200 text-gray-600 text-[10px] px-2 py-0.5 rounded-full font-bold">
                                Belum Diunggah
                            </span>
                        @endif
                    </div>

                    @if($ba && $ba->latestDocument)
                        <div class="p-3 bg-white border border-gray-200 rounded-lg mb-3">
                            <div class="flex items-center justify-between">
                                <div class="truncate mr-2">
                                    <span class="font-bold text-xs text-gray-900 block truncate">{{ $ba->latestDocument->original_filename ?: 'Berita_Acara_Signed.pdf' }}</span>
                                    <span class="text-[10px] text-gray-400">Versi {{ $ba->latestDocument->version_number }} &bull; {{ $ba->latestDocument->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($ba->latestDocument->file_path) }}" target="_blank"
                                    class="text-indigo-600 hover:text-indigo-800 font-bold text-xs shrink-0 flex items-center gap-1">
                                    <span>Unduh</span>
                                    <span>↗</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-gray-500 mb-3 leading-relaxed">
                            Setelah lembar Berita Acara dicetak dan ditandatangani manual oleh Tim Asistensi & Sub Unit, unggah hasil scan PDF ke sistem ini.
                        </p>
                    @endif
                </div>

                <div>
                    @if(Auth::user()->isProposer())
                        <form action="{{ route('operator.submissions.berita-acara.upload', $submission) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                            @csrf
                            <input type="file" name="attachment" accept="application/pdf" class="text-xs w-full block border border-gray-300 rounded-lg p-1.5 bg-white" required>
                            <input type="text" name="notes" placeholder="Catatan versi revisi (opsional)" class="text-xs w-full rounded-lg border-gray-300">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1.5 px-3 rounded-lg text-xs shadow transition">
                                {{ ($ba && $ba->latestDocument) ? 'Unggah Revisi Berita Acara Baru' : 'Unggah Berkas Scan Berita Acara' }}
                            </button>
                        </form>
                    @else
                        <div class="py-2 text-center text-[10px] text-gray-400 italic bg-gray-100 rounded-lg">
                            Mode Peninjau (Unggah Dinonaktifkan)
                        </div>
                    @endif

                    @if($ba && $ba->documents()->count() > 0)
                        <div class="mt-2.5 text-center">
                            <a href="{{ route('operator.submissions.berita-acara.history', $submission) }}"
                                class="text-[11px] text-indigo-600 hover:underline font-bold">
                                🕒 Lihat Riwayat Versi ({{ $ba->documents()->count() }} Berkas)
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Referensi Berita Acara Operator Lain di Unit Ini (Jika Ada) --}}
        @if(isset($otherDeskVerifications) && $otherDeskVerifications->isNotEmpty())
            <div x-data="{ openOthersBa: false }" class="border-t border-gray-100 p-4 bg-slate-50/50">
                <button type="button" @click="openOthersBa = !openOthersBa" class="flex items-center justify-between w-full text-left text-xs font-bold text-gray-700 hover:text-indigo-600 transition">
                    <span class="flex items-center gap-1.5">
                        <span>👥</span>
                        <span>Lihat Status Berita Acara Rekan Operator Lain di Unit Ini ({{ $otherDeskVerifications->count() }})</span>
                    </span>
                    <span x-text="openOthersBa ? '▲ Tutup' : '▼ Lihat'" class="text-[11px] text-indigo-600 font-semibold"></span>
                </button>

                <div x-show="openOthersBa" x-transition class="mt-3 space-y-2">
                    @foreach($otherDeskVerifications as $otherBa)
                        <div class="bg-white border border-gray-200 rounded-xl p-3 flex justify-between items-center text-xs">
                            <div>
                                <span class="font-bold text-gray-900">{{ $otherBa->user?->name }}</span>
                                <span class="text-gray-500">({{ $otherBa->sub_unit_name }})</span>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    Desk: {{ $otherBa->hari }}, {{ $otherBa->tanggal_desk ? $otherBa->tanggal_desk->format('d/m/Y') : '-' }} &bull; 
                                    Dokumen: {{ $otherBa->latestDocument ? 'V' . $otherBa->latestDocument->version_number . ' Diunggah' : 'Belum Diunggah' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('submissions.berita-acara.print', ['submission' => $submission->id, 'user_id' => $otherBa->user_id]) }}" target="_blank"
                                    class="text-indigo-600 hover:underline font-semibold text-[11px]">
                                    Cetak Lembar
                                </a>
                                @if($otherBa->latestDocument)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($otherBa->latestDocument->file_path) }}" target="_blank"
                                        class="bg-indigo-50 text-indigo-700 font-semibold px-2 py-1 rounded text-[10px]">
                                        Unduh Scan
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- MODAL PENGATURAN PARAMETER BERITA ACARA --}}
    <div x-show="openBaModal" style="display: none;" 
        class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            {{-- Backdrop --}}
            <div x-show="openBaModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                @click="openBaModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Panel --}}
            <div x-show="openBaModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                
                <form action="{{ route('operator.submissions.berita-acara.save', $submission) }}" method="POST">
                    @csrf
                    
                    {{-- Modal Header --}}
                    <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📝</span>
                            <h3 class="text-base font-bold">Parameter Berita Acara Asistensi / Desk</h3>
                        </div>
                        <button type="button" @click="openBaModal = false" class="text-white/80 hover:text-white text-lg font-bold">
                            ✕
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto text-sm text-gray-700">

                        {{-- Waktu & Tanggal Desk --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Desk</label>
                                <input type="date" name="tanggal_desk" x-model="tanggalDesk" @change="onDateChange($event.target.value)"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hari Pelaksanaan</label>
                                <input type="text" name="hari" x-model="hari" placeholder="Contoh: Sabtu"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ruang Desk</label>
                                <input type="text" name="ruang_desk" x-model="ruangDesk" placeholder="Ruang RA. Kardinah"
                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                        </div>

                        {{-- Pengejaan Terbilang Tanggal --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pengejaan Terbilang Tanggal (Otomatis)</label>
                            <input type="text" name="tanggal_desk_spelled" x-model="tanggalSpelled"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50/50" required>
                            <span class="text-[10px] text-gray-400 mt-0.5 block">Format: tanggal [Hari Terbilang] Bulan [Nama Bulan] Tahun [Tahun Terbilang]. Dapat diedit manual jika perlu.</span>
                        </div>

                        {{-- Nama Sub Unit --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Sub Bagian / Instalasi / Unit</label>
                            <input type="text" name="sub_unit_name" x-model="subUnitName"
                                class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>

                        {{-- Checklist Evaluasi 3 Kriteria --}}
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                            <span class="block text-xs font-black text-slate-800 uppercase tracking-wider">Checklist Evaluasi Asistensi</span>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">1. Usulan Melalui SIPAKAR</label>
                                    <select name="is_usulan_sipakar" x-model="isUsulanSipakar" class="w-full text-xs rounded-lg border-gray-300">
                                        <option value="Ya">Ya</option>
                                        <option value="Tidak">Tidak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">3. Dokumen RAB Diunggah Pada Sistem</label>
                                    <select name="is_dokumen_rab_uploaded" x-model="isDokumenRabUploaded" class="w-full text-xs rounded-lg border-gray-300">
                                        <option value="Ya">Ya</option>
                                        <option value="Tidak">Tidak</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">2. Latar Belakang Sudah Memenuhi 3 Kriteria</label>
                                <select name="kriteria_latar_belakang" x-model="kriteriaLatarBelakang" class="w-full text-xs rounded-lg border-gray-300 font-semibold">
                                    <option value="Ya">Ya (Sudah Memenuhi)</option>
                                    <option value="Perlu Perbaikan">Perlu Perbaikan</option>
                                    <option value="Tidak">Tidak (Belum Memenuhi)</option>
                                </select>
                            </div>

                            {{-- Kolom Teks Bebas Catatan Perbaikan Latar Belakang --}}
                            <div x-show="kriteriaLatarBelakang === 'Perlu Perbaikan'" x-transition class="space-y-1">
                                <label class="block text-xs font-bold text-amber-800 uppercase tracking-wider">
                                    ⚠️ Catatan Khusus Perbaikan Latar Belakang
                                </label>
                                <textarea name="catatan_perbaikan_latar_belakang" x-model="catatanPerbaikan" rows="3"
                                    placeholder="Tuliskan poin-poin yang perlu diperbaiki oleh operator terkait latar belakang usulan belanja ini..."
                                    class="w-full text-xs rounded-xl border-amber-300 focus:border-amber-500 focus:ring-amber-500 bg-amber-50/50"></textarea>
                                <span class="text-[10px] text-amber-700">Kolom ini akan dicetak langsung pada lembar Berita Acara sebagai tindak lanjut asistensi.</span>
                            </div>
                        </div>

                        {{-- Catatan Umum Hasil Desk --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Hasil Asistensi / Desk</label>
                            <textarea name="catatan" x-model="catatan" rows="2" class="w-full text-xs rounded-xl border-gray-300"></textarea>
                        </div>

                        {{-- Anggota Tim Asistensi (Dinamis) --}}
                        <div class="border-t border-gray-200 pt-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold text-gray-700 uppercase">Anggota Tim Asistensi (Sisi Kiri)</label>
                                <button type="button" @click="addTimMember()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold">
                                    + Tambah Tim
                                </button>
                            </div>
                            <template x-for="(member, idx) in timAsistensi" :key="idx">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400 font-mono w-4" x-text="idx + 1 + '.'"></span>
                                    <input type="text" name="tim_asistensi[]" x-model="timAsistensi[idx]" placeholder="Nama Anggota Tim Asistensi & Gelar"
                                        class="w-full text-xs rounded-lg border-gray-300 py-1.5" required>
                                    <button type="button" @click="removeTimMember(idx)" class="text-red-500 hover:text-red-700 px-1 text-sm font-bold" title="Hapus">
                                        &times;
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Anggota Sub Unit Asistensi (Dinamis) --}}
                        <div class="border-t border-gray-200 pt-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold text-gray-700 uppercase">Anggota Sub Unit Asistensi (Sisi Kanan)</label>
                                <button type="button" @click="addAnggotaSubUnit()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold">
                                    + Tambah Anggota
                                </button>
                            </div>
                            <template x-for="(subMember, idx) in anggotaSubUnit" :key="idx">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400 font-mono w-4" x-text="idx + 1 + '.'"></span>
                                    <input type="text" name="anggota_sub_unit[]" x-model="anggotaSubUnit[idx]" placeholder="Nama Anggota Sub Unit / Pejabat"
                                        class="w-full text-xs rounded-lg border-gray-300 py-1.5" required>
                                    <button type="button" @click="removeAnggotaSubUnit(idx)" class="text-red-500 hover:text-red-700 px-1 text-sm font-bold" title="Hapus">
                                        &times;
                                    </button>
                                </div>
                            </template>
                        </div>

                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-6 py-3.5 flex justify-end gap-2 border-t border-gray-100">
                        <button type="button" @click="openBaModal = false"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 shadow-md transition">
                            Simpan Parameter Berita Acara
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>

</div>
