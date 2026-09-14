<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1 rounded-md">
                        {{ $rkbmd->nomor_permohonan }}
                    </span>
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
                        $badgeCls = $statusClasses[$rkbmd->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                    @endphp
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $badgeCls }}">
                        Status: {{ $rkbmd->status }}
                    </span>
                </div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight mt-1.5">
                    {{ $rkbmd->title }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('operator.rkbmd.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    ← Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ replyModalOpen: false, forwardModalOpen: false }">
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

            <!-- ACTION BAR UNTUK OPERATOR TUJUAN (JIKA BELUM FINAL) -->
            @if($rkbmd->canBeManagedBy(Auth::user()))
                <div class="bg-gradient-to-r from-indigo-50 via-sky-50 to-white border border-indigo-200 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-indigo-600 text-white rounded-lg text-xs">🔔</span>
                            <h3 class="text-sm font-extrabold text-slate-900">Tindakan Diperlukan: Anda Adalah Pemegang Berkas Aktif Saat Ini</h3>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                            Silakan telaah kebutuhan barang di bawah ini. Anda dapat memberikan <strong>Balasan Keputusan</strong> atau <strong>Mengalihkan Berkas</strong> ke Operator Pengusul lain.
                        </p>
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0 w-full md:w-auto">
                        <!-- Tombol Skenario 1: Balasan -->
                        <button type="button" @click="replyModalOpen = true"
                            class="flex-1 md:flex-initial inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all gap-1.5">
                            <span>💬</span>
                            <span>Tanggapi / Berikan Balasan</span>
                        </button>

                        <!-- Tombol Skenario 2: Alihkan -->
                        <button type="button" @click="forwardModalOpen = true"
                            class="flex-1 md:flex-initial inline-flex items-center justify-center px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all gap-1.5">
                            <span>↪️</span>
                            <span>Alihkan ke Pengusul Lain</span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- KARTU INFORMASI PERMOHONAN & PIHAK TERKAIT -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Kolom 1 & 2: Informasi Detail Permohonan & Tabel Barang -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 space-y-4">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
                            <span>📄</span> Informasi Permohonan
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block mb-0.5">Asal Sub-Unit Pemohon:</span>
                                <span class="font-bold text-slate-800 flex items-center gap-1">
                                    <span>📌</span>
                                    <span>{{ $rkbmd->subUnit ? $rkbmd->subUnit->name : ($rkbmd->unit?->name ?? '-') }}</span>
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-400 block mb-0.5">Nama Pegawai Pemohon:</span>
                                <span class="font-bold text-slate-800 flex items-center gap-1">
                                    <span>👤</span>
                                    <span>{{ $rkbmd->applicant->name }} {{ $rkbmd->applicant->nip ? '(' . $rkbmd->applicant->nip . ')' : '' }}</span>
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-400 block mb-0.5">Tahun Anggaran Kebutuhan:</span>
                                <span class="font-bold text-indigo-700 font-mono text-sm">Tahun {{ $rkbmd->year }}</span>
                            </div>

                            <div>
                                <span class="text-slate-400 block mb-0.5">Tanggal Pengajuan:</span>
                                <span class="font-bold text-slate-700">{{ $rkbmd->created_at->format('d M Y, H:i') }} WIB</span>
                            </div>

                            <div>
                                <span class="text-slate-400 block mb-0.5">Operator Tujuan Pertama:</span>
                                <span class="font-semibold text-slate-700">{{ $rkbmd->originalOperator->name }}</span>
                            </div>

                            <div>
                                <span class="text-slate-400 block mb-0.5">Pemegang Berkas Saat Ini:</span>
                                <span class="font-bold text-indigo-600 flex items-center gap-1">
                                    <span>🎯</span>
                                    <span>{{ $rkbmd->targetOperator->name }}</span>
                                </span>
                            </div>
                        </div>

                        @if($rkbmd->notes)
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <span class="text-xs font-bold text-slate-500 block mb-1">Catatan / Latar Belakang Kebutuhan:</span>
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
                                    {{ $rkbmd->notes }}
                                </div>
                            </div>
                        @endif

                        @if($rkbmd->attachment_path)
                            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between bg-indigo-50/50 border border-indigo-100 rounded-xl p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">📄</span>
                                    <div>
                                        <div class="text-xs font-bold text-indigo-950">Dokumen Nota Dinas / Memo Intern</div>
                                        <div class="text-[11px] text-indigo-600">Berkas permohonan resmi berformat PDF</div>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($rkbmd->attachment_path) }}" target="_blank"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs gap-1">
                                    <span>👁️</span> Buka PDF
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- TABEL DAFTAR BARANG YANG DIMOHONKAN -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 space-y-4">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
                            <span>📋</span> Rincian Daftar Barang (Permendagri No. 108 Tahun 2016)
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Kode & Nama Barang BMD</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Jumlah Dimohon</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Satuan</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Spesifikasi Kebutuhan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    @foreach($rkbmd->items as $idx => $item)
                                        <tr class="hover:bg-slate-50/60">
                                            <td class="px-4 py-3 text-slate-400 font-bold">{{ $idx + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900">{{ $item->masterBarang->nama_barang }}</div>
                                                <div class="font-mono text-[11px] text-indigo-600 mt-0.5">
                                                    {{ $item->masterBarang->kode_barang }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-extrabold text-slate-900">
                                                {{ number_format($item->volume, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-700 font-medium">
                                                {{ $item->satuan }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $item->spesifikasi ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- HASIL BALASAN FINAL (JIKA SUDAH DIBALAS) -->
                    @if($rkbmd->replied_at)
                        <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 p-6 space-y-3">
                            <div class="flex items-center justify-between pb-3 border-b border-emerald-100">
                                <h3 class="text-sm font-extrabold text-emerald-900 flex items-center gap-2">
                                    <span>✅</span> Keputusan / Balasan Resmi Operator Pengusul
                                </h3>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $badgeCls }}">
                                    {{ $rkbmd->status }}
                                </span>
                            </div>

                            <div class="text-xs text-slate-600">
                                Dibalas oleh <strong>{{ $rkbmd->repliedBy?->name ?? 'Operator Pengusul' }}</strong> pada {{ $rkbmd->replied_at->format('d M Y, H:i') }} WIB
                            </div>

                            <div class="bg-emerald-50/60 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-950 whitespace-pre-wrap leading-relaxed font-medium">
                                {{ $rkbmd->reply_notes }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Kolom 3: TIMELINE RIWAYAT PENGALIHAN & AUDIT TRAIL LENGKAP -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 space-y-4 sticky top-6">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <span>📜</span> Riwayat & Jejak Audit
                            </h3>
                            <span class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                {{ $rkbmd->histories->count() }} Tahapan
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-400">
                            Seluruh histori pengajuan, pengalihan berantai, dan balasan tercatat transparan dan dapat dilihat oleh pemohon, pengalih, dan penerima baru.
                        </p>

                        <!-- Timeline Items -->
                        <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                            @foreach($rkbmd->histories as $history)
                                <div class="relative text-xs">
                                    <!-- Bullet Marker -->
                                    <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full border-2 border-white flex items-center justify-center
                                        {{ $history->action === 'Pengajuan' ? 'bg-blue-600' : ($history->action === 'Pengalihan' ? 'bg-amber-500' : 'bg-emerald-600') }} shadow-xs">
                                    </div>

                                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-slate-900">
                                                {{ $history->action }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">
                                                {{ $history->created_at->format('d/m/Y H:i') }}
                                            </span>
                                        </div>

                                        <div class="text-[11px] text-slate-600">
                                            Oleh: <strong>{{ $history->actor->name }}</strong>
                                        </div>

                                        @if($history->action === 'Pengalihan')
                                            <div class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-2 font-medium">
                                                <span>↪️ Dialihkan ke: <strong>{{ $history->toOperator?->name }}</strong></span>
                                                <div class="mt-1 text-slate-600 italic">"{{ $history->notes }}"</div>
                                            </div>
                                        @elseif($history->notes)
                                            <div class="text-[11px] text-slate-600 bg-white border border-slate-200 rounded-lg p-2">
                                                {{ $history->notes }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL 1: BALASAN KEPUTUSAN (SKENARIO 1) -->
        <div x-show="replyModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div @click.away="replyModalOpen = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-base text-slate-800 flex items-center gap-2">
                        <span>💬</span> Berikan Balasan Permohonan RKBMD
                    </h3>
                    <button type="button" @click="replyModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('operator.rkbmd.reply', $rkbmd) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="reply_status" class="block text-xs font-bold text-slate-700 mb-1">
                            Status Keputusan Balasan <span class="text-rose-500">*</span>
                        </label>
                        <select id="reply_status" name="status" required
                            class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            <option value="" disabled selected>-- Pilih Status Keputusan --</option>
                            <option value="Dipenuhi">✅ Dipenuhi (Diakomodir penuh dalam usulan belanja)</option>
                            <option value="Dipenuhi Sebagian">☑️ Dipenuhi Sebagian (Akomodir sebagian jumlah/item)</option>
                            <option value="Substitusi">🔄 Substitusi (Dipenuhi dengan spesifikasi/barang alternatif)</option>
                            <option value="Optimalisasi">🏢 Optimalisasi (Dipenuhi mutasi aset BMD eksisting)</option>
                            <option value="Ditolak">❌ Ditolak (Tidak dapat dipenuhi tahun anggaran ini)</option>
                        </select>
                    </div>

                    <div>
                        <label for="reply_notes" class="block text-xs font-bold text-slate-700 mb-1">
                            Teks Catatan / Alasan / Instruksi Balasan <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reply_notes" name="reply_notes" rows="4" required
                            class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            placeholder="Tuliskan keterangan lengkap atau tindak lanjut pemenuhan barang bagi sub-unit pemohon..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="replyModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs">
                            Kirim Balasan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: PENGALIHAN BERKAS KE PENGUSUL LAIN (SKENARIO 2) -->
        <div x-show="forwardModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div @click.away="forwardModalOpen = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-base text-slate-800 flex items-center gap-2">
                        <span>↪️</span> Alihkan Permohonan ke Pengusul Lain
                    </h3>
                    <button type="button" @click="forwardModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('operator.rkbmd.forward', $rkbmd) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="new_target_operator_id" class="block text-xs font-bold text-slate-700 mb-1">
                            Pilih Operator Pengusul Tujuan Baru <span class="text-rose-500">*</span>
                        </label>
                        <select id="new_target_operator_id" name="new_target_operator_id" required
                            class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 py-2.5">
                            <option value="" disabled selected>-- Pilih Operator Pengusul Baru --</option>
                            @foreach($otherProposers as $prop)
                                <option value="{{ $prop->id }}">
                                    👤 {{ $prop->name }} - {{ $prop->unit?->name }} {{ $prop->subUnit ? '(' . $prop->subUnit->name . ')' : '' }} {{ $prop->jabatan ? '• ' . $prop->jabatan : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Hanya menampilkan operator dengan hak pengusulan RBA (can_propose = 1).</p>
                    </div>

                    <div>
                        <label for="forward_reason" class="block text-xs font-bold text-slate-700 mb-1">
                            Keterangan / Alasan Pengalihan <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="forward_reason" name="forward_reason" rows="4" required
                            class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            placeholder="Jelaskan alasan mengapa permohonan ini dialihkan (catatan ini akan terbaca oleh pemohon dan penerima baru)..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="forwardModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs">
                            Alihkan Permohonan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
