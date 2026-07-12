<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaSubmission;
use App\Models\RbaSubmissionDocument;
use App\Models\RbaSubmissionDocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function uploadDocument(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $isLocked = $submission->header->status_global === 'Locked';
        if (!$isLocked) {
            return back()->with('error', 'Dokumen KAK, RAK, dan RTP hanya dapat diunggah setelah RBA dikunci oleh Administrator.');
        }

        $request->validate([
            'type' => 'required|in:KAK,RAK,RTP',
            'attachment' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        DB::transaction(function () use ($request, $submission) {
            $document = RbaSubmissionDocument::firstOrCreate([
                'rba_submission_id' => $submission->id,
                'type' => $request->type,
            ]);

            // Versioning
            $latestVersion = $document->versions()->max('version_number') ?? 0;
            $newVersion = $latestVersion + 1;

            $path = $request->file('attachment')->store('documents', 'public');

            RbaSubmissionDocumentVersion::create([
                'rba_submission_document_id' => $document->id,
                'file_path' => $path,
                'version_number' => $newVersion,
                'uploaded_by' => Auth::id(),
            ]);
        });

        return back()->with('success', "Dokumen {$request->type} versi baru berhasil diunggah.");
    }

    public function history(RbaSubmission $submission, string $type)
    {
        if ($submission->unit_id !== Auth::user()->unit_id && Auth::user()->role !== 'Supervisor' && Auth::user()->role !== 'Administrator') {
            abort(403);
        }

        if (!in_array($type, ['KAK', 'RAK', 'RTP'])) {
            abort(404);
        }

        $document = RbaSubmissionDocument::with(['versions.uploader'])
            ->where('rba_submission_id', $submission->id)
            ->where('type', $type)
            ->first();

        $versions = $document ? $document->versions : collect();

        return view('operator.documents.history', compact('submission', 'type', 'versions'));
    }
}
