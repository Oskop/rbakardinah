<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RbaHeaderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $headers = \App\Models\RbaHeader::with(['period', 'admin'])->latest()->get();
        return view('admin.headers.index', compact('headers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $periods = \App\Models\RbaPeriod::where('is_active', true)->orderBy('name')->get();
        return view('admin.headers.create', compact('periods'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:rba_periods,id',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        // Check if header already exists for this period and year
        $exists = \App\Models\RbaHeader::where('period_id', $validated['period_id'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['period_id' => 'RBA Header for this period and year already exists.'])->withInput();
        }

        \DB::transaction(function () use ($validated) {
            $header = \App\Models\RbaHeader::create([
                'period_id' => $validated['period_id'],
                'year' => $validated['year'],
                'admin_id' => \Auth::id(),
                'status_global' => 'Draft',
            ]);

            // Create submissions for ALL units
            $units = \App\Models\Unit::all();
            foreach ($units as $unit) {
                \App\Models\RbaSubmission::create([
                    'rba_header_id' => $header->id,
                    'unit_id' => $unit->id,
                    'status_submission' => 'Draft',
                ]);
            }
        });

        return redirect()->route('admin.headers.index')->with('success', 'RBA Header and Unit Submissions created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\RbaHeader $header)
    {
        $header->load([
            'submissions.unit.users' => function ($q) {
                $q->where('is_active', true)->orderBy('role')->orderBy('name');
            },
            'submissions.operatorBackgrounds',
            'submissions.documents.latestVersion',
            'submissions.details' => function ($q) {
                $q->with(['attachments', 'creator', 'validator']);
            },
            'period',
            'admin'
        ]);

        // 1. Fetch all account codes
        $accountCodes = \App\Models\AccountCode::orderBy('code')->get();

        // 2. Fetch all RBA details for this header
        $submissionIds = $header->submissions->pluck('id');
        $details = \App\Models\RbaDetail::whereIn('rba_submission_id', $submissionIds)
            ->with(['creator', 'validator', 'attachments'])
            ->get();

        // 3. Fetch all Global Pagu for this header
        $pagus = \App\Models\RbaAccountPagu::where('rba_header_id', $header->id)->get()->keyBy('account_code_id');

        // Group details by account code
        $detailsByAccount = $details->groupBy('account_code_id');

        // Build the hierarchical tree
        $reportData = [];
        foreach ($accountCodes as $ac) {
            $code = $ac->code;
            $items = $detailsByAccount->get($ac->id, collect());

            $nominalUsulan = $items->sum('nominal_request');
            $nominalPagu = isset($pagus[$ac->id]) ? $pagus[$ac->id]->nominal_pagu : 0;

            // We also need to sum up children for parent nodes
            // But since we are iterating in order of code, we might need a post-processing or a recursive approach.
            // A simpler way: for each leaf detail, add its value to all its parent prefixes.

            $reportData[$code] = [
                'id' => $ac->id,
                'code' => $code,
                'name' => $ac->name,
                'usulan' => $nominalUsulan,
                'pagu' => $nominalPagu,
                'details' => $items, // List of individual items for this specific code
                'level' => count(explode('.', rtrim($code, '.'))),
            ];
        }

        // Post-process: Aggregate children into parents
        // We sort by code length descending to ensure children are processed before parents
        $codes = array_keys($reportData);
        usort($codes, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        foreach ($codes as $childCode) {
            $parts = explode('.', rtrim($childCode, '.'));
            if (count($parts) > 1) {
                array_pop($parts);
                $parentCode = implode('.', $parts);

                // If parent exists in our list, add child's values to it
                if (isset($reportData[$parentCode])) {
                    $reportData[$parentCode]['usulan'] += $reportData[$childCode]['usulan'];
                    $reportData[$parentCode]['pagu'] += $reportData[$childCode]['pagu'];
                }
            }
        }

        // Grand totals
        $totalUsulan = $details->sum('nominal_request');
        $totalPagu = $pagus->sum('nominal_pagu');

        $units = \App\Models\Unit::orderBy('name')->get();
        $allOperators = \App\Models\User::where('role', 'Operator')->with('unit')->orderBy('name')->get();

        // 4. Build Unit Monitoring Metrics (Supervisor & Operator Level)
        $unitMonitoring = $header->submissions->map(function ($submission) {
            $unit = $submission->unit;
            $supervisors = $unit ? $unit->users->where('role', 'Supervisor')->values() : collect();
            $operators = $unit ? $unit->users->where('role', 'Operator')->values() : collect();

            $submissionDetails = $submission->details;
            $totalNominalUnit = (float) $submissionDetails->sum('nominal_request');
            $validatedDetailsCount = $submissionDetails->where('is_validated', true)->count();
            $rejectedDetailsCount = $submissionDetails->where('is_rejected', true)->count();
            $totalDetailsCount = $submissionDetails->count();

            $operatorBackgrounds = $submission->operatorBackgrounds->keyBy('user_id');
            $documentsByUser = $submission->documents->groupBy('user_id');

            $operatorMetrics = $operators->map(function ($operator) use ($submission, $submissionDetails, $operatorBackgrounds, $documentsByUser) {
                // 1. Status Latar Belakang
                $hasOwnBg = $operatorBackgrounds->has($operator->id) && !empty(trim($operatorBackgrounds->get($operator->id)->background ?? ''));
                $hasLegacyBg = !empty(trim($submission->background ?? ''));
                $hasBackground = $hasOwnBg || ($operatorBackgrounds->isEmpty() && $hasLegacyBg);

                // 2. Besar Nominal Usulan Operator
                $opDetails = $submissionDetails->where('created_by', $operator->id);
                $nominalUsulan = (float) $opDetails->sum('nominal_request');
                $itemCount = $opDetails->count();

                // 3. Kelengkapan Upload PDF-PDF
                // a) KAK, RAK, RTP
                $userDocs = $documentsByUser->get($operator->id, collect());
                $kakDoc = $userDocs->firstWhere('type', 'KAK');
                $rakDoc = $userDocs->firstWhere('type', 'RAK');
                $rtpDoc = $userDocs->firstWhere('type', 'RTP');

                $hasKak = $kakDoc && $kakDoc->latestVersion !== null;
                $hasRak = $rakDoc && $rakDoc->latestVersion !== null;
                $hasRtp = $rtpDoc && $rtpDoc->latestVersion !== null;
                $mandatoryDocsCount = ($hasKak ? 1 : 0) + ($hasRak ? 1 : 0) + ($hasRtp ? 1 : 0);

                // b) PDF Lampiran Rincian Belanja
                $detailsWithPdf = $opDetails->filter(function ($d) {
                    return $d->attachments && $d->attachments->isNotEmpty();
                })->count();

                $isAllComplete = $hasBackground && $mandatoryDocsCount === 3 && ($itemCount === 0 || $detailsWithPdf === $itemCount);

                return [
                    'operator' => $operator,
                    'has_background' => $hasBackground,
                    'background_text' => $hasOwnBg ? $operatorBackgrounds->get($operator->id)->background : ($hasLegacyBg ? $submission->background : null),
                    'nominal_usulan' => $nominalUsulan,
                    'item_count' => $itemCount,
                    'has_kak' => $hasKak,
                    'has_rak' => $hasRak,
                    'has_rtp' => $hasRtp,
                    'mandatory_docs_count' => $mandatoryDocsCount,
                    'details_with_pdf_count' => $detailsWithPdf,
                    'total_details_count' => $itemCount,
                    'is_all_complete' => $isAllComplete,
                ];
            });

            return [
                'submission_id' => $submission->id,
                'status_submission' => $submission->status_submission,
                'unit' => $unit,
                'supervisors' => $supervisors,
                'total_nominal' => $totalNominalUnit,
                'total_details' => $totalDetailsCount,
                'validated_details' => $validatedDetailsCount,
                'rejected_details' => $rejectedDetailsCount,
                'operators_count' => $operators->count(),
                'operators_monitoring' => $operatorMetrics,
            ];
        });

        return view('admin.headers.show', compact('header', 'reportData', 'totalUsulan', 'totalPagu', 'units', 'allOperators', 'unitMonitoring'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function toggleStatus(\App\Models\RbaHeader $header)
    {
        $newStatus = $header->status_global === 'Draft' ? 'Locked' : 'Draft';
        $header->update(['status_global' => $newStatus]);

        $message = $newStatus === 'Locked'
            ? 'RBA berhasil terkunci. Operator sudah tidak bisa menambahkan rincian belanja.'
            : 'RBA berhasil terbuka. Operator sudah bisa menambahkan rincian belanja.';

        return back()->with('success', $message);
    }

    public function printPreview(Request $request, \App\Models\RbaHeader $header)
    {
        $includeBackground = $request->get('include_background', '1') == '1';

        $unitIdsRaw = $request->get('unit_ids', []);
        $selectedUnitIds = [];
        if (is_array($unitIdsRaw)) {
            $selectedUnitIds = array_filter(array_map('intval', $unitIdsRaw));
        } elseif (is_string($unitIdsRaw) && trim($unitIdsRaw) !== '') {
            $selectedUnitIds = array_filter(array_map('intval', explode(',', $unitIdsRaw)));
        }

        $operatorIdsRaw = $request->get('operator_ids', []);
        $selectedOperatorIds = [];
        if (is_array($operatorIdsRaw)) {
            $selectedOperatorIds = array_filter(array_map('intval', $operatorIdsRaw));
        } elseif (is_string($operatorIdsRaw) && trim($operatorIdsRaw) !== '') {
            $selectedOperatorIds = array_filter(array_map('intval', explode(',', $operatorIdsRaw)));
        }

        $units = \App\Models\Unit::orderBy('name')->get();
        $allOperators = \App\Models\User::where('role', 'Operator')->with('unit')->orderBy('name')->get();

        $submissionsQuery = $header->submissions();
        if (!empty($selectedUnitIds) && empty($selectedOperatorIds)) {
            $submissionsQuery->whereIn('unit_id', $selectedUnitIds);
        }
        $submissions = $submissionsQuery->with('unit')->get();
        $submissionIds = $submissions->pluck('id');

        $detailsQuery = \App\Models\RbaDetail::whereIn('rba_submission_id', $submissionIds)
            ->with(['accountCode', 'creator', 'submission.unit']);

        if (!empty($selectedUnitIds) && !empty($selectedOperatorIds)) {
            $detailsQuery->where(function ($q) use ($selectedUnitIds, $selectedOperatorIds) {
                $q->whereHas('submission', function ($subQ) use ($selectedUnitIds) {
                    $subQ->whereIn('unit_id', $selectedUnitIds);
                })->orWhereIn('created_by', $selectedOperatorIds);
            });
        } elseif (!empty($selectedOperatorIds)) {
            $detailsQuery->whereIn('created_by', $selectedOperatorIds);
        }

        $details = $detailsQuery->get();

        $currentYear = $header->year;
        $currentPeriodName = $header->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = \App\Models\RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $header->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            $previousHeader = \App\Models\RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = \App\Models\RbaHeader::where('id', '<', $header->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? \App\Models\RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        $filterLabels = [];
        if (empty($selectedUnitIds) && empty($selectedOperatorIds)) {
            $filterLabels[] = 'Seluruh Unit Kerja & Operator (RSUD Kardinah)';
        } else {
            if (!empty($selectedUnitIds)) {
                $unitNames = $units->whereIn('id', $selectedUnitIds)->pluck('name')->toArray();
                $filterLabels[] = 'Unit: ' . implode(', ', $unitNames);
            }
            if (!empty($selectedOperatorIds)) {
                $opNames = $allOperators->whereIn('id', $selectedOperatorIds)->pluck('name')->toArray();
                $filterLabels[] = 'Operator: ' . implode(', ', $opNames);
            }
        }
        $filterLabel = implode(' | ', $filterLabels);

        return view('reports.admin_rba_print', compact(
            'header', 'details', 'includeBackground', 'previousPagus',
            'units', 'allOperators', 'selectedUnitIds', 'selectedOperatorIds', 'filterLabel'
        ));
    }

    public function printPreviewFinal(Request $request, \App\Models\RbaHeader $header)
    {
        $includeBackground = $request->get('include_background', '1') == '1';

        $unitIdsRaw = $request->get('unit_ids', []);
        $selectedUnitIds = [];
        if (is_array($unitIdsRaw)) {
            $selectedUnitIds = array_filter(array_map('intval', $unitIdsRaw));
        } elseif (is_string($unitIdsRaw) && trim($unitIdsRaw) !== '') {
            $selectedUnitIds = array_filter(array_map('intval', explode(',', $unitIdsRaw)));
        }

        $operatorIdsRaw = $request->get('operator_ids', []);
        $selectedOperatorIds = [];
        if (is_array($operatorIdsRaw)) {
            $selectedOperatorIds = array_filter(array_map('intval', $operatorIdsRaw));
        } elseif (is_string($operatorIdsRaw) && trim($operatorIdsRaw) !== '') {
            $selectedOperatorIds = array_filter(array_map('intval', explode(',', $operatorIdsRaw)));
        }

        $units = \App\Models\Unit::orderBy('name')->get();
        $allOperators = \App\Models\User::where('role', 'Operator')->with('unit')->orderBy('name')->get();

        $submissionsQuery = $header->submissions();
        if (!empty($selectedUnitIds) && empty($selectedOperatorIds)) {
            $submissionsQuery->whereIn('unit_id', $selectedUnitIds);
        }
        $submissions = $submissionsQuery->with('unit')->get();
        $submissionIds = $submissions->pluck('id');

        $detailsQuery = \App\Models\RbaDetail::whereIn('rba_submission_id', $submissionIds)
            ->with(['accountCode', 'creator', 'submission.unit']);

        if (!empty($selectedUnitIds) && !empty($selectedOperatorIds)) {
            $detailsQuery->where(function ($q) use ($selectedUnitIds, $selectedOperatorIds) {
                $q->whereHas('submission', function ($subQ) use ($selectedUnitIds) {
                    $subQ->whereIn('unit_id', $selectedUnitIds);
                })->orWhereIn('created_by', $selectedOperatorIds);
            });
        } elseif (!empty($selectedOperatorIds)) {
            $detailsQuery->whereIn('created_by', $selectedOperatorIds);
        }

        $details = $detailsQuery->get();

        $pagus = \App\Models\RbaAccountPagu::where('rba_header_id', $header->id)->get()->keyBy('account_code_id');

        $currentYear = $header->year;
        $currentPeriodName = $header->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = \App\Models\RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $header->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            $previousHeader = \App\Models\RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = \App\Models\RbaHeader::where('id', '<', $header->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? \App\Models\RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        $filterLabels = [];
        if (empty($selectedUnitIds) && empty($selectedOperatorIds)) {
            $filterLabels[] = 'Seluruh Unit Kerja & Operator (RSUD Kardinah)';
        } else {
            if (!empty($selectedUnitIds)) {
                $unitNames = $units->whereIn('id', $selectedUnitIds)->pluck('name')->toArray();
                $filterLabels[] = 'Unit: ' . implode(', ', $unitNames);
            }
            if (!empty($selectedOperatorIds)) {
                $opNames = $allOperators->whereIn('id', $selectedOperatorIds)->pluck('name')->toArray();
                $filterLabels[] = 'Operator: ' . implode(', ', $opNames);
            }
        }
        $filterLabel = implode(' | ', $filterLabels);

        return view('reports.admin_rba_final_print', compact(
            'header', 'submissions', 'details', 'pagus', 'includeBackground', 'previousPagus',
            'units', 'allOperators', 'selectedUnitIds', 'selectedOperatorIds', 'filterLabel'
        ));
    }
}
