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
            $routeRouter = $request->route('router');
            $routerId = is_object($routeRouter) ? $routeRouter->id : (is_numeric($routeRouter) ? (int) $routeRouter : null);

            $routerId = $routerId
                ?? $request->route('pppoe')?->router_id
                ?? $request->route('pppoeUser')?->router_id
                ?? $request->route('hotspot')?->router_id
                ?? $request->route('queue')?->router_id
                ?? ($request->filled('router_id') ? (int) $request->input('router_id') : null);

            if ($routerId && !$user->canAccessRouter((int) $routerId)) {
                abort(403, 'Anda tidak memiliki akses ke router ini.');
            }
        }

        return $next($request);
    }
}
