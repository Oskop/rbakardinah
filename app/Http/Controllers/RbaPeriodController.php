<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RbaPeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $periods = \App\Models\RbaPeriod::withCount('headers')->orderBy('name')->get();
        return view('admin.periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.periods.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rba_periods,name',
            'is_active' => 'sometimes|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        \App\Models\RbaPeriod::create($validated);

        return redirect()->route('admin.periods.index')->with('success', 'RBA Period created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\RbaPeriod $period)
    {
        return view('admin.periods.show', compact('period'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\RbaPeriod $period)
    {
        return view('admin.periods.edit', compact('period'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\RbaPeriod $period)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rba_periods,name,' . $period->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $period->update($validated);

        return redirect()->route('admin.periods.index')->with('success', 'RBA Period updated successfully.');
    }

    /**
     * Toggle RBA Period active status instead of permanent deletion.
     */
    public function destroy(\App\Models\RbaPeriod $period)
    {
        $period->update(['is_active' => !$period->is_active]);

        $status = $period->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.periods.index')->with('success', "Periode RBA {$period->name} berhasil {$status}.");
    }
}
