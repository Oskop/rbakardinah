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
        $periods = \App\Models\RbaPeriod::all();
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
        $header->load(['submissions.unit', 'period', 'admin']);

        // 1. Fetch all account codes
        $accountCodes = \App\Models\AccountCode::orderBy('code')->get();

        // 2. Fetch all RBA details for this header
        $submissionIds = $header->submissions->pluck('id');
        $details = \App\Models\RbaDetail::whereIn('rba_submission_id', $submissionIds)
            ->with(['creator', 'validator'])
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

        // Final sort by code ascending for display
        ksort($reportData);

        return view('admin.headers.show', compact('header', 'reportData'));
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
}
