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
        $periods = \App\Models\RbaPeriod::all();
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
        ]);

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
        ]);

        $period->update($validated);

        return redirect()->route('admin.periods.index')->with('success', 'RBA Period updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\RbaPeriod $period)
    {
        $period->delete();

        return redirect()->route('admin.periods.index')->with('success', 'RBA Period deleted successfully.');
    }
}
