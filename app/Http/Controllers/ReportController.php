<?php

namespace App\Http\Controllers;

use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Display the centralized RBA Reports & Printing Center.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;

        $headers = collect();
        $submissions = collect();
        $units = collect();
        $allOperators = collect();
        $selectedPeriodId = null;

        if ($role === 'Administrator') {
            $headers = RbaHeader::with(['period', 'submissions.unit'])
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->get();

            $units = Unit::where('is_active', true)->orderBy('name')->get();
            $allOperators = User::where('role', 'Operator')
                ->where('is_active', true)
                ->with('unit')
                ->orderBy('name')
                ->get();

            $selectedPeriodId = $request->get('header_id')
                ?? ($headers->firstWhere('status_global', 'open')?->id ?? $headers->first()?->id);
        } elseif ($role === 'Supervisor') {
            if ($user->unit_id) {
                $submissions = RbaSubmission::where('unit_id', $user->unit_id)
                    ->with(['header.period', 'unit', 'details'])
                    ->orderByDesc('id')
                    ->get();

                $allOperators = User::where('unit_id', $user->unit_id)
                    ->where('role', 'Operator')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }

            $selectedPeriodId = $request->get('submission_id') ?? $submissions->first()?->id;
        } elseif ($role === 'Operator') {
            if ($user->unit_id) {
                $submissions = RbaSubmission::where('unit_id', $user->unit_id)
                    ->with(['header.period', 'unit', 'details'])
                    ->orderByDesc('id')
                    ->get();
            }

            $selectedPeriodId = $request->get('submission_id') ?? $submissions->first()?->id;
        }

        return view('reports.index', compact(
            'role',
            'user',
            'headers',
            'submissions',
            'units',
            'allOperators',
            'selectedPeriodId'
        ));
    }
}
