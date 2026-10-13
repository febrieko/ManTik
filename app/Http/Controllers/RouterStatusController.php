<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\RouterUptimeLog;
use App\Services\RouterMonitorService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RouterStatusController extends Controller
{
    /**
     * Authorize user access: only Super Admin, Admin, and Teknisi
     */
    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isTeknisi())) {
            abort(403, 'Akses ditolak. Halaman ini hanya dapat diakses oleh Super Admin, Admin, dan Teknisi.');
        }
    }

    /**
     * Get accessible routers for current user
     */
    protected function getAccessibleRouters()
    {
        $user = auth()->user();
        if ($user->isTeknisi()) {
            return $user->assignedRouters()->with(['currentUptimeLog', 'latestUptimeLog'])->get();
        }
        return Router::with(['currentUptimeLog', 'latestUptimeLog'])->get();
    }

    /**
     * Display router status and uptime/downtime history logs
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $routers = $this->getAccessibleRouters();
        $routerIds = $routers->pluck('id')->toArray();

        // Summary statistics
        $totalRouters = $routers->count();
        $onlineRouters = $routers->where('status', 'online')->count();
        $offlineRouters = $routers->where('status', 'offline')->count();

        // Calculate average uptime % across routers (last 30 days)
        $avgUptimePercent = 0;
        if ($totalRouters > 0) {
            $sumPercent = 0;
            foreach ($routers as $r) {
                $sumPercent += $r->getUptimePercentage(30);
            }
            $avgUptimePercent = round($sumPercent / $totalRouters, 1);
        }

        // Query Logs with filters
        $logsQuery = RouterUptimeLog::with('router')
            ->whereIn('router_id', $routerIds);

        if ($request->filled('router_id')) {
            $logsQuery->where('router_id', $request->input('router_id'));
        }

        if ($request->filled('status')) {
            $logsQuery->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $logsQuery->where('started_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $logsQuery->where('started_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $logsQuery->where(function ($q) use ($search) {
                $q->whereHas('router', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%")
                       ->orWhere('host', 'like', "%{$search}%");
                })->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $logs = $logsQuery->orderByDesc('started_at')->paginate(20)->withQueryString();

        return view('router_status.index', compact(
            'routers',
            'totalRouters',
            'onlineRouters',
            'offlineRouters',
            'avgUptimePercent',
            'logs'
        ));
    }

    /**
     * Check status for all accessible routers
     */
    public function checkAll(Request $request, RouterMonitorService $monitorService)
    {
        $this->authorizeAccess();

        $routers = $this->getAccessibleRouters()->where('is_active', true);
        $summary = $monitorService->checkAll($routers);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Pemindaian selesai: {$summary['online']} Online, {$summary['offline']} Offline",
                'data'    => $summary,
            ]);
        }

        return redirect()->route('router-status.index')
            ->with('success', "Status semua router berhasil diperbarui! Online: {$summary['online']}, Offline: {$summary['offline']}.");
    }

    /**
     * Check single router status
     */
    public function checkSingle(Request $request, Router $router, RouterMonitorService $monitorService)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        if (!$user->canAccessRouter($router->id)) {
            abort(403, 'Anda tidak memiliki akses ke router ini.');
        }

        $result = $monitorService->checkRouter($router);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Status router {$router->name}: " . strtoupper($result['status']),
                'data'    => $result,
            ]);
        }

        return redirect()->back()
            ->with('success', "Status {$router->name}: " . strtoupper($result['status']) . " ({$result['reason']})");
    }

    /**
     * Detailed view of router uptime and downtime
     */
    public function show(Router $router)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        if (!$user->canAccessRouter($router->id)) {
            abort(403, 'Anda tidak memiliki akses ke router ini.');
        }

        $router->load(['currentUptimeLog', 'latestUptimeLog']);

        // Uptime percentages
        $uptime24h = $router->getUptimePercentage(1);
        $uptime7d  = $router->getUptimePercentage(7);
        $uptime30d = $router->getUptimePercentage(30);

        // Outages count in last 30 days
        $outagesCount30d = RouterUptimeLog::where('router_id', $router->id)
            ->where('status', 'offline')
            ->where('started_at', '>=', now()->subDays(30))
            ->count();

        // Total downtime seconds in last 30 days
        $downtimeLogs = RouterUptimeLog::where('router_id', $router->id)
            ->where('status', 'offline')
            ->where('started_at', '>=', now()->subDays(30))
            ->get();

        $totalDowntimeSeconds = 0;
        foreach ($downtimeLogs as $dlog) {
            $totalDowntimeSeconds += $dlog->duration_seconds_actual;
        }
        $totalDowntimeFormatted = RouterUptimeLog::formatDuration($totalDowntimeSeconds);

        // Paginated logs for this router
        $logs = RouterUptimeLog::where('router_id', $router->id)
            ->orderByDesc('started_at')
            ->paginate(25);

        return view('router_status.show', compact(
            'router',
            'uptime24h',
            'uptime7d',
            'uptime30d',
            'outagesCount30d',
            'totalDowntimeFormatted',
            'logs'
        ));
    }
}
