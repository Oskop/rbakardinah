<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ReportSortingService
{
    /**
     * Mengurutkan koleksi RbaDetail berdasarkan kolom dan arah yang ditentukan.
     *
     * @param  Collection  $details
     * @param  string  $sortBy
     * @param  string  $sortDir
     * @param  Collection|null  $pagus
     * @return Collection
     */
    public function sortDetails(Collection $details, string $sortBy = 'account_code', string $sortDir = 'asc', ?Collection $pagus = null): Collection
    {
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        return $details->sort(function ($a, $b) use ($sortBy, $sortDir, $pagus) {
            $valA = null;
            $valB = null;

            switch ($sortBy) {
                case 'account_name':
                    $valA = strtolower($a->accountCode->name ?? '');
                    $valB = strtolower($b->accountCode->name ?? '');
                    break;

                case 'description':
                    $valA = strtolower($a->description ?? '');
                    $valB = strtolower($b->description ?? '');
                    break;

                case 'volume':
                    $valA = (float)($a->volume ?? 0);
                    $valB = (float)($b->volume ?? 0);
                    break;

                case 'satuan':
                    $valA = strtolower($a->satuan ?? '');
                    $valB = strtolower($b->satuan ?? '');
                    break;

                case 'harga_satuan':
                    $valA = (float)($a->harga_satuan ?? 0);
                    $valB = (float)($b->harga_satuan ?? 0);
                    break;

                case 'nominal_request':
                case 'total_usulan':
                    $valA = (float)($a->nominal_request ?? 0);
                    $valB = (float)($b->nominal_request ?? 0);
                    break;

                case 'pagu':
                case 'nominal_pagu':
                case 'pagu_final':
                    $valA = (float)($pagus[$a->account_code_id]->nominal_pagu ?? 0);
                    $valB = (float)($pagus[$b->account_code_id]->nominal_pagu ?? 0);
                    break;

                case 'unit':
                    $valA = strtolower($a->submission->unit->name ?? '');
                    $valB = strtolower($b->submission->unit->name ?? '');
                    break;

                case 'creator':
                case 'operator':
                    $valA = strtolower($a->creator->name ?? '');
                    $valB = strtolower($b->creator->name ?? '');
                    break;

                case 'account_code':
                default:
                    $valA = $a->accountCode->code ?? '';
                    $valB = $b->accountCode->code ?? '';
                    break;
            }

            if ($valA == $valB) {
                // Secondary sorting untuk kestabilan (kode akun lalu id)
                $codeA = $a->accountCode->code ?? '';
                $codeB = $b->accountCode->code ?? '';
                if ($codeA != $codeB) {
                    return $codeA <=> $codeB;
                }
                return ($a->id <=> $b->id) * ($sortDir === 'desc' ? -1 : 1);
            }

            $cmp = ($valA <=> $valB);
            return $sortDir === 'desc' ? -$cmp : $cmp;
        })->values();
    }

    /**
     * Mengurutkan rincian belanja berformat grup rekening.
     *
     * @param  Collection  $groupedDetails
     * @param  string  $sortBy
     * @param  string  $sortDir
     * @param  Collection|null  $pagus
     * @return Collection
     */
    public function sortGroupedDetails(Collection $groupedDetails, string $sortBy = 'account_code', string $sortDir = 'asc', ?Collection $pagus = null): Collection
    {
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        // 1. Urutkan item detail di dalam tiap-tiap grup rekening
        $groupedWithSortedItems = $groupedDetails->map(function ($items) use ($sortBy, $sortDir, $pagus) {
            return $this->sortDetails($items, $sortBy, $sortDir, $pagus);
        });

        // 2. Urutkan susunan grup rekening
        return $groupedWithSortedItems->sort(function ($groupA, $groupB) use ($sortBy, $sortDir, $pagus) {
            $firstA = $groupA->first();
            $firstB = $groupB->first();
            $codeA = $firstA?->accountCode;
            $codeB = $firstB?->accountCode;

            $valA = null;
            $valB = null;

            switch ($sortBy) {
                case 'account_name':
                    $valA = strtolower($codeA->name ?? '');
                    $valB = strtolower($codeB->name ?? '');
                    break;

                case 'nominal_request':
                case 'total_usulan':
                    $valA = (float)$groupA->sum('nominal_request');
                    $valB = (float)$groupB->sum('nominal_request');
                    break;

                case 'pagu':
                case 'nominal_pagu':
                case 'pagu_final':
                    $valA = (float)($pagus[$firstA->account_code_id]->nominal_pagu ?? 0);
                    $valB = (float)($pagus[$firstB->account_code_id]->nominal_pagu ?? 0);
                    break;

                case 'description':
                    $valA = strtolower($firstA->description ?? '');
                    $valB = strtolower($firstB->description ?? '');
                    break;

                case 'account_code':
                default:
                    $valA = $codeA->code ?? '';
                    $valB = $codeB->code ?? '';
                    break;
            }

            if ($valA == $valB) {
                return ($codeA->code ?? '') <=> ($codeB->code ?? '');
            }

            $cmp = ($valA <=> $valB);
            return $sortDir === 'desc' ? -$cmp : $cmp;
        });
    }

    /**
     * Mengurutkan daftar AccountCode yang akan dirender pada format grup admin RBA final.
     *
     * @param  Collection  $accountCodes
     * @param  Collection  $detailsByAccount
     * @param  string  $sortBy
     * @param  string  $sortDir
     * @param  Collection|null  $pagus
     * @return Collection
     */
    public function sortAccountCodes(Collection $accountCodes, Collection $detailsByAccount, string $sortBy = 'account_code', string $sortDir = 'asc', ?Collection $pagus = null): Collection
    {
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        return $accountCodes->sort(function ($codeA, $codeB) use ($detailsByAccount, $sortBy, $sortDir, $pagus) {
            $valA = null;
            $valB = null;

            switch ($sortBy) {
                case 'account_name':
                    $valA = strtolower($codeA->name ?? '');
                    $valB = strtolower($codeB->name ?? '');
                    break;

                case 'nominal_request':
                case 'total_usulan':
                    $groupA = $detailsByAccount->get($codeA->id);
                    $groupB = $detailsByAccount->get($codeB->id);
                    $valA = (float)($groupA ? $groupA->sum('nominal_request') : 0);
                    $valB = (float)($groupB ? $groupB->sum('nominal_request') : 0);
                    break;

                case 'pagu':
                case 'nominal_pagu':
                case 'pagu_final':
                    $valA = (float)($pagus[$codeA->id]->nominal_pagu ?? 0);
                    $valB = (float)($pagus[$codeB->id]->nominal_pagu ?? 0);
                    break;

                case 'account_code':
                default:
                    $valA = $codeA->code ?? '';
                    $valB = $codeB->code ?? '';
                    break;
            }

            if ($valA == $valB) {
                return ($codeA->code ?? '') <=> ($codeB->code ?? '');
            }

            $cmp = ($valA <=> $valB);
            return $sortDir === 'desc' ? -$cmp : $cmp;
        })->values();
    }

    /**
     * Menghasilkan teks label informasi pengurutan untuk header dokumen cetak.
     *
     * @param  string  $sortBy
     * @param  string  $sortDir
     * @return string
     */
    public function getSortLabel(string $sortBy = 'account_code', string $sortDir = 'asc'): string
    {
        $dirLabel = strtolower($sortDir) === 'desc' ? 'Menurun (Z-A / Besar-Kecil)' : 'Menaik (A-Z / Kecil-Besar)';

        $columnLabels = [
            'account_code'    => 'Nomor Rekening Belanja',
            'account_name'    => 'Nama Rekening Belanja',
            'description'     => 'Uraian & Spesifikasi Belanja',
            'volume'          => 'Volume Belanja',
            'satuan'          => 'Satuan Belanja',
            'harga_satuan'    => 'Harga Satuan (Rp)',
            'nominal_request' => 'Total Usulan Belanja (Rp)',
            'total_usulan'    => 'Total Usulan Belanja (Rp)',
            'pagu'            => 'Nominal Pagu Final (Rp)',
            'nominal_pagu'    => 'Nominal Pagu Final (Rp)',
            'pagu_final'      => 'Nominal Pagu Final (Rp)',
            'creator'         => 'Operator Penyusun',
            'operator'        => 'Operator Penyusun',
            'unit'            => 'Unit Kerja',
        ];

        $columnName = $columnLabels[$sortBy] ?? 'Nomor Rekening Belanja';

        return "{$columnName} ({$dirLabel})";
    }
}
