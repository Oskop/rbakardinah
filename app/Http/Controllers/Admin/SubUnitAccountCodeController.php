<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubUnitAccountCode;
use App\Models\SubUnit;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use Illuminate\Http\Request;

class SubUnitAccountCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SubUnitAccountCode::with([
            'subUnit.unit',
            'accountCode.kelompokBelanja'
        ]);

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        if ($request->filled('sub_unit_id')) {
            $query->where('sub_unit_id', $request->sub_unit_id);
        }

        if ($request->filled('kelompok_belanja_id')) {
            $query->whereHas('accountCode', function ($q) use ($request) {
                $q->where('kelompok_belanja_id', $request->kelompok_belanja_id);
            });
        }

        $mappings = $query->orderBy('sub_unit_id')->get();

        // Statistics
        $totalMappings = SubUnitAccountCode::count();
        $totalSubUnits = SubUnitAccountCode::distinct('sub_unit_id')->count('sub_unit_id');
        $totalAccounts = SubUnitAccountCode::distinct('account_code_id')->count('account_code_id');
        $years = SubUnitAccountCode::distinct('fiscal_year')->pluck('fiscal_year')->filter()->values();
        if ($years->isEmpty()) {
            $years = collect(['2027']);
        }

        $subUnits = SubUnit::active()->with('unit')->orderBy('name')->get();
        $accountCodes = AccountCode::where('is_active', true)->with('kelompokBelanja')->orderBy('code')->get();
        $kelompokBelanjas = KelompokBelanja::where('is_active', true)->orderBy('kode')->get();

        return view('admin.sub_unit_account_codes.index', compact(
            'mappings',
            'totalMappings',
            'totalSubUnits',
            'totalAccounts',
            'years',
            'subUnits',
            'accountCodes',
            'kelompokBelanjas'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sub_unit_id' => 'required|exists:sub_units,id',
            'account_code_id' => 'required|exists:account_codes,id',
            'fiscal_year' => 'required|string|max:10',
            'keterangan_khusus' => 'nullable|string|max:500',
        ], [
            'sub_unit_id.required' => 'Sub-unit kerja wajib dipilih.',
            'account_code_id.required' => 'Nomor rekening belanja wajib dipilih.',
            'fiscal_year.required' => 'Tahun anggaran wajib diisi.',
        ]);

        $exists = SubUnitAccountCode::where('sub_unit_id', $request->sub_unit_id)
            ->where('account_code_id', $request->account_code_id)
            ->where('fiscal_year', $request->fiscal_year)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Relasi mapping untuk sub-unit dan nomor rekening tersebut sudah terdaftar pada tahun anggaran ' . $request->fiscal_year);
        }

        SubUnitAccountCode::create([
            'sub_unit_id' => $request->sub_unit_id,
            'account_code_id' => $request->account_code_id,
            'fiscal_year' => $request->fiscal_year,
            'keterangan_khusus' => $request->keterangan_khusus,
        ]);

        return back()->with('success', 'Mapping rekening ke sub-unit berhasil ditambahkan.');
    }

    /**
     * Store multiple account codes for a single sub-unit in bulk.
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'sub_unit_id' => 'required|exists:sub_units,id',
            'account_code_ids' => 'required|array|min:1',
            'account_code_ids.*' => 'exists:account_codes,id',
            'fiscal_year' => 'required|string|max:10',
            'keterangan_khusus' => 'nullable|string|max:500',
        ], [
            'sub_unit_id.required' => 'Sub-unit kerja wajib dipilih.',
            'account_code_ids.required' => 'Pilih minimal satu nomor rekening.',
            'account_code_ids.min' => 'Pilih minimal satu nomor rekening.',
            'fiscal_year.required' => 'Tahun anggaran wajib diisi.',
        ]);

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($request->account_code_ids as $codeId) {
            $mapping = SubUnitAccountCode::firstOrCreate(
                [
                    'sub_unit_id' => $request->sub_unit_id,
                    'account_code_id' => $codeId,
                    'fiscal_year' => $request->fiscal_year,
                ],
                [
                    'keterangan_khusus' => $request->keterangan_khusus,
                ]
            );

            if ($mapping->wasRecentlyCreated) {
                $createdCount++;
            } else {
                $skippedCount++;
            }
        }

        $msg = "Berhasil menambahkan {$createdCount} mapping rekening.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} rekening dilewati karena sudah ada).";
        }

        return back()->with('success', $msg);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubUnitAccountCode $subUnitAccountCode)
    {
        $request->validate([
            'sub_unit_id' => 'required|exists:sub_units,id',
            'account_code_id' => 'required|exists:account_codes,id',
            'fiscal_year' => 'required|string|max:10',
            'keterangan_khusus' => 'nullable|string|max:500',
        ], [
            'sub_unit_id.required' => 'Sub-unit kerja wajib dipilih.',
            'account_code_id.required' => 'Nomor rekening belanja wajib dipilih.',
            'fiscal_year.required' => 'Tahun anggaran wajib diisi.',
        ]);

        $conflict = SubUnitAccountCode::where('sub_unit_id', $request->sub_unit_id)
            ->where('account_code_id', $request->account_code_id)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('id', '!=', $subUnitAccountCode->id)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Kombinasi sub-unit, nomor rekening, dan tahun anggaran tersebut sudah digunakan oleh data lain.');
        }

        $subUnitAccountCode->update([
            'sub_unit_id' => $request->sub_unit_id,
            'account_code_id' => $request->account_code_id,
            'fiscal_year' => $request->fiscal_year,
            'keterangan_khusus' => $request->keterangan_khusus,
        ]);

        return back()->with('success', 'Data mapping rekening berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubUnitAccountCode $subUnitAccountCode)
    {
        $subUnitAccountCode->delete();

        return back()->with('success', 'Mapping rekening berhasil dihapus.');
    }
}
