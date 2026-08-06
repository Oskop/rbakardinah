<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\RbaHeader;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $headers = RbaHeader::with([
            'period',
            'submissions.details.creator.unit',
            'accountPagus'
        ])
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();

        $rbaData = $headers->map(function ($header) {
            $totalPaguGlobal = (float) $header->accountPagus->sum('nominal_pagu');

            $allDetails = $header->submissions->flatMap->details;
            $totalUsulanGlobal = (float) $allDetails->sum('nominal_request');

            $accountPagus = $header->accountPagus->keyBy('account_code_id');

            // Group all details in this header by their creator (Operator)
            $detailsByCreator = $allDetails->groupBy('created_by');

            $operators = $detailsByCreator->map(function ($detailsGroup, $creatorId) use ($accountPagus, $totalUsulanGlobal) {
                $creator = $detailsGroup->first()->creator;
                $totalUsulanOperator = (float) $detailsGroup->sum('nominal_request');

                $uniqueAccountCodeIds = $detailsGroup->pluck('account_code_id')->unique();
                $totalPaguOperator = (float) $uniqueAccountCodeIds->sum(function ($acId) use ($accountPagus) {
                    return isset($accountPagus[$acId]) ? (float) $accountPagus[$acId]->nominal_pagu : 0;
                });

                $percentageShare = $totalUsulanGlobal > 0 
                    ? round(($totalUsulanOperator / $totalUsulanGlobal) * 100, 1) 
                    : 0;

                return [
                    'operator_id' => $creatorId,
                    'operator_name' => $creator ? $creator->name : ('Operator #' . $creatorId),
                    'unit_name' => ($creator && $creator->unit) ? $creator->unit->name : 'Tanpa Unit',
                    'unit_code' => ($creator && $creator->unit) ? $creator->unit->code : '',
                    'total_usulan' => $totalUsulanOperator,
                    'total_pagu' => $totalPaguOperator,
                    'item_count' => $detailsGroup->count(),
                    'percentage_share' => $percentageShare,
                ];
            })->values();

            return [
                'id' => $header->id,
                'year' => $header->year,
                'period_name' => $header->period->name ?? 'RBA',
                'status_global' => $header->status_global,
                'total_usulan_global' => $totalUsulanGlobal,
                'total_pagu_global' => $totalPaguGlobal,
                'operators' => $operators,
                'unit_count' => $header->submissions->count(),
            ];
        });

        return view('supervisor.dashboard', compact('rbaData'));
    }
}
