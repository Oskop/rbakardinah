<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function index()
    {
        $unitId = Auth::user()->unit_id;
        $submissions = RbaSubmission::with(['header.period', 'unit'])
            ->where('unit_id', $unitId)
            ->latest()
            ->get();

        return view('operator.submissions.index', compact('submissions'));
    }

    public function show(RbaSubmission $submission)
    {
        // Ensure operator can only see their unit's submission
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $submission->load(['details' => function ($query) {
            $query->where('created_by', Auth::id());
        }, 'details.accountCode', 'details.attachments', 'header.period', 'documents.versions', 'documents.latestVersion']);

        // Load pagu for this header
        $pagus = \App\Models\RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)->get()->keyBy('account_code_id');

        // Calculate totals per account code for this header (for visual indicator)
        $headerTotals = \App\Models\RbaDetail::whereHas('submission', function ($q) use ($submission) {
            $q->where('rba_header_id', $submission->rba_header_id);
        })
            ->selectRaw('account_code_id, SUM(nominal_request) as total')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        return view('operator.submissions.show', compact('submission', 'pagus', 'headerTotals'));
    }

    public function submit(RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if ($submission->status_submission !== 'Draft') {
            return back()->with('error', 'Only Draft submissions can be submitted.');
        }

        $submission->update(['status_submission' => 'Pending Supervisor']);

        return redirect()->route('operator.submissions.index')->with('success', 'Submission sent to Supervisor.');
    }

    public function updateBackground(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $request->validate([
            'background' => 'required|string',
        ]);

        $submission->update([
            'background' => $request->background,
        ]);

        return back()->with('success', 'Latar belakang RBA berhasil diperbarui.');
    }
}
