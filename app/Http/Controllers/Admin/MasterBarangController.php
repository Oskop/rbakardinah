<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterBarang;
use Illuminate\Http\Request;

class MasterBarangController extends Controller
{
    public function index()
    {
        $items = MasterBarang::latest()->get();
        return view('admin.master_barangs.index', compact('items'));
    }

    public function create()
    {
        return view('admin.master_barangs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:100|unique:master_barangs,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
        ]);

        $validated['is_active'] = true;

        MasterBarang::create($validated);

        return redirect()->route('admin.master-barangs.index')
            ->with('success', 'Master Barang BMD Permendagri 108 berhasil ditambahkan.');
    }

    public function edit(MasterBarang $masterBarang)
    {
        return view('admin.master_barangs.edit', compact('masterBarang'));
    }

    public function update(Request $request, MasterBarang $masterBarang)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:100|unique:master_barangs,kode_barang,' . $masterBarang->id,
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $masterBarang->update($validated);

        return redirect()->route('admin.master-barangs.index')
            ->with('success', 'Master Barang BMD berhasil diperbarui.');
    }

    public function destroy(MasterBarang $masterBarang)
    {
        $masterBarang->update(['is_active' => !$masterBarang->is_active]);

        $status = $masterBarang->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.master-barangs.index')
            ->with('success', "Master Barang BMD berhasil {$status}.");
    }
}
