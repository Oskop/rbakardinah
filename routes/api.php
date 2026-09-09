<?php

use App\Http\Controllers\Api\V1\PaguApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SIPAKAR RSUD Kardinah
|--------------------------------------------------------------------------
|
| Rute-rute API untuk integrasi sistem dan aplikasi pihak ketiga.
| Dilindungi oleh middleware otentikasi API Key dan rate limiting.
|
*/

Route::prefix('v1')->group(function () {
    // Health check endpoint (publik untuk verifikasi konektivitas)
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'service' => 'SIPAKAR RSUD Kardinah REST API',
            'version' => '1.0.0',
            'status' => 'OK',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Protected API Endpoints (Membutuhkan API Key valid & dicatat log-nya)
    Route::middleware(['api.log', 'api.key', 'throttle:60,1'])->group(function () {
        Route::get('/pagu', [PaguApiController::class, 'index'])->name('api.v1.pagu.index');
    });
});
