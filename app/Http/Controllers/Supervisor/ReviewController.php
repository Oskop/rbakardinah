<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\RbaAccountPagu;
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

        $operators = \App\Models\User::where('unit_id', Auth::user()->unit_id)
            ->where('role', 'Operator')
            ->orderBy('name')
            ->get();

        $submission->load([
            'details.accountCode', 
            'details.attachments', 
            'header.period', 
            'documents' => function ($query) {
                $query->with(['user', 'versions.uploader', 'latestVersion']);
            }
        ]);

        $documents = $submission->documents->groupBy('user_id');

        // Load pagu for indicators
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

        $headerTotals = \App\Models\RbaDetail::whereHas('submission', function ($q) use ($submission) {
            $q->where('rba_header_id', $submission->rba_header_id);
        })
            ->selectRaw('account_code_id, SUM(nominal_request) as total')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        return view('supervisor.submissions.show', compact('submission', 'pagus', 'headerTotals', 'operators', 'documents', 'previousPagus'));
    }

    public function printPreview(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $operatorIdsRaw = $request->get('operator_ids', []);
        
        $selectedOperatorIds = [];
        if (is_array($operatorIdsRaw)) {
            $selectedOperatorIds = array_filter(array_map('intval', $operatorIdsRaw));
        } elseif (is_string($operatorIdsRaw) && trim($operatorIdsRaw) !== '') {
            $selectedOperatorIds = array_filter(array_map('intval', explode(',', $operatorIdsRaw)));
        }

        $allOperators = \App\Models\User::where('unit_id', Auth::user()->unit_id)
            ->where('role', 'Operator')
            ->orderBy('name')
            ->get();

        $includeBackgroundOption = $request->get('include_background', '1') == '1';

        $submission->load([
            'details' => function ($query) use ($selectedOperatorIds) {
                $query->with(['accountCode', 'creator']);
                if (!empty($selectedOperatorIds)) {
                    $query->whereIn('created_by', $selectedOperatorIds);
                }
            },
            'header.period',
            'unit'
        ]);

        $filteredBackground = $this->getFilteredBackground($submission, $selectedOperatorIds, $allOperators, $includeBackgroundOption);
        $includeBackground = !empty($filteredBackground);

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

        if (empty($selectedOperatorIds) || count($selectedOperatorIds) === $allOperators->count()) {
            $operatorFilterLabel = 'Semua Operator (' . $allOperators->count() . ' Operator)';
        } else {
            $filteredOperatorNames = $allOperators->whereIn('id', $selectedOperatorIds)->pluck('name')->toArray();
            $operatorFilterLabel = implode(', ', $filteredOperatorNames);
        }

        return view('reports.supervisor_rba_print', compact('submission', 'filteredBackground', 'includeBackground', 'previousPagus', 'allOperators', 'selectedOperatorIds', 'operatorFilterLabel'));
    }

    public function printPreviewFinal(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $operatorIdsRaw = $request->get('operator_ids', []);
        
        $selectedOperatorIds = [];
        if (is_array($operatorIdsRaw)) {
            $selectedOperatorIds = array_filter(array_map('intval', $operatorIdsRaw));
        } elseif (is_string($operatorIdsRaw) && trim($operatorIdsRaw) !== '') {
            $selectedOperatorIds = array_filter(array_map('intval', explode(',', $operatorIdsRaw)));
        }

        $allOperators = \App\Models\User::where('unit_id', Auth::user()->unit_id)
            ->where('role', 'Operator')
            ->orderBy('name')
            ->get();

        $includeBackgroundOption = $request->get('include_background', '1') == '1';

        $submission->load([
            'details' => function ($query) use ($selectedOperatorIds) {
                $query->with(['accountCode', 'creator']);
                if (!empty($selectedOperatorIds)) {
                    $query->whereIn('created_by', $selectedOperatorIds);
                }
            },
            'header.period',
            'unit'
        ]);

        $filteredBackground = $this->getFilteredBackground($submission, $selectedOperatorIds, $allOperators, $includeBackgroundOption);
        $includeBackground = !empty($filteredBackground);

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

        if (empty($selectedOperatorIds) || count($selectedOperatorIds) === $allOperators->count()) {
            $operatorFilterLabel = 'Semua Operator (' . $allOperators->count() . ' Operator)';
        } else {
            $filteredOperatorNames = $allOperators->whereIn('id', $selectedOperatorIds)->pluck('name')->toArray();
            $operatorFilterLabel = implode(', ', $filteredOperatorNames);
        }

        return view('reports.supervisor_rba_final_print', compact('submission', 'pagus', 'filteredBackground', 'includeBackground', 'previousPagus', 'allOperators', 'selectedOperatorIds', 'operatorFilterLabel'));
    }

    private function getFilteredBackground(RbaSubmission $submission, array $selectedOperatorIds, $allOperators, bool $includeBackgroundRequested): ?string
    {
        if (!$includeBackgroundRequested || empty($submission->background)) {
            return null;
        }

        $isAllOperators = empty($selectedOperatorIds) || count($selectedOperatorIds) === $allOperators->count();
        if ($isAllOperators) {
            return $submission->background;
        }

        $unselectedOperatorNames = $allOperators->whereNotIn('id', $selectedOperatorIds)->pluck('name')->toArray();

        $hasDetailsForSelected = $submission->details->whereIn('created_by', $selectedOperatorIds)->count() > 0;
        if (!$hasDetailsForSelected) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $submission->background);
        $keptLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            $containsUnselected = false;
            foreach ($unselectedOperatorNames as $unselectedName) {
                if (stripos($trimmedLine, $unselectedName) !== false) {
                    $containsUnselected = true;
                    break;
                }
            }

            if (!$containsUnselected) {
                $keptLines[] = $line;
            }
        }

        if (!empty($keptLines)) {
            return implode("\n", $keptLines);
        }

        return $submission->background;
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
