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

        // Only show account codes that are NOT locked by pagu
        $lockedAccountIds = \App\Models\RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)
            ->where('nominal_pagu', '>', 0)
            ->pluck('account_code_id');

        $accountCodes = AccountCode::whereNotIn('id', $lockedAccountIds)->get();
        return view('operator.details.create', compact('submission', 'accountCodes'));
    }

    public function edit(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('update', $detail);

        $lockedAccountIds = \App\Models\RbaAccountPagu::where('rba_header_id', $detail->submission->rba_header_id)
            ->where('nominal_pagu', '>', 0)
            ->pluck('account_code_id');

        $accountCodes = AccountCode::whereNotIn('id', $lockedAccountIds)->get();
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

        Gate::authorize('update', $detail);

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

    public function submitItem(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('update', $detail);

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

        // Update submission status if it was Draft
        if ($detail->submission->status_submission === 'Draft') {
            $detail->submission->update(['status_submission' => 'Pending Supervisor']);
        }

        return back()->with('success', 'Rincian berhasil diajukan ke Supervisor.');
    }

    public function destroy(RbaDetail $detail)
    {
        if ($detail->submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        Gate::authorize('delete', $detail);

        $detail->delete();

        return back()->with('success', 'Rincian berhasil dihapus.');
    }
}
