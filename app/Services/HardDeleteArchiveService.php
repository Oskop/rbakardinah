<?php

namespace App\Services;

use App\Models\HardDeleteArchive;
use App\Models\RbaHeader;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class HardDeleteArchiveService
{
    /**
     * Capture full snapshot and hard delete an RBA Header and all its relations.
     */
    public function deleteRbaHeader(RbaHeader $header, ?string $reason = null, ?int $userId = null): HardDeleteArchive
    {
        return DB::transaction(function () use ($header, $reason, $userId) {
            $headerId = $header->id;
            $periodName = $header->period->name ?? 'RBA';
            $label = "RBA Header {$header->year} - {$periodName} (ID #{$headerId})";

            // 1. Snapshot all related tables
            $snapshot = $this->captureRbaHeaderTree($header);

            // 2. Save snapshot to hard_delete_archives
            $archive = HardDeleteArchive::create([
                'model_type' => RbaHeader::class,
                'model_id' => $headerId,
                'label' => $label,
                'reason' => $reason ?: 'Penghapusan data RBA salah input',
                'root_data' => $snapshot['root_data'],
                'related_data' => $snapshot['related_data'],
                'metadata' => $snapshot['metadata'],
                'deleted_by' => $userId,
                'deleted_at' => now(),
            ]);

            // 3. Cascade delete records in proper foreign key order
            $submissionIds = collect($snapshot['related_data']['rba_submissions'] ?? [])->pluck('id')->all();
            $detailIds = collect($snapshot['related_data']['rba_details'] ?? [])->pluck('id')->all();
            $subDocIds = collect($snapshot['related_data']['rba_submission_documents'] ?? [])->pluck('id')->all();
            $deskVerifIds = collect($snapshot['related_data']['rba_desk_verifications'] ?? [])->pluck('id')->all();
            $detailDocIds = collect($snapshot['related_data']['rba_detail_documents'] ?? [])->pluck('id')->all();

            // a. Details attachments & pivot
            if (!empty($detailIds)) {
                DB::table('rba_detail_attachments')->whereIn('rba_detail_id', $detailIds)->delete();
                DB::table('rba_attachments')->whereIn('rba_detail_id', $detailIds)->delete();
            }
            if (!empty($detailDocIds)) {
                DB::table('rba_attachments')->whereIn('rba_detail_document_id', $detailDocIds)->delete();
                DB::table('rba_detail_documents')->whereIn('id', $detailDocIds)->delete();
            }

            // b. Details
            if (!empty($detailIds)) {
                DB::table('rba_details')->whereIn('id', $detailIds)->delete();
            }

            // c. Submissions children
            if (!empty($subDocIds)) {
                DB::table('rba_submission_document_versions')->whereIn('rba_submission_document_id', $subDocIds)->delete();
                DB::table('rba_submission_documents')->whereIn('id', $subDocIds)->delete();
            }

            if (!empty($deskVerifIds)) {
                DB::table('rba_desk_verification_documents')->whereIn('rba_desk_verification_id', $deskVerifIds)->delete();
                DB::table('rba_desk_verifications')->whereIn('id', $deskVerifIds)->delete();
            }

            if (!empty($submissionIds)) {
                DB::table('rba_submission_operator_backgrounds')->whereIn('rba_submission_id', $submissionIds)->delete();
                DB::table('rba_submissions')->whereIn('id', $submissionIds)->delete();
            }

            // d. Header children & Header
            DB::table('rba_account_pagus')->where('rba_header_id', $headerId)->delete();
            DB::table('rba_headers')->where('id', $headerId)->delete();

            // 4. Log to ActivityLog
            if ($userId) {
                ActivityLog::create([
                    'user_id' => $userId,
                    'action' => 'deleted',
                    'model_type' => RbaHeader::class,
                    'model_id' => $headerId,
                    'description' => "Menghapus secara permanen {$label}. Snapshot diamankan ke Arsip Sampah (Archive ID #{$archive->id}). Alasan: {$archive->reason}",
                ]);
            }

            return $archive;
        });
    }

    /**
     * Restore an archived RBA Header and all its relations back to operational tables.
     */
    public function restoreRbaHeader(HardDeleteArchive $archive, ?int $userId = null): RbaHeader
    {
        if ($archive->isRestored()) {
            throw new RuntimeException("Arsip ini sudah pernah dipulihkan pada " . $archive->restored_at->format('d/m/Y H:i'));
        }

        if ($archive->model_type !== RbaHeader::class) {
            throw new RuntimeException("Tipe model arsip [{$archive->model_type}] tidak didukung untuk pemulihan RBA Header.");
        }

        $root = $archive->root_data;
        $rel = $archive->related_data ?? [];

        // 1. Collision Guards
        if (DB::table('rba_headers')->where('id', $archive->model_id)->exists()) {
            throw new RuntimeException("Gagal Restore: ID RBA Header #{$archive->model_id} saat ini sudah digunakan oleh data lain di tabel operasional.");
        }

        if (DB::table('rba_headers')->where('period_id', $root['period_id'])->where('year', $root['year'])->exists()) {
            throw new RuntimeException("Gagal Restore: RBA Header untuk Tahun {$root['year']} dengan Periode ID {$root['period_id']} sudah ada di tabel operasional. Tidak boleh ada 2 RBA aktif pada periode yang sama.");
        }

        return DB::transaction(function () use ($archive, $root, $rel, $userId) {
            // 2. Insert root header
            DB::table('rba_headers')->insert($root);

            // 3. Insert header children in dependency order
            if (!empty($rel['rba_account_pagus'])) {
                DB::table('rba_account_pagus')->insert($rel['rba_account_pagus']);
            }

            if (!empty($rel['rba_submissions'])) {
                DB::table('rba_submissions')->insert($rel['rba_submissions']);
            }

            if (!empty($rel['rba_submission_operator_backgrounds'])) {
                DB::table('rba_submission_operator_backgrounds')->insert($rel['rba_submission_operator_backgrounds']);
            }

            if (!empty($rel['rba_submission_documents'])) {
                DB::table('rba_submission_documents')->insert($rel['rba_submission_documents']);
            }

            if (!empty($rel['rba_submission_document_versions'])) {
                DB::table('rba_submission_document_versions')->insert($rel['rba_submission_document_versions']);
            }

            if (!empty($rel['rba_desk_verifications'])) {
                DB::table('rba_desk_verifications')->insert($rel['rba_desk_verifications']);
            }

            if (!empty($rel['rba_desk_verification_documents'])) {
                DB::table('rba_desk_verification_documents')->insert($rel['rba_desk_verification_documents']);
            }

            if (!empty($rel['rba_detail_documents'])) {
                DB::table('rba_detail_documents')->insert($rel['rba_detail_documents']);
            }

            if (!empty($rel['rba_details'])) {
                DB::table('rba_details')->insert($rel['rba_details']);
            }

            if (!empty($rel['rba_attachments'])) {
                DB::table('rba_attachments')->insert($rel['rba_attachments']);
            }

            if (!empty($rel['rba_detail_attachments'])) {
                DB::table('rba_detail_attachments')->insert($rel['rba_detail_attachments']);
            }

            // 4. Update archive state
            $archive->update([
                'restored_at' => now(),
                'restored_by' => $userId,
            ]);

            // 5. Log Activity
            if ($userId) {
                ActivityLog::create([
                    'user_id' => $userId,
                    'action' => 'restored',
                    'model_type' => RbaHeader::class,
                    'model_id' => $archive->model_id,
                    'description' => "Memulihkan (Restore) 100% data {$archive->label} dari Arsip Sampah (Archive ID #{$archive->id}) kembali ke tabel operasional.",
                ]);
            }

            return RbaHeader::findOrFail($archive->model_id);
        });
    }

    /**
     * Snapshot entire relational tree of an RbaHeader.
     */
    private function captureRbaHeaderTree(RbaHeader $header): array
    {
        $headerId = $header->id;

        // Root
        $rootData = DB::table('rba_headers')->where('id', $headerId)->first();
        $rootDataArray = (array) $rootData;

        // Submissions & Pagus
        $submissions = DB::table('rba_submissions')->where('rba_header_id', $headerId)->get()->map(fn($r) => (array)$r)->all();
        $pagus = DB::table('rba_account_pagus')->where('rba_header_id', $headerId)->get()->map(fn($r) => (array)$r)->all();

        $submissionIds = array_column($submissions, 'id');

        $backgrounds = [];
        $subDocs = [];
        $subDocVersions = [];
        $deskVerifs = [];
        $deskVerifDocs = [];
        $detailDocs = [];
        $details = [];
        $attachments = [];
        $detailAttachments = [];
        $filePaths = [];

        if (!empty($submissionIds)) {
            $backgrounds = DB::table('rba_submission_operator_backgrounds')->whereIn('rba_submission_id', $submissionIds)->get()->map(fn($r) => (array)$r)->all();

            $subDocs = DB::table('rba_submission_documents')->whereIn('rba_submission_id', $submissionIds)->get()->map(fn($r) => (array)$r)->all();
            $subDocIds = array_column($subDocs, 'id');
            if (!empty($subDocIds)) {
                $subDocVersions = DB::table('rba_submission_document_versions')->whereIn('rba_submission_document_id', $subDocIds)->get()->map(fn($r) => (array)$r)->all();
                foreach ($subDocVersions as $ver) {
                    if (!empty($ver['file_path'])) $filePaths[] = $ver['file_path'];
                }
            }

            $deskVerifs = DB::table('rba_desk_verifications')->whereIn('rba_submission_id', $submissionIds)->get()->map(fn($r) => (array)$r)->all();
            $deskVerifIds = array_column($deskVerifs, 'id');
            if (!empty($deskVerifIds)) {
                $deskVerifDocs = DB::table('rba_desk_verification_documents')->whereIn('rba_desk_verification_id', $deskVerifIds)->get()->map(fn($r) => (array)$r)->all();
                foreach ($deskVerifDocs as $dDoc) {
                    if (!empty($dDoc['file_path'])) $filePaths[] = $dDoc['file_path'];
                }
            }

            $detailDocs = DB::table('rba_detail_documents')->whereIn('rba_submission_id', $submissionIds)->get()->map(fn($r) => (array)$r)->all();
            $detailDocIds = array_column($detailDocs, 'id');

            $details = DB::table('rba_details')->whereIn('rba_submission_id', $submissionIds)->get()->map(fn($r) => (array)$r)->all();
            $detailIds = array_column($details, 'id');

            if (!empty($detailIds) || !empty($detailDocIds)) {
                $attQuery = DB::table('rba_attachments');
                if (!empty($detailIds) && !empty($detailDocIds)) {
                    $attQuery->where(function ($q) use ($detailIds, $detailDocIds) {
                        $q->whereIn('rba_detail_id', $detailIds)
                          ->orWhereIn('rba_detail_document_id', $detailDocIds);
                    });
                } elseif (!empty($detailIds)) {
                    $attQuery->whereIn('rba_detail_id', $detailIds);
                } else {
                    $attQuery->whereIn('rba_detail_document_id', $detailDocIds);
                }

                $attachments = $attQuery->get()->map(fn($r) => (array)$r)->all();
                foreach ($attachments as $att) {
                    if (!empty($att['file_path'])) $filePaths[] = $att['file_path'];
                }

                if (!empty($detailIds)) {
                    $detailAttachments = DB::table('rba_detail_attachments')->whereIn('rba_detail_id', $detailIds)->get()->map(fn($r) => (array)$r)->all();
                }
            }
        }

        return [
            'root_data' => $rootDataArray,
            'related_data' => [
                'rba_submissions' => $submissions,
                'rba_account_pagus' => $pagus,
                'rba_submission_operator_backgrounds' => $backgrounds,
                'rba_submission_documents' => $subDocs,
                'rba_submission_document_versions' => $subDocVersions,
                'rba_desk_verifications' => $deskVerifs,
                'rba_desk_verification_documents' => $deskVerifDocs,
                'rba_detail_documents' => $detailDocs,
                'rba_details' => $details,
                'rba_attachments' => $attachments,
                'rba_detail_attachments' => $detailAttachments,
            ],
            'metadata' => [
                'submissions_count' => count($submissions),
                'details_count' => count($details),
                'attachments_count' => count($attachments),
                'file_paths' => array_values(array_unique($filePaths)),
            ],
        ];
    }
}

