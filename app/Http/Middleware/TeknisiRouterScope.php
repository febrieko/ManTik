<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeknisiRouterScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Super Admin dan Admin: bebas akses semua
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $next($request);
        }

        // Teknisi: validasi akses ke router
        if ($user->isTeknisi()) {
            // Cari router_id dari request (parameter route atau query string)
            $routerId = $request->route('router')?->id
                     ?? $request->route('pppoe')?->router_id
                     ?? $request->route('hotspot')?->router_id
                     ?? $request->input('router_id');

            if ($routerId && !$user->canAccessRouter($routerId)) {
                abort(403, 'Anda tidak memiliki akses ke router ini.');
            }
        }

        return $next($request);
    }
}
