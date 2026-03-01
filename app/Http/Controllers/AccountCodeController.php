<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accountCodes = \App\Models\AccountCode::with('kelompokBelanja')->get();
        return view('admin.account-codes.index', compact('accountCodes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $groups = \App\Models\KelompokBelanja::all();
        return view('admin.account-codes.create', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelompok_belanja_id' => 'required|exists:kelompok_belanjas,id',
            'code' => 'required|string|max:20|unique:account_codes,code',
            'name' => 'required|string|max:150',
        ]);

        \App\Models\AccountCode::create($validated);

        return redirect()->route('admin.account-codes.index')->with('success', 'Account Code created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\AccountCode $accountCode)
    {
        return view('admin.account-codes.show', compact('accountCode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\AccountCode $accountCode)
    {
        $groups = \App\Models\KelompokBelanja::all();
        return view('admin.account-codes.edit', compact('accountCode', 'groups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\AccountCode $accountCode)
    {
        $validated = $request->validate([
            'kelompok_belanja_id' => 'required|exists:kelompok_belanjas,id',
            'code' => 'required|string|max:20|unique:account_codes,code,' . $accountCode->id,
            'name' => 'required|string|max:150',
        ]);

        $accountCode->update($validated);

        return redirect()->route('admin.account-codes.index')->with('success', 'Account Code updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\AccountCode $accountCode)
    {
        $accountCode->delete();

        return redirect()->route('admin.account-codes.index')->with('success', 'Account Code deleted successfully.');
    }
}
