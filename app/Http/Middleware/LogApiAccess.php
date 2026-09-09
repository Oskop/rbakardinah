<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiAccess
{
    /**
     * Handle an incoming request and log its metadata.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $client = $request->attributes->get('api_client');
        $statusCode = $response->getStatusCode();

        $errorMessage = null;
        if ($statusCode >= 400) {
            if (method_exists($response, 'getData')) {
                $data = $response->getData(true);
                $errorMessage = is_array($data) ? ($data['message'] ?? null) : null;
            } else {
                $content = json_decode($response->getContent(), true);
                if (is_array($content) && isset($content['message'])) {
                    $errorMessage = $content['message'];
                }
            }
        }

        try {
            ApiAccessLog::create([
                'api_client_id' => $client?->id,
                'client_name' => $client?->name ?? 'Klien Tidak Terautentikasi',
                'endpoint' => '/' . ltrim($request->path(), '/'),
                'method' => $request->method(),
                'status_code' => $statusCode,
                'query_params' => !empty($request->query()) ? $request->query() : null,
                'response_time_ms' => $duration,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 500) : null,
                'error_message' => $errorMessage,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Jangan menghentikan respon API jika terjadi kegagalan logging
            report($e);
        }

        return $response;
    }
}
