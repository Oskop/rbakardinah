<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaDetail;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\RbaAttachment;
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

        $accountCodes = AccountCode::all();
        return view('operator.details.create', compact('submission', 'accountCodes'));
    }

    public function edit(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if ($detail->submission->status_submission !== 'Draft') {
            return back()->with('error', 'Cannot edit items in a non-draft submission.');
        }

        $accountCodes = AccountCode::all();
        return view('operator.details.edit', compact('detail', 'accountCodes'));
    }

    public function update(Request $request, RbaDetail $detail)
    {
        $validated = $request->validate([
            'account_code_id' => 'required|exists:account_codes,id',
            'description' => 'required|string',
            'nominal_request' => 'required|numeric|min:0',
        ]);

        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('update', $detail);

        $detail->update($validated);

        return redirect()->route('operator.submissions.show', $detail->rba_submission_id)
            ->with('success', 'RBA Detail updated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rba_submission_id' => 'required|exists:rba_submissions,id',
            'account_code_id' => 'required|exists:account_codes,id',
            'description' => 'required|string',
            'nominal_request' => 'required|numeric|min:0',
            'attachment' => 'required|file|mimes:pdf|max:10240', // 10MB
        ]);

        $submission = RbaSubmission::findOrFail($validated['rba_submission_id']);

        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('create', [RbaDetail::class, $submission, $validated['account_code_id']]);

        \DB::transaction(function () use ($validated, $submission, $request) {
            $detail = RbaDetail::create([
                'rba_submission_id' => $validated['rba_submission_id'],
                'account_code_id' => $validated['account_code_id'],
                'description' => $validated['description'],
                'nominal_request' => $validated['nominal_request'],
                'created_by' => Auth::id(),
            ]);

            $path = $request->file('attachment')->store('attachments', 'public');

            RbaAttachment::create([
                'rba_detail_id' => $detail->id,
                'file_path' => $path,
                'version_number' => 1,
                'uploaded_by' => Auth::id(),
            ]);
        });

        return redirect()->route('operator.submissions.show', $submission->id)
            ->with('success', 'RBA Detail added successfully.');
    }

    public function uploadVersion(Request $request, RbaDetail $detail)
    {
        $request->validate([
            'attachment' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        // Versioning logic
        $latestVersion = $detail->attachments()->max('version_number') ?? 0;
        $newVersion = $latestVersion + 1;

        $path = $request->file('attachment')->store('attachments', 'public');

        RbaAttachment::create([
            'rba_detail_id' => $detail->id,
            'file_path' => $path,
            'version_number' => $newVersion,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', "New version (V{$newVersion}) uploaded successfully.");
    }
}
