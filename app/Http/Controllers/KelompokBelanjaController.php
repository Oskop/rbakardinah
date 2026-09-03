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
        $groups = \App\Models\KelompokBelanja::withCount('accountCodes')->orderBy('kode')->get();
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
            'kode' => 'required|string|max:50|unique:kelompok_belanjas,kode',
            'name' => 'required|string|max:150|unique:kelompok_belanjas,name',
            'is_active' => 'sometimes|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

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
            'kode' => 'required|string|max:50|unique:kelompok_belanjas,kode,' . $kelompokBelanja->id,
            'name' => 'required|string|max:150|unique:kelompok_belanjas,name,' . $kelompokBelanja->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $kelompokBelanja->update($validated);

        return redirect()->route('admin.kelompok-belanja.index')->with('success', 'Kelompok Belanja updated successfully.');
    }

    /**
     * Toggle Kelompok Belanja active status instead of permanent deletion.
     */
    public function destroy(\App\Models\KelompokBelanja $kelompokBelanja)
    {
        $kelompokBelanja->update(['is_active' => !$kelompokBelanja->is_active]);

        $status = $kelompokBelanja->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.kelompok-belanja.index')->with('success', "Kelompok Belanja {$kelompokBelanja->name} berhasil {$status}.");
    }
}
