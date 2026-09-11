<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class OrgChartController extends Controller
{
    /**
     * Display the hospital organizational chart (Bagan Struktur Organisasi).
     */
    public function index()
    {
        // Level 1: Direksi (Administrator Eselon II)
        $direksi = User::where('role', 'Administrator')
            ->where('is_active', true)
            ->orderByRaw("CASE 
                WHEN name LIKE '%Direktur%' AND name NOT LIKE '%Wadir%' THEN 1 
                WHEN name LIKE '%Wadir Pelayanan%' THEN 2 
                WHEN name LIKE '%Wadir%' THEN 3 
                ELSE 4 END")
            ->get();

        // Level 2 & 3: Units (Eselon III) with their SubUnits (Eselon IV/Non) and assigned Users
        $units = Unit::with([
            'subUnits' => function ($query) {
                $query->where('is_active', true)
                      ->with(['users' => function ($qu) {
                          $qu->where('is_active', true)->orderBy('name');
                      }])
                      ->orderBy('type')
                      ->orderBy('name');
            },
            'users' => function ($query) {
                $query->where('is_active', true)->orderBy('role')->orderBy('name');
            }
        ])
        ->where('is_active', true)
        ->orderBy('id')
        ->get();

        // Summary Statistics
        $stats = [
            'total_units' => Unit::where('is_active', true)->count(),
            'total_sub_units' => SubUnit::where('is_active', true)->count(),
            'total_supervisors' => User::where('role', 'Supervisor')->where('is_active', true)->count(),
            'total_operators' => User::where('role', 'Operator')->where('is_active', true)->count(),
            'total_sso_users' => User::where('auth_provider', 'simrs_oidc')->count(),
            'total_users' => User::where('is_active', true)->count(),
        ];

        return view('admin.org_chart.index', compact('direksi', 'units', 'stats'));
    }
}
