<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\RbaSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index()
    {
        $unitId = Auth::user()->unit_id;
        $submissions = RbaSubmission::with(['header.period', 'unit'])
            ->where('unit_id', $unitId)
            ->latest()
            ->get();

        return view('supervisor.submissions.index', compact('submissions'));
    }

    public function show(RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $submission->load(['details.accountCode', 'details.attachments', 'header.period', 'documents.versions', 'documents.latestVersion']);

        // Load pagu for indicators
        $pagus = \App\Models\RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)->get()->keyBy('account_code_id');
        $headerTotals = \App\Models\RbaDetail::whereHas('submission', function ($q) use ($submission) {
            $q->where('rba_header_id', $submission->rba_header_id);
        })
            ->selectRaw('account_code_id, SUM(nominal_request) as total')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        return view('supervisor.submissions.show', compact('submission', 'pagus', 'headerTotals'));
    }

    public function validate(RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if ($submission->status_submission !== 'Pending Supervisor') {
            return back()->with('error', 'Only Pending submissions can be validated.');
        }

        $submission->update(['status_submission' => 'Validated']);

        return redirect()->route('supervisor.submissions.index')->with('success', 'Submission validated successfully.');
    }

    public function toggleDetailValidation(Request $request, \App\Models\RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        // If supervisor is validating the item
        if (!$detail->is_validated) {
            if ($detail->isExceedingPagu() && !$detail->hasUploadedRevision()) {
                return back()->with('error', 'Rincian belanja ini melebihi pagu dan belum memiliki dokumen PDF revisi terbaru dari operator.');
            }
        }

        $detail->update([
            'is_validated' => !$detail->is_validated,
            'validated_at' => !$detail->is_validated ? now() : null,
            'validated_by' => !$detail->is_validated ? Auth::id() : null,
            'is_rejected' => false, // Clear rejection if validating
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $status = $detail->is_validated ? 'divalidasi' : 'dibatalkan validasinya';
        return back()->with('success', "Rincian berhasil $status.");
    }

    public function rejectDetail(Request $request, \App\Models\RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $detail->update([
            'is_validated' => false,
            'validated_at' => null,
            'validated_by' => null,
            'is_rejected' => true,
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', "Rincian telah ditolak.");
    }
}
