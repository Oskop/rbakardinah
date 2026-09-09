<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessLog;
use App\Models\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiAccessLogController extends Controller
{
    /**
     * Display a listing of API access logs with metrics and filtering.
     */
    public function index(Request $request): View
    {
        $query = ApiAccessLog::with('client')->latest();

        // 1. Filter: Keyword Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhere('endpoint', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        // 2. Filter: Status Code Category
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'success') {
                $query->whereBetween('status_code', [200, 299]);
            } elseif ($status === 'client_error') {
                $query->whereBetween('status_code', [400, 499]);
            } elseif ($status === 'unauthorized') {
                $query->where('status_code', 401);
            } elseif ($status === 'forbidden') {
                $query->where('status_code', 403);
            } elseif ($status === 'throttle') {
                $query->where('status_code', 429);
            } elseif ($status === 'server_error') {
                $query->where('status_code', '>=', 500);
            } elseif (is_numeric($status)) {
                $query->where('status_code', (int) $status);
            }
        }

        // 3. Filter: API Client
        if ($request->filled('client_id')) {
            $query->where('api_client_id', $request->client_id);
        }

        // 4. Filter: Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Statistik Metrik untuk Cards
        $totalLogs = ApiAccessLog::count();
        $totalToday = ApiAccessLog::whereDate('created_at', today())->count();
        $totalSuccess = ApiAccessLog::whereBetween('status_code', [200, 299])->count();
        $totalErrors = ApiAccessLog::where('status_code', '>=', 400)->count();

        $successRate = $totalLogs > 0 ? round(($totalSuccess / $totalLogs) * 100, 1) : 100;
        $avgResponseTime = round((float) ApiAccessLog::avg('response_time_ms'), 1);

        $logs = $query->paginate(25)->withQueryString();
        $apiClients = ApiClient::orderBy('name')->get();

        return view('admin.api_logs.index', compact(
            'logs',
            'apiClients',
            'totalLogs',
            'totalToday',
            'totalSuccess',
            'totalErrors',
            'successRate',
            'avgResponseTime'
        ));
    }

    /**
     * Return detailed JSON for the log entry modal.
     */
    public function show(ApiAccessLog $apiLog): JsonResponse
    {
        return response()->json([
            'id' => $apiLog->id,
            'client_name' => $apiLog->client_name ?: 'Klien Tidak Terautentikasi',
            'api_client' => $apiLog->client,
            'endpoint' => $apiLog->endpoint,
            'method' => $apiLog->method,
            'status_code' => $apiLog->status_code,
            'status_badge' => $apiLog->status_badge_class,
            'response_time_ms' => $apiLog->response_time_ms,
            'query_params' => $apiLog->query_params,
            'ip_address' => $apiLog->ip_address,
            'user_agent' => $apiLog->user_agent,
            'error_message' => $apiLog->error_message,
            'created_at' => $apiLog->created_at?->format('d/m/Y H:i:s') . ' WIB',
            'time_ago' => $apiLog->created_at?->diffForHumans(),
        ]);
    }

    /**
     * Prune logs older than specified days.
     */
    public function prune(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', 30);
        $days = max($days, 1);

        $deletedCount = ApiAccessLog::where('created_at', '<', now()->subDays($days))->delete();

        return back()->with('success', "Berhasil menghapus {$deletedCount} arsip log akses API yang lebih lama dari {$days} hari.");
    }
}
