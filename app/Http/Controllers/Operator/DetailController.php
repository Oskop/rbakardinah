<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaDetail;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\RbaAttachment;
use App\Models\RbaDetailDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class DetailController extends Controller
{
    public function create(Request $request)
    {
        $submissionId = $request->query('submission_id');
        $submission = RbaSubmission::findOrFail($submissionId);

        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if (!Auth::user()->isProposer()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengusulkan rincian belanja (Mode Peninjau / Viewer).');
        }

        $hasBackground = $submission->operatorBackgrounds()->where('user_id', Auth::id())->exists() || !empty($submission->background);
        if (!$hasBackground) {
            return redirect()->route('operator.submissions.show', $submission->id)
                ->with('error', 'Sebelum menginput rincian belanja, Anda wajib mengisi data latar belakang terlebih dahulu.');
        }

        // Only show account codes that are active and NOT locked by pagu
        $lockedAccountIds = \App\Models\RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)
            ->pluck('account_code_id');

        $accountCodes = AccountCode::with('kelompokBelanja')
            ->where('is_active', true)
            ->whereNotIn('id', $lockedAccountIds)
            ->orderBy('code')
            ->get();

        $existingDocuments = RbaDetailDocument::where('rba_submission_id', $submission->id)
            ->with(['latestVersion.details.accountCode'])
            ->orderByDesc('id')
            ->get();

        return view('operator.details.create', compact('submission', 'accountCodes', 'existingDocuments'));
    }

    public function edit(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('update', $detail);

        $lockedAccountIds = \App\Models\RbaAccountPagu::where('rba_header_id', $detail->submission->rba_header_id)
            ->pluck('account_code_id');

        $accountCodes = AccountCode::with('kelompokBelanja')
            ->where(function ($q) use ($detail) {
                $q->where('is_active', true)->orWhere('id', $detail->account_code_id);
            })
            ->whereNotIn('id', $lockedAccountIds)
            ->orderBy('code')
            ->get();

        $existingDocuments = RbaDetailDocument::where('rba_submission_id', $detail->rba_submission_id)
            ->with(['latestVersion.details.accountCode'])
            ->orderByDesc('id')
            ->get();

        return view('operator.details.edit', compact('detail', 'accountCodes', 'existingDocuments'));
    }

    public function update(Request $request, RbaDetail $detail)
    {
        $validated = $request->validate([
            'account_code_id' => 'required|exists:account_codes,id',
            'description' => 'required|string',
            'volume' => 'required|numeric|min:0.01',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'rba_detail_document_id' => 'nullable|exists:rba_detail_documents,id',
        ]);

        $validated['nominal_request'] = $validated['volume'] * $validated['harga_satuan'];

        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('update', $detail);

        // Reset status to Draft so the supervisor must re-validate the post-edit usulan
        $validated['is_validated'] = false;
        $validated['validated_at'] = null;
        $validated['validated_by'] = null;
        $validated['is_submitted'] = false;
        $validated['is_rejected'] = false;
        $validated['rejected_at'] = null;
        $validated['rejected_by'] = null;
        $validated['rejection_reason'] = null;

        $docId = $validated['rba_detail_document_id'] ?? null;
        unset($validated['rba_detail_document_id']);

        $detail->update($validated);

        // Jika operator memilih beralih ke dokumen yang sudah ada
        if ($docId) {
            $doc = RbaDetailDocument::where('rba_submission_id', $detail->rba_submission_id)->find($docId);
            if ($doc && $doc->latestVersion) {
                $detail->attachments()->syncWithoutDetaching([$doc->latestVersion->id]);
            }
        }

        return redirect()->route('operator.submissions.show', $detail->rba_submission_id)
            ->with('success', 'RBA Detail berhasil diperbarui dan status kembali menjadi Draft (perlu diajukan dan divalidasi ulang oleh Supervisor).');
    }

    public function store(Request $request)
    {
        $rules = [
            'rba_submission_id' => 'required|exists:rba_submissions,id',
            'account_code_id' => 'required|exists:account_codes,id',
            'description' => 'required|string',
            'volume' => 'required|numeric|min:0.01',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'document_source' => 'nullable|in:new,existing',
            'rba_detail_document_id' => 'nullable|exists:rba_detail_documents,id',
            'document_name' => 'nullable|string|max:255',
        ];

        // Jika mode pemilihan dokumen yang sudah ada
        if ($request->input('document_source') === 'existing') {
            $rules['rba_detail_document_id'] = 'required|exists:rba_detail_documents,id';
        } else {
            // Unggah file baru wajib jika tidak memilih dokumen eksisting
            $rules['attachment'] = 'required|file|mimes:pdf|max:10240';
        }

        $validated = $request->validate($rules);
        $validated['nominal_request'] = $validated['volume'] * $validated['harga_satuan'];

        $submission = RbaSubmission::findOrFail($validated['rba_submission_id']);

        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $hasBackground = $submission->operatorBackgrounds()->where('user_id', Auth::id())->exists() || !empty($submission->background);
        if (!$hasBackground) {
            return redirect()->route('operator.submissions.show', $submission->id)
                ->with('error', 'Sebelum menginput rincian belanja, Anda wajib mengisi data latar belakang terlebih dahulu.');
        }

        Gate::authorize('create', [RbaDetail::class, $submission, $validated['account_code_id']]);

        \DB::transaction(function () use ($validated, $submission, $request) {
            $detail = RbaDetail::create([
                'rba_submission_id' => $validated['rba_submission_id'],
                'account_code_id' => $validated['account_code_id'],
                'description' => $validated['description'],
                'volume' => $validated['volume'],
                'satuan' => $validated['satuan'],
                'harga_satuan' => $validated['harga_satuan'],
                'nominal_request' => $validated['nominal_request'],
                'created_by' => Auth::id(),
            ]);

            if ($request->input('document_source') === 'existing' && $request->filled('rba_detail_document_id')) {
                $doc = RbaDetailDocument::where('rba_submission_id', $submission->id)
                    ->findOrFail($request->rba_detail_document_id);
                
                $latestAttachment = $doc->latestVersion;
                if (!$latestAttachment) {
                    throw new \Exception('Dokumen terpilih tidak memiliki berkas lampiran yang valid.');
                }

                $detail->attachments()->syncWithoutDetaching([$latestAttachment->id]);
            } else {
                $file = $request->file('attachment');
                $docName = $request->filled('document_name')
                    ? $request->document_name
                    : ($file ? $file->getClientOriginalName() : 'Dokumen Usulan Belanja');

                $doc = RbaDetailDocument::create([
                    'rba_submission_id' => $submission->id,
                    'document_name' => $docName,
                    'created_by' => Auth::id(),
                ]);

                $path = $file->store('attachments', 'public');

                $attachment = RbaAttachment::create([
                    'rba_detail_document_id' => $doc->id,
                    'rba_detail_id' => $detail->id,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'version_number' => 1,
                    'uploaded_by' => Auth::id(),
                ]);

                $detail->attachments()->syncWithoutDetaching([$attachment->id]);
            }
        });

        return redirect()->route('operator.submissions.show', $submission->id)
            ->with('success', 'RBA Detail added successfully.');
    }

    public function uploadVersion(Request $request, RbaDetail $detail)
    {
        $request->validate([
            'attachment' => 'required|file|mimes:pdf|max:10240',
            'upload_mode' => 'nullable|in:standalone,shared_update',
            'document_name' => 'nullable|string|max:255',
            'target_detail_ids' => 'nullable|array',
            'target_detail_ids.*' => 'integer|exists:rba_details,id',
        ]);

        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('uploadVersion', $detail);

        $uploadMode = $request->input('upload_mode', 'shared_update');
        $file = $request->file('attachment');
        $path = $file->store('attachments', 'public');
        $originalFilename = $file->getClientOriginalName();
        $newVersion = 1;
        $affectedCount = 1;

        \DB::transaction(function () use ($request, $detail, $uploadMode, $path, $originalFilename, &$newVersion, &$affectedCount) {
            $currentDoc = $detail->document();

            if ($uploadMode === 'standalone' || !$currentDoc) {
                // Mode Standalone: Pisahkan usulan ini menjadi dokumen mandiri baru
                $docName = $request->filled('document_name')
                    ? $request->document_name
                    : ($currentDoc ? $currentDoc->document_name . ' (Revisi Mandiri)' : $originalFilename);

                $newDoc = RbaDetailDocument::create([
                    'rba_submission_id' => $detail->rba_submission_id,
                    'document_name' => $docName,
                    'created_by' => Auth::id(),
                ]);

                $newVersion = ($detail->attachments()->max('rba_attachments.version_number') ?? 0) + 1;

                $attachment = RbaAttachment::create([
                    'rba_detail_document_id' => $newDoc->id,
                    'rba_detail_id' => $detail->id,
                    'file_path' => $path,
                    'original_filename' => $originalFilename,
                    'version_number' => $newVersion,
                    'uploaded_by' => Auth::id(),
                ]);

                $detail->attachments()->syncWithoutDetaching([$attachment->id]);

                $targetDetails = collect([$detail]);
            } else {
                // Mode Shared Update: Perbarui dokumen bersama ke versi berikutnya
                $newVersion = ($currentDoc->versions()->max('version_number') ?? 0) + 1;

                $attachment = RbaAttachment::create([
                    'rba_detail_document_id' => $currentDoc->id,
                    'rba_detail_id' => $detail->id,
                    'file_path' => $path,
                    'original_filename' => $originalFilename,
                    'version_number' => $newVersion,
                    'uploaded_by' => Auth::id(),
                ]);

                // Ambil daftar target usulan yang dicentang ikut menggunakan revisi ini
                $targetIds = $request->input('target_detail_ids', [$detail->id]);
                if (!in_array($detail->id, $targetIds)) {
                    $targetIds[] = $detail->id;
                }

                $targetDetails = RbaDetail::where('rba_submission_id', $detail->rba_submission_id)
                    ->whereIn('id', $targetIds)
                    ->get();

                foreach ($targetDetails as $td) {
                    $td->attachments()->syncWithoutDetaching([$attachment->id]);
                }
            }

            // Atur ulang status usulan terkait ke Draft agar disinkronkan ke Supervisor
            foreach ($targetDetails as $td) {
                $td->update([
                    'is_validated' => false,
                    'validated_at' => null,
                    'validated_by' => null,
                    'is_submitted' => false,
                    'is_rejected' => false,
                    'rejected_at' => null,
                    'rejected_by' => null,
                    'rejection_reason' => null,
                ]);
            }

            $affectedCount = $targetDetails->count();
        });

        $detail->submission->syncValidationStatus();

        $msg = $affectedCount > 1
            ? "Versi PDF baru (V{$newVersion}) berhasil diunggah untuk {$affectedCount} rincian belanja. Status kembali menjadi Draft."
            : "Versi PDF baru (V{$newVersion}) berhasil diunggah. Status usulan kembali menjadi Draft (silakan klik Ajukan ke Supervisor).";

        return back()->with('success', $msg);
    }

    public function submitItem(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        // Exception: If pagu is set, they can submit if it exceeds pagu AND they have uploaded the revision.
        if ($detail->isExceedingPagu() && !$detail->hasUploadedRevision()) {
            return back()->with('error', 'Anda wajib mengunggah PDF rincian belanja baru menyesuaikan pagu yang ditetapkan oleh admin sebelum mengajukan.');
        }

        Gate::authorize('submit', $detail);

        $detail->update([
            'is_submitted' => true,
            'is_rejected' => false,
            'rejection_reason' => null,
            'is_validated' => false,
            'validated_at' => null,
            'validated_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        // Automatically sync unit submission macro status
        $detail->submission->syncValidationStatus();

        return back()->with('success', 'Rincian berhasil diajukan ke Supervisor.');
    }

    public function destroy(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('delete', $detail);

        $submission = $detail->submission;
        $detail->delete();
        $submission->syncValidationStatus();

        return back()->with('success', 'Rincian berhasil dihapus.');
    }
}
