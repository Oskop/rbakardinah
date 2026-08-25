<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RbaHeader;
use App\Models\AccountCode;
use App\Models\RbaAccountPagu;
use App\Models\RbaDetail;
use Illuminate\Http\Request;

class RbaAccountPaguController extends Controller
{
    public function index(RbaHeader $header)
    {
        $accountCodes = AccountCode::with('kelompokBelanja')->get();

        // Load existing pagu for this header
        $existingPagus = RbaAccountPagu::where('rba_header_id', $header->id)
            ->get()
            ->keyBy('account_code_id');

        // Calculate total requested and validation stats per account code for this header
        $requestStats = RbaDetail::whereHas('submission', function ($query) use ($header) {
            $query->where('rba_header_id', $header->id);
        })
            ->selectRaw('account_code_id, 
                         SUM(nominal_request) as total_nominal, 
                         COUNT(*) as total_count, 
                         SUM(CASE WHEN is_validated = 1 THEN 1 ELSE 0 END) as validated_count,
                         SUM(CASE WHEN is_validated = 0 THEN 1 ELSE 0 END) as unvalidated_count')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        // Load unvalidated details with creator and unit supervisors for UI breakdown
        $unvalidatedDetails = RbaDetail::with(['creator', 'submission.unit'])
            ->whereHas('submission', function ($query) use ($header) {
                $query->where('rba_header_id', $header->id);
            })
            ->where('is_validated', false)
            ->get();

        $unitIds = $unvalidatedDetails->pluck('submission.unit_id')->filter()->unique();
        $supervisorsByUnit = \App\Models\User::whereIn('unit_id', $unitIds)
            ->where('role', 'Supervisor')
            ->get()
            ->groupBy('unit_id');

        $unvalidatedGrouped = $unvalidatedDetails->groupBy('account_code_id');

        return view('admin.headers.pagu', compact(
            'header', 
            'accountCodes', 
            'existingPagus', 
            'requestStats', 
            'unvalidatedGrouped', 
            'supervisorsByUnit'
        ));
    }

    public function store(Request $request, RbaHeader $header)
    {
        // Support saving per individual account code
        if ($request->has('account_code_id')) {
            $validated = $request->validate([
                'account_code_id' => 'required|exists:account_codes,id',
                'nominal_pagu' => 'required|numeric|min:0',
            ]);

            $accountCode = AccountCode::findOrFail($validated['account_code_id']);

            // Validasi: Cek apakah ada rincian belanja pada rekening ini yang belum divalidasi supervisor
            $unvalidatedDetails = RbaDetail::with(['creator', 'submission.unit'])
                ->whereHas('submission', function ($query) use ($header) {
                    $query->where('rba_header_id', $header->id);
                })
                ->where('account_code_id', $accountCode->id)
                ->where('is_validated', false)
                ->get();

            if ($unvalidatedDetails->isNotEmpty()) {
                $unitIds = $unvalidatedDetails->pluck('submission.unit_id')->filter()->unique();
                $supervisorsByUnit = \App\Models\User::whereIn('unit_id', $unitIds)
                    ->where('role', 'Supervisor')
                    ->get()
                    ->groupBy('unit_id');

                $itemsInfo = [];
                foreach ($unvalidatedDetails as $detail) {
                    $opName = $detail->creator?->name ?? 'Operator';
                    $unitName = $detail->submission?->unit?->name ?? '-';
                    $unitId = $detail->submission?->unit_id;
                    
                    $spvs = isset($supervisorsByUnit[$unitId]) 
                        ? $supervisorsByUnit[$unitId]->pluck('name')->toArray() 
                        : [];
                    $spvNames = !empty($spvs) ? implode(', ', $spvs) : 'Supervisor Unit';
                    $nominalFormatted = number_format($detail->nominal_request, 0, ',', '.');

                    $itemsInfo[] = "• Usulan: \"{$detail->description}\" (Rp {$nominalFormatted}) oleh Operator: {$opName} (Unit: {$unitName}) - Wajib divalidasi oleh Supervisor: {$spvNames}";
                }

                $messageList = implode('<br>', $itemsInfo);
                $errorMessage = "Pagu untuk rekening <strong>{$accountCode->code} ({$accountCode->name})</strong> tidak dapat ditetapkan karena terdapat " . count($unvalidatedDetails) . " usulan rincian belanja yang belum divalidasi oleh Supervisor:<br><br>{$messageList}";

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'items' => $itemsInfo,
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', $errorMessage)
                    ->withInput();
            }

            $pagu = RbaAccountPagu::updateOrCreate(
                [
                    'rba_header_id' => $header->id,
                    'account_code_id' => $validated['account_code_id'],
                ],
                [
                    'nominal_pagu' => $validated['nominal_pagu'],
                ]
            );

            $formattedNominal = number_format($validated['nominal_pagu'], 0, ',', '.');
            $successMsg = "Pagu untuk rekening {$accountCode->code} ({$accountCode->name}) berhasil ditetapkan sebesar Rp {$formattedNominal}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'data' => [
                        'account_code_id' => $accountCode->id,
                        'nominal_pagu' => (float)$pagu->nominal_pagu,
                        'nominal_formatted' => $formattedNominal,
                        'updated_at' => $pagu->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                        'destroy_url' => route('admin.headers.pagu.destroy', [$header, $accountCode]),
                        'stats' => $this->getSummaryStats($header),
                    ],
                ]);
            }

            return redirect()->back()->with('success', $successMsg);
        }

        // Backward compatibility for batch array saving if ever called
        $validated = $request->validate([
            'pagus' => 'required|array',
            'pagus.*' => 'nullable|numeric|min:0',
        ]);

        \DB::transaction(function () use ($validated, $header) {
            foreach ($validated['pagus'] as $accountId => $nominal) {
                if ($nominal === null || $nominal === '') {
                    RbaAccountPagu::where('rba_header_id', $header->id)
                        ->where('account_code_id', $accountId)
                        ->delete();
                } else {
                    RbaAccountPagu::updateOrCreate(
                        [
                            'rba_header_id' => $header->id,
                            'account_code_id' => $accountId,
                        ],
                        [
                            'nominal_pagu' => $nominal,
                        ]
                    );
                }
            }
        });

        return redirect()->route('admin.headers.show', $header)->with('success', 'Global Pagu updated successfully.');
    }

    public function destroy(Request $request, RbaHeader $header, AccountCode $accountCode)
    {
        RbaAccountPagu::where('rba_header_id', $header->id)
            ->where('account_code_id', $accountCode->id)
            ->delete();

        $successMsg = "Penetapan pagu untuk rekening {$accountCode->code} ({$accountCode->name}) berhasil dibatalkan.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'data' => [
                    'account_code_id' => $accountCode->id,
                    'stats' => $this->getSummaryStats($header),
                ],
            ]);
        }

        return redirect()->back()->with('success', $successMsg);
    }

    private function getSummaryStats(RbaHeader $header)
    {
        $totalRekening = AccountCode::count();
        $existingPagus = RbaAccountPagu::where('rba_header_id', $header->id)->get();
        $ditetapkanCount = $existingPagus->count();
        $belumDitetapkanCount = $totalRekening - $ditetapkanCount;
        $totalPaguNominal = $existingPagus->sum('nominal_pagu');

        return [
            'total_rekening' => $totalRekening,
            'ditetapkan_count' => $ditetapkanCount,
            'belum_ditetapkan_count' => $belumDitetapkanCount,
            'total_pagu_nominal' => $totalPaguNominal,
            'total_pagu_formatted' => 'Rp ' . number_format($totalPaguNominal, 0, ',', '.'),
        ];
    }
}
