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
        return view('admin.headers.show', compact('header'));
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
