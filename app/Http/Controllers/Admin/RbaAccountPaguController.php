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
        $validated = $request->validate([
            'pagus' => 'required|array',
            'pagus.*' => 'nullable|numeric|min:0',
        ]);

        \DB::transaction(function () use ($validated, $header) {
            foreach ($validated['pagus'] as $accountId => $nominal) {
                if ($nominal === null || $nominal === '') {
                    // If nominal is null, we can either delete or skip
                    // Let's delete to represent unassigned pagu
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
}
