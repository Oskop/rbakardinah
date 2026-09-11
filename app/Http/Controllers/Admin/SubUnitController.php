<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubUnit;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubUnitController extends Controller
{
    /**
     * Display a listing of the sub-units.
     */
    public function index(Request $request)
    {
        $query = SubUnit::with('unit')->withCount('users');

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('unit', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $subUnits = $query->orderBy('unit_id')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $types = ['Sub Bagian', 'Sub Bidang', 'Seksi', 'Instalasi', 'Komite', 'Tim Kerja', 'Unit'];

        return view('admin.sub_units.index', compact('subUnits', 'units', 'types'));
    }

    /**
     * Show the form for creating a new sub-unit.
     */
    public function create()
    {
        $units = Unit::orderBy('name')->get();
        $types = ['Sub Bagian', 'Sub Bidang', 'Seksi', 'Instalasi', 'Komite', 'Tim Kerja', 'Unit'];

        return view('admin.sub_units.create', compact('units', 'types'));
    }

    /**
     * Store a newly created sub-unit in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'code' => 'nullable|string|max:50|unique:sub_units,code',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        SubUnit::create($validated);

        return redirect()->route('admin.sub-units.index')->with('success', 'Sub-Unit berhasil ditambahkan.');
    }

    /**
     * Display the specified sub-unit.
     */
    public function show(SubUnit $subUnit)
    {
        $subUnit->load(['unit', 'users']);
        return view('admin.sub_units.show', compact('subUnit'));
    }

    /**
     * Show the form for editing the specified sub-unit.
     */
    public function edit(SubUnit $subUnit)
    {
        $units = Unit::orderBy('name')->get();
        $types = ['Sub Bagian', 'Sub Bidang', 'Seksi', 'Instalasi', 'Komite', 'Tim Kerja', 'Unit'];

        return view('admin.sub_units.edit', compact('subUnit', 'units', 'types'));
    }

    /**
     * Update the specified sub-unit in storage.
     */
    public function update(Request $request, SubUnit $subUnit)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'code' => 'nullable|string|max:50|unique:sub_units,code,' . $subUnit->id,
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $subUnit->update($validated);

        return redirect()->route('admin.sub-units.index')->with('success', 'Sub-Unit berhasil diperbarui.');
    }

    /**
     * Toggle sub-unit active status.
     */
    public function destroy(SubUnit $subUnit)
    {
        $subUnit->update(['is_active' => !$subUnit->is_active]);

        $status = $subUnit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.sub-units.index')->with('success', "Sub-Unit {$subUnit->name} berhasil {$status}.");
    }

    /**
     * AJAX endpoint to get sub-units belonging to a specific parent unit.
     */
    public function getByUnit(Unit $unit): JsonResponse
    {
        $subUnits = $unit->subUnits()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'unit_id', 'code', 'name', 'type']);

        return response()->json($subUnits);
    }
}
