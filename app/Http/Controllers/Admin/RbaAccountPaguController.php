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
        $accountCodes = AccountCode::all();

        // Load existing pagu for this header
        $existingPagus = RbaAccountPagu::where('rba_header_id', $header->id)
            ->get()
            ->keyBy('account_code_id');

        // Calculate total requested per account code for this header
        // We sum all RbaDetail nominal_request related to this header's submissions
        $totalRequests = RbaDetail::whereHas('submission', function ($query) use ($header) {
            $query->where('rba_header_id', $header->id);
        })
            ->selectRaw('account_code_id, SUM(nominal_request) as total')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        return view('admin.headers.pagu', compact('header', 'accountCodes', 'existingPagus', 'totalRequests'));
    }

    public function store(Request $request, RbaHeader $header)
    {
        // Support saving per individual account code
        if ($request->has('account_code_id')) {
            $validated = $request->validate([
                'account_code_id' => 'required|exists:account_codes,id',
                'nominal_pagu' => 'required|numeric|min:0',
            ]);

            RbaAccountPagu::updateOrCreate(
                [
                    'rba_header_id' => $header->id,
                    'account_code_id' => $validated['account_code_id'],
                ],
                [
                    'nominal_pagu' => $validated['nominal_pagu'],
                ]
            );

            $accountCode = AccountCode::find($validated['account_code_id']);
            $formattedNominal = number_format($validated['nominal_pagu'], 0, ',', '.');
            return redirect()->back()->with('success', "Pagu untuk rekening {$accountCode->code} ({$accountCode->name}) berhasil ditetapkan sebesar Rp {$formattedNominal}.");
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

        return redirect()->back()->with('success', "Penetapan pagu untuk rekening {$accountCode->code} ({$accountCode->name}) berhasil dibatalkan.");
    }
}
