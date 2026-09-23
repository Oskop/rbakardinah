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

        $isProposer = Auth::user()->isProposer();
        $userSubUnitId = Auth::user()->sub_unit_id;

        $submission->load([
            'details' => function ($query) use ($isProposer, $userSubUnitId) {
                if ($isProposer) {
                    $query->where('created_by', Auth::id());
                } else {
                    if ($userSubUnitId) {
                        $query->whereHas('creator', function ($q) use ($userSubUnitId) {
                            $q->where('sub_unit_id', $userSubUnitId);
                        });
                    }
                }
            },
            'details.accountCode',
            'details.attachments',
            'details.creator',
            'header.period',
            'documents' => function ($query) use ($isProposer, $userSubUnitId) {
                if ($isProposer) {
                    $query->where('user_id', Auth::id());
                } else {
                    if ($userSubUnitId) {
                        $query->whereHas('user', function ($q) use ($userSubUnitId) {
                            $q->where('sub_unit_id', $userSubUnitId);
                        });
                    }
                }
            },
            'documents.versions',
            'documents.latestVersion'
        ]);

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

        if ($isProposer) {
            $myBgRecord = $submission->operatorBackgrounds()->where('user_id', Auth::id())->first();
            $myBackground = $myBgRecord ? $myBgRecord->background : ($submission->operatorBackgrounds()->count() === 0 ? $submission->background : '');
            $otherOperatorBackgrounds = $submission->operatorBackgrounds()
                ->with('user')
                ->where('user_id', '!=', Auth::id())
                ->whereHas('user', function ($q) {
                    $q->where('is_active', true);
                })
                ->get();
        } else {
            $myBackground = $submission->background;
            $otherOperatorBackgrounds = $submission->operatorBackgrounds()
                ->with('user')
                ->whereHas('user', function ($q) {
                    $q->where('is_active', true);
                })
                ->get();
        }

        $myDeskVerification = $submission->deskVerifications()->where('user_id', Auth::id())->with('latestDocument')->first();
        $otherDeskVerifications = $submission->deskVerifications()
            ->with(['user.subUnit', 'latestDocument'])
            ->where('user_id', '!=', Auth::id())
            ->get();

        $existingDocuments = \App\Models\RbaDetailDocument::where('rba_submission_id', $submission->id)
            ->with(['latestVersion.details.accountCode'])
            ->orderByDesc('id')
            ->get();

        return view('operator.submissions.show', compact('submission', 'pagus', 'headerTotals', 'previousPagus', 'myBackground', 'otherOperatorBackgrounds', 'myDeskVerification', 'otherDeskVerifications', 'existingDocuments'));
    }

    public function submit(RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if (!Auth::user()->isProposer()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengajukan usulan ke Supervisor (Mode Peninjau / Viewer).');
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

        if (!Auth::user()->isProposer()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah latar belakang (Mode Peninjau / Viewer).');
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
        $isProposer = Auth::user()->isProposer();
        $userSubUnitId = Auth::user()->sub_unit_id;

        $submission->load(['details' => function ($query) use ($isProposer, $userSubUnitId) {
            if ($isProposer) {
                $query->where('created_by', Auth::id());
            } else {
                if ($userSubUnitId) {
                    $query->whereHas('creator', function ($q) use ($userSubUnitId) {
                        $q->where('sub_unit_id', $userSubUnitId);
                    });
                }
            }
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
        $isProposer = Auth::user()->isProposer();
        $userSubUnitId = Auth::user()->sub_unit_id;

        $submission->load(['details' => function ($query) use ($isProposer, $userSubUnitId) {
            if ($isProposer) {
                $query->where('created_by', Auth::id());
            } else {
                if ($userSubUnitId) {
                    $query->whereHas('creator', function ($q) use ($userSubUnitId) {
                        $q->where('sub_unit_id', $userSubUnitId);
                    });
                }
            }
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
