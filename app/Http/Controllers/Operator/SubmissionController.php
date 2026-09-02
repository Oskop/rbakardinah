<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\RbaAccountPagu;
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
        }, 'details.accountCode', 'details.attachments', 'header.period', 'documents' => function ($query) {
            $query->where('user_id', Auth::id());
        }, 'documents.versions', 'documents.latestVersion']);

        // Load pagu for this header
        $pagus = RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)->get()->keyBy('account_code_id');

        // Determine previous RBA header to fetch nominal pagu AWAL
        $currentHeader = $submission->header;
        $currentYear = $currentHeader->year;
        $currentPeriodName = $currentHeader->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            // If current is Perubahan -> Previous is Murni for the SAME year
            $previousHeader = RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            // If current is Murni -> Previous is Perubahan for the PREVIOUS year (year - 1)
            $previousHeader = RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        // Fallback: Closest preceding RBA header by ID/Year
        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        // Calculate totals per account code for this header (for visual indicator)
        $headerTotals = \App\Models\RbaDetail::whereHas('submission', function ($q) use ($submission) {
            $q->where('rba_header_id', $submission->rba_header_id);
        })
            ->selectRaw('account_code_id, SUM(nominal_request) as total')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        $myBgRecord = $submission->operatorBackgrounds()->where('user_id', Auth::id())->first();
        $myBackground = $myBgRecord ? $myBgRecord->background : ($submission->operatorBackgrounds()->count() === 0 ? $submission->background : '');
        $otherOperatorBackgrounds = $submission->operatorBackgrounds()
            ->with('user')
            ->where('user_id', '!=', Auth::id())
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        return view('operator.submissions.show', compact('submission', 'pagus', 'headerTotals', 'previousPagus', 'myBackground', 'otherOperatorBackgrounds'));
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

        \App\Models\RbaSubmissionOperatorBackground::updateOrCreate(
            ['rba_submission_id' => $submission->id, 'user_id' => Auth::id()],
            ['background' => $request->background]
        );

        // Compile active operator backgrounds into submission->background for backward compatibility & print preview
        $allOpBgs = $submission->operatorBackgrounds()
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->with('user')
            ->get();

        if ($allOpBgs->count() > 1) {
            $compiled = $allOpBgs->map(function ($ob) {
                return $ob->user->name . ":\n" . $ob->background;
            })->join("\n\n");
        } else {
            $compiled = $allOpBgs->first()?->background ?? $request->background;
        }

        $submission->update([
            'background' => $compiled ?: $request->background,
        ]);

        return back()->with('success', 'Latar belakang RBA berhasil diperbarui.');
    }

    public function printPreview(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $includeBackground = $request->get('include_background', '1') == '1';

        $submission->load(['details' => function ($query) {
            $query->where('created_by', Auth::id());
        }, 'details.accountCode', 'header.period', 'unit']);

        $currentHeader = $submission->header;
        $currentYear = $currentHeader->year;
        $currentPeriodName = $currentHeader->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            $previousHeader = RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        return view('reports.operator_rba_print', compact('submission', 'includeBackground', 'previousPagus'));
    }

    public function printPreviewFinal(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $includeBackground = $request->get('include_background', '1') == '1';

        $submission->load(['details' => function ($query) {
            $query->where('created_by', Auth::id());
        }, 'details.accountCode', 'header.period', 'unit']);

        $pagus = RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)->get()->keyBy('account_code_id');

        $currentHeader = $submission->header;
        $currentYear = $currentHeader->year;
        $currentPeriodName = $currentHeader->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            $previousHeader = RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        return view('reports.operator_rba_final_print', compact('submission', 'includeBackground', 'pagus', 'previousPagus'));
    }

    public function exportPdf(Request $request, RbaSubmission $submission)
    {
        return $this->printPreview($request, $submission);
    }
}
