<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaSubmission;
use App\Models\RbaDeskVerification;
use App\Models\RbaDeskVerificationDocument;
use App\Models\RbaDetail;
use App\Models\RbaHeader;
use App\Models\RbaAccountPagu;
use App\Models\AccountCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeskVerificationController extends Controller
{
    /**
     * Menyimpan atau memperbarui parameter Berita Acara Asistensi / Desk RBA.
     */
    public function storeOrUpdate(Request $request, RbaSubmission $submission)
    {
        $user = Auth::user();

        // Otorisasi: Operator harus sesuai unit, atau Administrator / Supervisor
        if ($submission->unit_id !== $user->unit_id && !in_array($user->role, ['Administrator', 'Supervisor'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk unit kerja ini.');
        }

        if ($user->role === 'Operator' && !$user->isProposer()) {
            abort(403, 'Hanya operator pengusul yang dapat mengatur Berita Acara (Mode Peninjau).');
        }

        $targetUserId = $request->input('user_id', $user->id);
        if ($user->role === 'Operator') {
            $targetUserId = $user->id;
        }

        $request->validate([
            'hari' => 'required|string|max:50',
            'tanggal_desk' => 'required|date',
            'tanggal_desk_spelled' => 'required|string|max:255',
            'ruang_desk' => 'required|string|max:255',
            'sub_unit_name' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
            'is_usulan_sipakar' => 'required|in:Ya,Tidak',
            'kriteria_latar_belakang' => 'required|in:Ya,Tidak,Perlu Perbaikan',
            'catatan_perbaikan_latar_belakang' => 'nullable|required_if:kriteria_latar_belakang,Perlu Perbaikan|string',
            'is_dokumen_rab_uploaded' => 'required|in:Ya,Tidak',
            'tim_asistensi' => 'nullable|array',
            'tim_asistensi.*' => 'nullable|string|max:255',
            'anggota_sub_unit' => 'nullable|array',
            'anggota_sub_unit.*' => 'nullable|string|max:255',
        ]);

        // Bersihkan array dari elemen kosong
        $timAsistensi = array_values(array_filter(array_map('trim', $request->input('tim_asistensi', []))));
        $anggotaSubUnit = array_values(array_filter(array_map('trim', $request->input('anggota_sub_unit', []))));

        $targetUser = User::find($targetUserId);
        $subUnitName = $request->sub_unit_name ?: ($targetUser?->subUnit?->name ?? ($submission->unit->name ?? 'Unit'));

        $data = [
            'hari' => $request->hari,
            'tanggal_desk' => $request->tanggal_desk,
            'tanggal_desk_spelled' => $request->tanggal_desk_spelled,
            'ruang_desk' => $request->ruang_desk,
            'sub_unit_name' => $subUnitName,
            'catatan' => $request->catatan,
            'is_usulan_sipakar' => $request->is_usulan_sipakar,
            'kriteria_latar_belakang' => $request->kriteria_latar_belakang,
            'catatan_perbaikan_latar_belakang' => $request->kriteria_latar_belakang === 'Perlu Perbaikan'
                ? $request->catatan_perbaikan_latar_belakang
                : null,
            'is_dokumen_rab_uploaded' => $request->is_dokumen_rab_uploaded,
            'tim_asistensi' => $timAsistensi,
            'anggota_sub_unit' => $anggotaSubUnit,
            'created_by' => $user->id,
        ];

        RbaDeskVerification::updateOrCreate(
            ['rba_submission_id' => $submission->id, 'user_id' => $targetUserId],
            $data
        );

        return back()->with('success', 'Parameter Berita Acara Asistensi / Desk berhasil disimpan.');
    }

    /**
     * Menampilkan lembar cetak Berita Acara Asistensi / Desk RBA.
     */
    public function print(Request $request, RbaSubmission $submission, ?User $operator = null)
    {
        $user = Auth::user();

        // Otorisasi
        if ($submission->unit_id !== $user->unit_id && !in_array($user->role, ['Administrator', 'Supervisor'])) {
            abort(403);
        }

        $targetUser = $operator;
        if (!$targetUser) {
            $operatorId = $request->query('user_id', $user->id);
            $targetUser = User::find($operatorId) ?? $user;
        }

        // Jika operator melihat data orang lain dan bukan proposer/admin
        if ($user->role === 'Operator' && $targetUser->id !== $user->id && $user->sub_unit_id !== $targetUser->sub_unit_id) {
            abort(403, 'Anda hanya dapat mencetak Berita Acara untuk akun atau sub-unit Anda.');
        }

        $deskVerification = RbaDeskVerification::where('rba_submission_id', $submission->id)
            ->where('user_id', $targetUser->id)
            ->first();

        // Jika belum ada data di database, buat objek in-memory default
        if (!$deskVerification) {
            $components = RbaDeskVerification::parseDateComponents(Carbon::today());
            $deskVerification = new RbaDeskVerification([
                'rba_submission_id' => $submission->id,
                'user_id' => $targetUser->id,
                'hari' => $components['hari'],
                'tanggal_desk' => $components['tanggal_desk'],
                'tanggal_desk_spelled' => $components['tanggal_desk_spelled'],
                'ruang_desk' => 'Ruang RA. Kardinah',
                'sub_unit_name' => $targetUser->subUnit?->name ?? ($submission->unit->name ?? 'Unit'),
                'catatan' => 'Catatan hasil asistensi/desk terlampir.',
                'is_usulan_sipakar' => 'Ya',
                'kriteria_latar_belakang' => 'Ya',
                'catatan_perbaikan_latar_belakang' => null,
                'is_dokumen_rab_uploaded' => 'Ya',
                'tim_asistensi' => ['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom', 'Nurul L. R., S.I.Pus.'],
                'anggota_sub_unit' => [$targetUser->name],
            ]);
        }

        // Menghitung komparasi rekening belanja (AWAL vs PERUBAHAN vs SELISIH)
        $currentHeader = $submission->header;
        $currentPeriodName = $currentHeader->period->name ?? '';

        // Cari header periode sebelumnya (Awal)
        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = RbaHeader::where('year', $currentHeader->year)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', fn($q) => $q->where('name', 'like', '%Murni%'))
                ->first();
        } else {
            $previousHeader = RbaHeader::where('year', $currentHeader->year - 1)
                ->whereHas('period', fn($q) => $q->where('name', 'like', '%Perubahan%'))
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        // Rincian belanja saat ini (Perubahan) dari operator bersangkutan
        $currentDetails = RbaDetail::where('rba_submission_id', $submission->id)
            ->where('created_by', $targetUser->id)
            ->with('accountCode')
            ->get();

        // Rincian belanja sebelumnya (Awal) dari operator bersangkutan
        $previousDetails = collect();
        if ($previousHeader) {
            $prevSubmission = RbaSubmission::where('rba_header_id', $previousHeader->id)
                ->where('unit_id', $submission->unit_id)
                ->first();

            if ($prevSubmission) {
                $previousDetails = RbaDetail::where('rba_submission_id', $prevSubmission->id)
                    ->where(function ($q) use ($targetUser) {
                        $q->where('created_by', $targetUser->id);
                        if ($targetUser->sub_unit_id) {
                            $q->orWhereHas('creator', fn($cq) => $cq->where('sub_unit_id', $targetUser->sub_unit_id));
                        }
                    })
                    ->with('accountCode')
                    ->get();
            }
        }

        // Ambil pagu rekening periode sebelumnya sebagai fallback jika detail murni belum terperinci
        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        // Gabungkan seluruh kode rekening yang ada
        $accountCodeIds = $currentDetails->pluck('account_code_id')
            ->merge($previousDetails->pluck('account_code_id'))
            ->unique()
            ->filter();

        $accountCodes = AccountCode::whereIn('id', $accountCodeIds)->orderBy('code')->get();

        $rekeningRows = [];
        $totalAwal = 0;
        $totalPerubahan = 0;

        foreach ($accountCodes as $index => $ac) {
            // Hitung nilai saat ini (Perubahan)
            $perubahanVal = (float) $currentDetails->where('account_code_id', $ac->id)
                ->sum(fn($d) => (float) ($d->nominal_request ?: ($d->volume * ($d->harga_satuan ?? 0))));

            // Hitung nilai awal (Awal)
            $prevDetailSum = (float) $previousDetails->where('account_code_id', $ac->id)
                ->sum(fn($d) => (float) ($d->nominal_request ?: ($d->volume * ($d->harga_satuan ?? 0))));

            if ($prevDetailSum > 0) {
                $awalVal = $prevDetailSum;
            } elseif ($previousPagus->has($ac->id)) {
                $awalVal = (float) $previousPagus->get($ac->id)->nominal_pagu;
            } else {
                $awalVal = 0;
            }

            $selisih = $perubahanVal - $awalVal;

            $rekeningRows[] = [
                'no' => $index + 1,
                'code' => $ac->code,
                'name' => $ac->name,
                'awal' => $awalVal,
                'perubahan' => $perubahanVal,
                'selisih' => $selisih,
            ];

            $totalAwal += $awalVal;
            $totalPerubahan += $perubahanVal;
        }

        $totalSelisih = $totalPerubahan - $totalAwal;

        return view('reports.berita_acara_desk_print', compact(
            'submission',
            'deskVerification',
            'targetUser',
            'rekeningRows',
            'totalAwal',
            'totalPerubahan',
            'totalSelisih'
        ));
    }

    /**
     * Mengunggah dokumen scan Berita Acara yang telah ditandatangani manual (dengan versioning).
     */
    public function uploadSignedDocument(Request $request, RbaSubmission $submission)
    {
        $user = Auth::user();

        if ($submission->unit_id !== $user->unit_id && !in_array($user->role, ['Administrator', 'Supervisor'])) {
            abort(403);
        }

        if ($user->role === 'Operator' && !$user->isProposer()) {
            abort(403, 'Hanya operator pengusul yang dapat mengunggah dokumen Berita Acara.');
        }

        $targetUserId = $request->input('user_id', $user->id);
        if ($user->role === 'Operator') {
            $targetUserId = $user->id;
        }

        $request->validate([
            'attachment' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'notes' => 'nullable|string|max:255',
        ]);

        $targetUser = User::find($targetUserId) ?? $user;

        DB::transaction(function () use ($request, $submission, $targetUser, $user) {
            $components = RbaDeskVerification::parseDateComponents(Carbon::today());

            // Pastikan data induk desk verification ada
            $deskVerification = RbaDeskVerification::firstOrCreate(
                ['rba_submission_id' => $submission->id, 'user_id' => $targetUser->id],
                [
                    'hari' => $components['hari'],
                    'tanggal_desk' => $components['tanggal_desk'],
                    'tanggal_desk_spelled' => $components['tanggal_desk_spelled'],
                    'ruang_desk' => 'Ruang RA. Kardinah',
                    'sub_unit_name' => $targetUser->subUnit?->name ?? ($submission->unit->name ?? 'Unit'),
                    'is_usulan_sipakar' => 'Ya',
                    'kriteria_latar_belakang' => 'Ya',
                    'is_dokumen_rab_uploaded' => 'Ya',
                    'tim_asistensi' => ['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom', 'Nurul L. R., S.I.Pus.'],
                    'anggota_sub_unit' => [$targetUser->name],
                    'created_by' => $user->id,
                ]
            );

            $latestVersion = $deskVerification->documents()->max('version_number') ?? 0;
            $newVersion = $latestVersion + 1;

            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('berita_acara', 'public');

            RbaDeskVerificationDocument::create([
                'rba_desk_verification_id' => $deskVerification->id,
                'version_number' => $newVersion,
                'file_path' => $path,
                'original_filename' => $originalName,
                'notes' => $request->notes,
                'uploaded_by' => $user->id,
            ]);
        });

        return back()->with('success', 'Berkas scan Berita Acara Ditandatangani berhasil diunggah.');
    }

    /**
     * Menampilkan modal / riwayat versi dokumen Berita Acara yang telah diunggah.
     */
    public function history(Request $request, RbaSubmission $submission, ?User $operator = null)
    {
        $user = Auth::user();

        if ($submission->unit_id !== $user->unit_id && !in_array($user->role, ['Administrator', 'Supervisor'])) {
            abort(403);
        }

        $targetUser = $operator;
        if (!$targetUser) {
            $operatorId = $request->query('user_id', $user->id);
            $targetUser = User::find($operatorId) ?? $user;
        }

        $deskVerification = RbaDeskVerification::where('rba_submission_id', $submission->id)
            ->where('user_id', $targetUser->id)
            ->with(['documents.uploader'])
            ->first();

        $documents = $deskVerification ? $deskVerification->documents : collect();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'user' => $targetUser->name,
                'sub_unit' => $targetUser->subUnit?->name ?? ($submission->unit->name ?? 'Unit'),
                'documents' => $documents->map(fn($d) => [
                    'id' => $d->id,
                    'version_number' => $d->version_number,
                    'original_filename' => $d->original_filename,
                    'file_url' => Storage::url($d->file_path),
                    'notes' => $d->notes,
                    'uploaded_by' => $d->uploader?->name ?? 'System',
                    'created_at' => $d->created_at->format('d M Y, H:i'),
                ]),
            ]);
        }

        return view('operator.submissions.berita_acara_history', compact('submission', 'targetUser', 'deskVerification', 'documents'));
    }
}
