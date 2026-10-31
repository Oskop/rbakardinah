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

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $search = $request->input('search');
        $unitId = $request->input('unit_id');

        $applyFilters = function ($query) use ($startDate, $endDate, $status, $search) {
            $query->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($subQ) use ($search) {
                        $subQ->where('nomor_permohonan', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
                    });
                });
        };

        $mySubmissions = collect();
        $incomingSubmissions = collect();
        $forwardedSubmissions = collect();
        $allSubmissions = collect();
        $supervisorSubmissions = collect();
        $units = collect();

        if ($user->role === 'Administrator') {
            // Administrator: Melihat seluruh permohonan RKBMD lintas unit (Rumah Sakit)
            $adminQuery = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'targetOperator', 'originalOperator', 'repliedBy']);
            $applyFilters($adminQuery);
            if ($unitId) {
                $adminQuery->where('unit_id', $unitId);
            }
            $allSubmissions = $adminQuery->latest('created_at')->get();
            $units = \App\Models\Unit::orderBy('name')->get();
        } elseif ($user->role === 'Supervisor') {
            // Supervisor: Dua arah (diajukan oleh sub-unit di bawah unitnya ATAU ditujukan ke sub-unit di bawah unitnya)
            $spvUnitId = $user->unit_id;
            $supervisorQuery = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'targetOperator.unit', 'originalOperator.unit', 'repliedBy'])
                ->where(function ($q) use ($spvUnitId) {
                    $q->where('unit_id', $spvUnitId)
                      ->orWhereHas('targetOperator', function ($opQ) use ($spvUnitId) {
                          $opQ->where('unit_id', $spvUnitId);
                      });
                });
            $applyFilters($supervisorQuery);
            $supervisorSubmissions = $supervisorQuery->latest('created_at')->get();
        } else {
            // Operator Role: Operasional mandiri
            // 1. Permohonan Saya (Dibuat oleh pengguna login)
            $myQuery = RkbmdSubmission::with(['targetOperator', 'subUnit', 'unit', 'items'])
                ->where('user_id', $user->id);
            $applyFilters($myQuery);
            $mySubmissions = $myQuery->latest('created_at')->get();

            if ($isProposer) {
                // 2. Permohonan Masuk (Khusus Operator Pengusul / Penerima Berkas Saat Ini)
                $incomingQuery = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'originalOperator'])
                    ->where('target_operator_id', $user->id);
                $applyFilters($incomingQuery);
                $incomingSubmissions = $incomingQuery->latest('created_at')->get();

                // 3. Permohonan yang Dialihkan oleh Operator Pengusul ini
                $forwardedQuery = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'targetOperator', 'histories'])
                    ->whereHas('histories', function ($q) use ($user) {
                        $q->where('action', 'Pengalihan')
                            ->where('from_operator_id', $user->id);
                    })
                    ->where('target_operator_id', '!=', $user->id);
                $applyFilters($forwardedQuery);
                $forwardedSubmissions = $forwardedQuery->latest('updated_at')->get();
            }
        }

        return view('operator.rkbmd.index', compact(
            'mySubmissions',
            'incomingSubmissions',
            'forwardedSubmissions',
            'allSubmissions',
            'supervisorSubmissions',
            'units',
            'unitId',
            'isProposer',
            'startDate',
            'endDate',
            'status',
            'search'
        ));
    }

    /**
     * Endpoint API pencarian master barang (Permendagri 108 / SIPAKAR) secara async
     */
    public function searchMasterBarangs(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $level5 = trim((string) $request->input('level5', ''));
        $level6 = trim((string) $request->input('level6', ''));
        $limit = min(max((int) $request->input('limit', 20), 1), 50);

        $query = MasterBarang::active();

        if ($level5 !== '') {
            $query->where('kode_level5', $level5);
        }

        if ($level6 !== '') {
            $query->where('kode_level6', $level6);
        }

        $items = $query->search($q)
            ->orderBy('nama_barang')
            ->limit($limit)
            ->get(['id', 'kode_barang', 'kode_permendagri', 'kode_level5', 'nama_level5', 'kode_level6', 'nama_level6', 'nama_barang', 'satuan', 'deskripsi']);

        return response()->json($items);
    }

    public function create()
    {
        if (Auth::user()->role !== 'Operator') {
            abort(403, 'Akses Ditolak: Hanya Operator yang dapat membuat permohonan RKBMD.');
        }

        $user = Auth::user();

        // Operator Pengusul yang dapat dituju (can_propose = true, aktif, bukan diri sendiri)
        $targetOperators = User::where('role', 'Operator')
            ->where('can_propose', true)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->with(['unit', 'subUnit'])
            ->orderBy('name')
            ->get();

        // Daftar Kategori Level 5 untuk opsi filter cepat pada formulir
        $level5List = MasterBarang::whereNotNull('kode_level5')
            ->select('kode_level5', 'nama_level5')
            ->distinct()
            ->orderBy('nama_level5')
            ->get();

        // Sampel awal Master Barang (15 item saja untuk kecepatan render instan & hemat memori)
        $masterBarangs = MasterBarang::active()
            ->orderBy('nama_barang')
            ->limit(15)
            ->get(['id', 'kode_barang', 'kode_permendagri', 'kode_level5', 'nama_level5', 'kode_level6', 'nama_level6', 'nama_barang', 'satuan', 'deskripsi']);

        return view('operator.rkbmd.create', compact('user', 'targetOperators', 'masterBarangs', 'level5List'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'Operator') {
            abort(403, 'Akses Ditolak: Hanya Operator yang dapat membuat permohonan RKBMD.');
        }

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

        // Pastikan hak akses lihat (Pemohon, Target saat ini, Pengalih terdahulu, SPV unit pemohon ATAU unit target, atau Admin)
        $isParticipant = $rkbmd->user_id === $user->id
            || $rkbmd->target_operator_id === $user->id
            || $rkbmd->histories()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('from_operator_id', $user->id)
                    ->orWhere('to_operator_id', $user->id);
            })->exists()
            || ($user->role === 'Supervisor' && ($user->unit_id === $rkbmd->unit_id || $user->unit_id === $rkbmd->targetOperator?->unit_id))
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

        $rkbmd->load(['items.masterBarang', 'subUnit', 'unit', 'targetOperator']);

        // Hanya muat barang yang terpilih di submission ini dan sampel awal (hemat memori)
        $selectedIds = $rkbmd->items->pluck('master_barang_id')->filter()->unique();
        $selectedBarangs = MasterBarang::whereIn('id', $selectedIds)
            ->get(['id', 'kode_barang', 'kode_permendagri', 'kode_level5', 'nama_level5', 'kode_level6', 'nama_level6', 'nama_barang', 'satuan', 'deskripsi']);
        $sampleBarangs = MasterBarang::active()
            ->orderBy('nama_barang')
            ->limit(15)
            ->get(['id', 'kode_barang', 'kode_permendagri', 'kode_level5', 'nama_level5', 'kode_level6', 'nama_level6', 'nama_barang', 'satuan', 'deskripsi']);
        $masterBarangs = $selectedBarangs->merge($sampleBarangs)->unique('id')->values();

        $level5List = MasterBarang::whereNotNull('kode_level5')
            ->select('kode_level5', 'nama_level5')
            ->distinct()
            ->orderBy('nama_level5')
            ->get();

        return view('operator.rkbmd.edit', compact('rkbmd', 'targetOperators', 'masterBarangs', 'user', 'level5List'));
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

