<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MasterBarang;
use App\Models\RkbmdHistory;
use App\Models\RkbmdItem;
use App\Models\RkbmdSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RkbmdController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isProposer = $user->isProposer();

        // 1. Permohonan Saya (Dibuat oleh pengguna login)
        $mySubmissions = RkbmdSubmission::with(['targetOperator', 'subUnit', 'unit', 'items'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // 2. Permohonan Masuk (Khusus Operator Pengusul / Penerima Berkas Saat Ini)
        $incomingSubmissions = collect();
        $forwardedSubmissions = collect();

        if ($isProposer) {
            $incomingSubmissions = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'originalOperator'])
                ->where('target_operator_id', $user->id)
                ->latest()
                ->get();

            // 3. Permohonan yang Dialihkan oleh Operator Pengusul ini
            $forwardedSubmissions = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'targetOperator', 'histories'])
                ->whereHas('histories', function ($q) use ($user) {
                    $q->where('action', 'Pengalihan')
                        ->where('from_operator_id', $user->id);
                })
                ->where('target_operator_id', '!=', $user->id)
                ->latest('updated_at')
                ->get();
        }

        return view('operator.rkbmd.index', compact('mySubmissions', 'incomingSubmissions', 'forwardedSubmissions', 'isProposer'));
    }

    public function create()
    {
        $user = Auth::user();

        // Operator Pengusul yang dapat dituju (can_propose = true, aktif, bukan diri sendiri)
        $targetOperators = User::where('role', 'Operator')
            ->where('can_propose', true)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->with(['unit', 'subUnit'])
            ->orderBy('name')
            ->get();

        // Master Barang Permendagri 108/2016 aktif
        $masterBarangs = MasterBarang::active()->orderBy('nama_barang')->get();

        return view('operator.rkbmd.create', compact('user', 'targetOperators', 'masterBarangs'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'target_operator_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($user) {
                    $target = User::find($value);
                    if (!$target || $target->role !== 'Operator' || !$target->can_propose || !$target->is_active) {
                        $fail('Operator tujuan harus merupakan akun Operator aktif yang memiliki hak pengusulan RBA.');
                    }
                    if ($target->id === $user->id) {
                        $fail('Anda tidak dapat memilih akun Anda sendiri sebagai operator tujuan.');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'year' => 'required|digits:4',
            'notes' => 'nullable|string',
            'attachment' => 'required|file|mimes:pdf|max:10240', // 10MB
            'items' => 'required|array|min:1',
            'items.*.master_barang_id' => 'required|exists:master_barangs,id',
            'items.*.volume' => 'required|numeric|min:0.01',
            'items.*.satuan' => 'required|string|max:50',
            'items.*.spesifikasi' => 'nullable|string',
        ]);

        $year = (int) $request->year;
        $month = now()->format('m');

        // Generate Nomor Surat: RKBMD/YYYY/MM/XXXX
        $lastTicket = RkbmdSubmission::where('year', $year)->count();
        $sequence = str_pad($lastTicket + 1, 4, '0', STR_PAD_LEFT);
        $nomorPermohonan = "RKBMD/{$year}/{$month}/{$sequence}";

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('rkbmd_attachments', 'public');
        }

        $submission = DB::transaction(function () use ($request, $user, $year, $nomorPermohonan, $attachmentPath) {
            $sub = RkbmdSubmission::create([
                'nomor_permohonan' => $nomorPermohonan,
                'year' => $year,
                'user_id' => $user->id,
                'unit_id' => $user->unit_id ?? 1,
                'sub_unit_id' => $user->sub_unit_id,
                'target_operator_id' => $request->target_operator_id,
                'original_operator_id' => $request->target_operator_id,
                'title' => $request->title,
                'notes' => $request->notes,
                'attachment_path' => $attachmentPath,
                'status' => 'Diajukan',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($request->items as $itemData) {
                RkbmdItem::create([
                    'rkbmd_submission_id' => $sub->id,
                    'master_barang_id' => $itemData['master_barang_id'],
                    'volume' => $itemData['volume'],
                    'satuan' => $itemData['satuan'],
                    'spesifikasi' => $itemData['spesifikasi'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            // Catat Riwayat Pengajuan Pertama
            RkbmdHistory::create([
                'rkbmd_submission_id' => $sub->id,
                'user_id' => $user->id,
                'action' => 'Pengajuan',
                'to_operator_id' => $request->target_operator_id,
                'status_before' => null,
                'status_after' => 'Diajukan',
                'notes' => "Permohonan diajukan oleh {$user->name} (" . ($user->subUnit?->name ?? 'Sub-Unit') . ")",
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            return $sub;
        });

        return redirect()->route('operator.rkbmd.show', $submission)
            ->with('success', "Permohonan RKBMD ({$submission->nomor_permohonan}) berhasil diajukan.");
    }

    public function show(RkbmdSubmission $rkbmd)
    {
        $user = Auth::user();

        // Pastikan hak akses lihat (Pemohon, Target saat ini, Pengalih terdahulu, SPV unit, atau Admin)
        $isParticipant = $rkbmd->user_id === $user->id
            || $rkbmd->target_operator_id === $user->id
            || $rkbmd->histories()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('from_operator_id', $user->id)
                    ->orWhere('to_operator_id', $user->id);
            })->exists()
            || ($user->role === 'Supervisor' && $user->unit_id === $rkbmd->unit_id)
            || $user->role === 'Administrator';

        if (!$isParticipant) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat permohonan RKBMD ini.');
        }

        $rkbmd->load([
            'applicant',
            'unit',
            'subUnit',
            'targetOperator',
            'originalOperator',
            'repliedBy',
            'items.masterBarang',
            'histories.actor',
            'histories.fromOperator',
            'histories.toOperator',
        ]);

        // Daftar operator yang dapat dipilih jika ingin dialihkan (Skenario 2)
        $otherProposers = collect();
        if ($rkbmd->canBeManagedBy($user)) {
            $otherProposers = User::where('role', 'Operator')
                ->where('can_propose', true)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->where('id', '!=', $rkbmd->user_id)
                ->with(['unit', 'subUnit'])
                ->orderBy('name')
                ->get();
        }

        return view('operator.rkbmd.show', compact('rkbmd', 'otherProposers'));
    }

    /**
     * Skenario 1: Menanggapi / Membalas Permohonan RKBMD
     */
    public function reply(Request $request, RkbmdSubmission $rkbmd)
    {
        $user = Auth::user();

        if (!$rkbmd->canBeManagedBy($user)) {
            abort(403, 'Anda tidak berwenang memberikan balasan pada permohonan RKBMD ini.');
        }

        $request->validate([
            'status' => 'required|in:Dipenuhi,Dipenuhi Sebagian,Substitusi,Optimalisasi,Ditolak',
            'reply_notes' => 'required|string|max:2000',
            'item_status' => 'nullable|array',
            'item_volume_approved' => 'nullable|array',
            'item_notes' => 'nullable|array',
        ]);

        $statusBefore = $rkbmd->status;

        DB::transaction(function () use ($request, $rkbmd, $user, $statusBefore) {
            $rkbmd->update([
                'status' => $request->status,
                'reply_notes' => $request->reply_notes,
                'replied_at' => now(),
                'replied_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Update status & volume per item jika ada input rincian
            if ($request->has('item_status')) {
                foreach ($request->item_status as $itemId => $itemStatus) {
                    $item = RkbmdItem::where('rba_submission_id', $rkbmd->id)->where('id', $itemId)->first();
                    if ($item) {
                        $item->update([
                            'status_item' => $itemStatus,
                            'volume_disetujui' => $request->item_volume_approved[$itemId] ?? null,
                            'catatan_operator' => $request->item_notes[$itemId] ?? null,
                            'updated_by' => $user->id,
                        ]);
                    }
                }
            }

            // Catat Riwayat Balasan
            RkbmdHistory::create([
                'rkbmd_submission_id' => $rkbmd->id,
                'user_id' => $user->id,
                'action' => 'Balasan',
                'status_before' => $statusBefore,
                'status_after' => $request->status,
                'notes' => "Balasan dari {$user->name} [Status: {$request->status}]: {$request->reply_notes}",
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });

        return redirect()->route('operator.rkbmd.show', $rkbmd)
            ->with('success', "Permohonan RKBMD telah berhasil dibalas dengan status: {$request->status}.");
    }

    /**
     * Memperbarui / Mengedit Balasan Permohonan RKBMD (Jika terjadi kesalahan penginputan)
     */
    public function updateReply(Request $request, RkbmdSubmission $rkbmd)
    {
        $user = Auth::user();

        if (!$rkbmd->canEditReply($user)) {
            abort(403, 'Anda tidak berwenang mengedit balasan permohonan RKBMD ini.');
        }

        $request->validate([
            'status' => 'required|in:Dipenuhi,Dipenuhi Sebagian,Substitusi,Optimalisasi,Ditolak',
            'reply_notes' => 'required|string|max:2000',
        ]);

        $statusBefore = $rkbmd->status;

        DB::transaction(function () use ($request, $rkbmd, $user, $statusBefore) {
            $rkbmd->update([
                'status' => $request->status,
                'reply_notes' => $request->reply_notes,
                'updated_by' => $user->id,
            ]);

            // Catat Riwayat Edit Balasan
            RkbmdHistory::create([
                'rkbmd_submission_id' => $rkbmd->id,
                'user_id' => $user->id,
                'action' => 'Edit Balasan',
                'status_before' => $statusBefore,
                'status_after' => $request->status,
                'notes' => "Revisi balasan oleh {$user->name} [Status: {$request->status}]: {$request->reply_notes}",
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });

        return redirect()->route('operator.rkbmd.show', $rkbmd)
            ->with('success', "Balasan permohonan RKBMD ({$rkbmd->nomor_permohonan}) berhasil diperbarui.");
    }

    /**
     * Skenario 2: Mengalihkan (Forward) Permohonan RKBMD ke Operator Pengusul Lain
     */
    public function forward(Request $request, RkbmdSubmission $rkbmd)
    {
        $user = Auth::user();

        if (!$rkbmd->canBeManagedBy($user)) {
            abort(403, 'Anda tidak berwenang mengalihkan permohonan RKBMD ini.');
        }

        $request->validate([
            'new_target_operator_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($user, $rkbmd) {
                    $target = User::find($value);
                    if (!$target || $target->role !== 'Operator' || !$target->can_propose || !$target->is_active) {
                        $fail('Operator tujuan alihan harus merupakan akun Operator aktif yang memiliki hak pengusulan RBA.');
                    }
                    if ($target->id === $user->id) {
                        $fail('Anda tidak dapat mengalihkan permohonan ke akun Anda sendiri.');
                    }
                },
            ],
            'forward_reason' => 'required|string|max:2000',
        ]);

        $statusBefore = $rkbmd->status;
        $newTarget = User::findOrFail($request->new_target_operator_id);

        DB::transaction(function () use ($request, $rkbmd, $user, $newTarget, $statusBefore) {
            $rkbmd->update([
                'target_operator_id' => $newTarget->id,
                'status' => 'Dialihkan',
                'updated_by' => $user->id,
            ]);

            // Catat Riwayat Pengalihan Berantai Lengkap
            RkbmdHistory::create([
                'rkbmd_submission_id' => $rkbmd->id,
                'user_id' => $user->id,
                'action' => 'Pengalihan',
                'from_operator_id' => $user->id,
                'to_operator_id' => $newTarget->id,
                'status_before' => $statusBefore,
                'status_after' => 'Dialihkan',
                'notes' => $request->forward_reason,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });

        return redirect()->route('operator.rkbmd.show', $rkbmd)
            ->with('success', "Permohonan RKBMD telah berhasil dialihkan ke operator {$newTarget->name}.");
    }

    /**
     * Menampilkan formulir edit permohonan RKBMD bagi pemohon
     */
    public function edit(RkbmdSubmission $rkbmd)
    {
        $user = Auth::user();

        if (!$rkbmd->canEditSubmission($user)) {
            abort(403, 'Permohonan RKBMD ini tidak dapat diedit karena sudah diproses/dialihkan atau Anda tidak memiliki hak akses.');
        }

        // Daftar operator pengusul aktif (can_propose = 1) kecuali pemohon sendiri
        $targetOperators = User::where('role', 'Operator')
            ->where('can_propose', true)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->with(['unit', 'subUnit'])
            ->orderBy('name')
            ->get();

        $masterBarangs = MasterBarang::orderBy('kode_barang')->get(['id', 'kode_barang', 'nama_barang', 'satuan']);

        $rkbmd->load(['items.masterBarang', 'subUnit', 'unit', 'targetOperator']);

        return view('operator.rkbmd.edit', compact('rkbmd', 'targetOperators', 'masterBarangs', 'user'));
    }

    /**
     * Memperbarui data permohonan RKBMD oleh pemohon
     */
    public function update(Request $request, RkbmdSubmission $rkbmd)
    {
        $user = Auth::user();

        if (!$rkbmd->canEditSubmission($user)) {
            abort(403, 'Permohonan RKBMD ini tidak dapat diedit karena sudah diproses/dialihkan atau Anda tidak memiliki hak akses.');
        }

        $request->validate([
            'target_operator_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($user) {
                    $target = User::find($value);
                    if (!$target || $target->role !== 'Operator' || !$target->can_propose || !$target->is_active) {
                        $fail('Operator tujuan harus merupakan akun Operator aktif yang memiliki hak pengusulan RBA.');
                    }
                    if ($target->id === $user->id) {
                        $fail('Anda tidak dapat memilih akun Anda sendiri sebagai operator tujuan.');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'year' => 'required|integer|digits:4',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf|max:10240',
            'items' => 'required|array|min:1',
            'items.*.master_barang_id' => 'required|exists:master_barangs,id',
            'items.*.volume' => 'required|numeric|min:0.01',
            'items.*.satuan' => 'required|string|max:50',
            'items.*.spesifikasi' => 'nullable|string|max:255',
        ]);

        $attachmentPath = $rkbmd->attachment_path;
        if ($request->hasFile('attachment')) {
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = $request->file('attachment')->store('rkbmd_attachments', 'public');
        }

        DB::transaction(function () use ($request, $rkbmd, $user, $attachmentPath) {
            $rkbmd->update([
                'target_operator_id' => $request->target_operator_id,
                'original_operator_id' => $request->target_operator_id,
                'title' => $request->title,
                'year' => $request->year,
                'notes' => $request->notes,
                'attachment_path' => $attachmentPath,
                'updated_by' => $user->id,
            ]);

            // Sinkronisasi item: hapus lama dan buat yang baru
            $rkbmd->items()->delete();

            foreach ($request->items as $itemData) {
                RkbmdItem::create([
                    'rkbmd_submission_id' => $rkbmd->id,
                    'master_barang_id' => $itemData['master_barang_id'],
                    'volume' => $itemData['volume'],
                    'satuan' => $itemData['satuan'],
                    'spesifikasi' => $itemData['spesifikasi'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            // Catat Riwayat Edit Permohonan
            RkbmdHistory::create([
                'rkbmd_submission_id' => $rkbmd->id,
                'user_id' => $user->id,
                'action' => 'Edit Permohonan',
                'to_operator_id' => $request->target_operator_id,
                'status_before' => 'Diajukan',
                'status_after' => 'Diajukan',
                'notes' => "Permohonan diperbarui oleh pemohon ({$user->name})",
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });

        return redirect()->route('operator.rkbmd.show', $rkbmd)
            ->with('success', "Permohonan RKBMD ({$rkbmd->nomor_permohonan}) berhasil diperbarui.");
    }
}

