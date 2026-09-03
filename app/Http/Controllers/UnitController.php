<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = \App\Models\Unit::orderBy('name')->get();
        return view('admin.units.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.units.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:units,code',
            'name' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        \App\Models\Unit::create($validated);

        return redirect()->route('admin.units.index')->with('success', 'Unit created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Unit $unit)
    {
        return view('admin.units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\Unit $unit)
    {
        return view('admin.units.edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Unit $unit)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,
            'name' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $unit->update($validated);

        return redirect()->route('admin.units.index')->with('success', 'Unit updated successfully.');
    }

    /**
     * Toggle unit active status instead of permanent deletion.
     */
    public function destroy(\App\Models\Unit $unit)
    {
        $unit->update(['is_active' => !$unit->is_active]);

        $status = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.units.index')->with('success', "Unit {$unit->name} berhasil {$status}.");
    }
}
