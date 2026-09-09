<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Otentikasi gagal. API Key tidak ditemukan pada header Authorization: Bearer <token> atau X-API-KEY.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $client = ApiClient::findByPlainToken($token);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Otentikasi gagal. API Key tidak valid atau tidak terdaftar.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Akun API Key ini berstatus non-aktif. Silakan hubungi Administrator RSUD.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($client->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. API Key ini telah kedaluwarsa sejak ' . $client->expires_at->format('d-m-Y H:i') . ' WIB.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Catat penggunaan terakhir
        $client->recordUsage($request->ip());

        // Lampirkan data client ke request attributes
        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    /**
     * Extract token from Authorization Bearer header or X-API-KEY header.
     */
    protected function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Bearer ')) {
            return trim(substr($authHeader, 7));
        }

        $xApiKey = $request->header('X-API-KEY');
        if (!empty($xApiKey)) {
            return trim($xApiKey);
        }

        // Juga periksa query parameter token sebagai fallback opsional jika diizinkan
        if ($request->has('api_key')) {
            return trim($request->query('api_key'));
        }

        return null;
    }
}
