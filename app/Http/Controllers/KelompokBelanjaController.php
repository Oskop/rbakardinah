<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KelompokBelanjaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = \App\Models\KelompokBelanja::all();
        return view('admin.kelompok-belanjas.index', compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.kelompok-belanjas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:kelompok_belanjas,name',
        ]);

        \App\Models\KelompokBelanja::create($validated);

        return redirect()->route('admin.kelompok-belanja.index')->with('success', 'Kelompok Belanja created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\KelompokBelanja $kelompokBelanja)
    {
        return view('admin.kelompok-belanjas.edit', compact('kelompokBelanja'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\Illuminate\Http\Request $request, \App\Models\KelompokBelanja $kelompokBelanja)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:kelompok_belanjas,name,' . $kelompokBelanja->id,
        ]);

        $kelompokBelanja->update($validated);

        return redirect()->route('admin.kelompok-belanja.index')->with('success', 'Kelompok Belanja updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\KelompokBelanja $kelompokBelanja)
    {
        // Check if there are account codes using this group
        if ($kelompokBelanja->accountCodes()->count() > 0) {
            return redirect()->route('admin.kelompok-belanja.index')->with('error', 'Cannot delete group that is currently used by Account Codes.');
        }

        $kelompokBelanja->delete();

        return redirect()->route('admin.kelompok-belanja.index')->with('success', 'Kelompok Belanja deleted successfully.');
    }
}
