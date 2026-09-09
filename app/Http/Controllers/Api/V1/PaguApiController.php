<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RbaAccountPagu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaguApiController extends Controller
{
    /**
     * Display a listing of RBA Account Pagus with extensive filtering and metadata.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RbaAccountPagu::with([
            'header.period',
            'accountCode.kelompokBelanja',
        ]);

        $appliedFilters = [];

        // 1. Filter: Tahun RBA
        $year = $request->query('year', $request->query('tahun'));
        if (!empty($year)) {
            $query->whereHas('header', fn($q) => $q->where('year', (int) $year));
            $appliedFilters['year'] = (int) $year;
        }

        // 2. Filter: Periode RBA (Nama atau ID)
        $period = $request->query('period', $request->query('periode'));
        if (!empty($period)) {
            $query->whereHas('header.period', fn($q) => $q->where('name', 'like', "%{$period}%"));
            $appliedFilters['period'] = $period;
        }

        $periodId = $request->query('period_id');
        if (!empty($periodId)) {
            $query->whereHas('header', fn($q) => $q->where('period_id', (int) $periodId));
            $appliedFilters['period_id'] = (int) $periodId;
        }

        // 3. Filter: Status Global RBA
        $status = $request->query('status', $request->query('status_rba'));
        if (!empty($status)) {
            $query->whereHas('header', fn($q) => $q->where('status_global', $status));
            $appliedFilters['status'] = $status;
        }

        // 4. Filter: Kode Kelompok Belanja
        $kodeKb = $request->query('kode_kelompok_belanja', $request->query('kelompok_belanja_kode'));
        if (!empty($kodeKb)) {
            $query->whereHas('accountCode.kelompokBelanja', fn($q) => $q->where('kode', $kodeKb));
            $appliedFilters['kode_kelompok_belanja'] = $kodeKb;
        }

        // 5. Filter: Nama Kelompok Belanja
        $namaKb = $request->query('nama_kelompok_belanja', $request->query('kelompok_belanja_name'));
        if (!empty($namaKb)) {
            $query->whereHas('accountCode.kelompokBelanja', fn($q) => $q->where('name', 'like', "%{$namaKb}%"));
            $appliedFilters['nama_kelompok_belanja'] = $namaKb;
        }

        // 6. Filter: Kode Nomor Rekening
        $kodeRek = $request->query('kode_rekening', $request->query('account_code', $request->query('kode_nomor_rekening')));
        if (!empty($kodeRek)) {
            $query->whereHas('accountCode', fn($q) => $q->where('code', 'like', "{$kodeRek}%"));
            $appliedFilters['kode_rekening'] = $kodeRek;
        }

        // 7. Filter: Uraian / Nama Nomor Rekening
        $namaRek = $request->query('nama_rekening', $request->query('account_name', $request->query('uraian_nomor_rekening', $request->query('uraian_rekening'))));
        if (!empty($namaRek)) {
            $query->whereHas('accountCode', fn($q) => $q->where('name', 'like', "%{$namaRek}%"));
            $appliedFilters['nama_rekening'] = $namaRek;
        }

        // 8. Filter: Nominal Pagu (Rentang / Nilai Tepat)
        $minPagu = $request->query('min_pagu');
        if ($minPagu !== null && $minPagu !== '') {
            $query->where('nominal_pagu', '>=', (float) $minPagu);
            $appliedFilters['min_pagu'] = (float) $minPagu;
        }

        $maxPagu = $request->query('max_pagu');
        if ($maxPagu !== null && $maxPagu !== '') {
            $query->where('nominal_pagu', '<=', (float) $maxPagu);
            $appliedFilters['max_pagu'] = (float) $maxPagu;
        }

        $exactPagu = $request->query('exact_pagu', $request->query('nilai_pagu'));
        if ($exactPagu !== null && $exactPagu !== '') {
            $query->where('nominal_pagu', (float) $exactPagu);
            $appliedFilters['exact_pagu'] = (float) $exactPagu;
        }

        // 9. Quick Search (q / search)
        $search = $request->query('q', $request->query('search'));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('accountCode', function ($acQ) use ($search) {
                    $acQ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })->orWhereHas('accountCode.kelompokBelanja', function ($kbQ) use ($search) {
                    $kbQ->where('kode', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            });
            $appliedFilters['search'] = $search;
        }

        // Kalkulasi Total Agregasi Berdasarkan Hasil Filter
        $totalNominalPagu = (float) (clone $query)->sum('nominal_pagu');
        $totalRecords = (int) (clone $query)->count();

        // 10. Sorting
        $sortBy = $request->query('sort_by', 'code');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'nominal_pagu') {
            $query->orderBy('nominal_pagu', $sortDir);
        } elseif ($sortBy === 'id') {
            $query->orderBy('id', $sortDir);
        } else {
            // Default sort by account_codes.code
            $query->join('account_codes', 'rba_account_pagus.account_code_id', '=', 'account_codes.id')
                  ->orderBy('account_codes.code', $sortDir)
                  ->select('rba_account_pagus.*');
        }

        // 11. Pagination / All
        $fetchAll = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN)
            || $request->query('paginate') === 'false';

        $perPage = min(max((int) $request->query('per_page', $request->query('limit', 50)), 1), 200);

        if ($fetchAll) {
            $pagus = $query->get();
            $paginationMeta = [
                'current_page' => 1,
                'per_page' => $totalRecords,
                'total_pages' => 1,
                'has_more' => false,
            ];
        } else {
            $paginated = $query->paginate($perPage);
            $pagus = $paginated->items();
            $paginationMeta = [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total_pages' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'has_more' => $paginated->hasMorePages(),
            ];
        }

        // 12. Transform Resource Data
        $data = collect($pagus)->map(function ($pagu) {
            $header = $pagu->header;
            $accountCode = $pagu->accountCode;
            $kelompokBelanja = $accountCode?->kelompokBelanja;

            return [
                'id' => $pagu->id,
                'rba_header_id' => $pagu->rba_header_id,
                'year' => (int) ($header->year ?? 0),
                'period' => $header->period->name ?? '-',
                'status_rba' => $header->status_global ?? 'Draft',
                'kode_kelompok_belanja' => $kelompokBelanja->kode ?? '-',
                'nama_kelompok_belanja' => $kelompokBelanja->name ?? '-',
                'kode_rekening' => $accountCode->code ?? '-',
                'nama_rekening' => $accountCode->name ?? '-',
                'nominal_pagu' => (float) $pagu->nominal_pagu,
                'nominal_pagu_formatted' => 'Rp ' . number_format($pagu->nominal_pagu, 0, ',', '.'),
                'updated_at' => $pagu->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data pagu RBA berhasil diambil.',
            'meta' => [
                'total_records' => $totalRecords,
                'total_nominal_pagu' => $totalNominalPagu,
                'total_nominal_pagu_formatted' => 'Rp ' . number_format($totalNominalPagu, 0, ',', '.'),
                'pagination' => $paginationMeta,
                'filters_applied' => $appliedFilters,
            ],
            'data' => $data,
        ]);
    }
}
