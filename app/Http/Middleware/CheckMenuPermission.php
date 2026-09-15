<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $menuKey  Key permission menu yang didelegasikan (e.g. 'master_barangs')
     */
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if ($user->hasMenuPermission($menuKey)) {
            return $next($request);
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses untuk membuka menu ini.');
    }
}
