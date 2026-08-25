<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user.unit')->latest();

        // Filter: Search Keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Filter: Role
        if ($request->filled('role')) {
            $query->where('user_role', $request->role);
        }

        // Filter: Action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter: Model Type
        if ($request->filled('model')) {
            $query->where('model_type', 'like', "%" . $request->model . "%");
        }

        // Filter: Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Statistics
        $totalLogs = ActivityLog::count();
        $totalToday = ActivityLog::whereDate('created_at', today())->count();
        $totalCreates = ActivityLog::where('action', 'created')->count();
        $totalUpdates = ActivityLog::where('action', 'updated')->count();
        $totalDeletes = ActivityLog::where('action', 'deleted')->count();

        $roleStats = ActivityLog::selectRaw('user_role, count(*) as total')
            ->groupBy('user_role')
            ->pluck('total', 'user_role');

        $logs = $query->paginate(25)->withQueryString();

        // Available models for filter dropdown
        $availableModels = [
            'RbaDetail' => 'Usulan RBA Detail',
            'RbaSubmission' => 'RBA Submission',
            'RbaAccountPagu' => 'Penetapan Pagu Rekening',
            'RbaHeader' => 'RBA Header',
            'RbaPeriod' => 'Periode RBA',
            'AccountCode' => 'Nomor Rekening',
            'KelompokBelanja' => 'Kelompok Belanja',
            'Unit' => 'Unit Kerja',
            'User' => 'Pengguna (User)',
            'RbaAttachment' => 'Lampiran PDF RBA',
            'RbaSubmissionDocument' => 'Dokumen KAK/RAK/RTP',
            'RbaSubmissionDocumentVersion' => 'Versi Dokumen KAK/RAK/RTP',
        ];

        return view('admin.logs.index', compact(
            'logs',
            'totalLogs',
            'totalToday',
            'totalCreates',
            'totalUpdates',
            'totalDeletes',
            'roleStats',
            'availableModels'
        ));
    }

    public function show(ActivityLog $log)
    {
        if (request()->wantsJson()) {
            return response()->json($log->load('user.unit'));
        }

        return view('admin.logs.show', compact('log'));
    }
}
