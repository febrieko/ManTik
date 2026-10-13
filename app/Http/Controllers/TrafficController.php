<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\TrafficLog;
use App\Models\TrafficHourlyStat;
use App\Models\TrafficDailyStat;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrafficController extends Controller
{
    /**
     * Original traffic monitor page (single interface, live + history)
     */
    public function index(Request $request)
    {
        $routers = Router::where('is_active', true)->get();
        $selectedRouterId = $request->get('router_id');
        $selectedInterface = $request->get('interface');
        $period = $request->get('period', 'live'); // live, 1h, 1d, 1w, 1m, 1y

        $interfaces = [];
        $selectedRouter = null;
        $historicalData = null;

        if ($selectedRouterId) {
            $selectedRouter = Router::find($selectedRouterId);
            if ($selectedRouter && $selectedRouter->status === 'online') {
                try {
                    $service = new MikrotikService($selectedRouter);

                    // Cache interfaces for 5 minutes because querying loaded routers can take >15 seconds
                    $cacheKey = "router_{$selectedRouterId}_interfaces";
                    $interfaces = cache()->remember($cacheKey, now()->addMinutes(5), function() use ($service) {
                        $mtInterfaces = $service->getInterfaces();
                        $ifaceList = [];
                        foreach ($mtInterfaces as $iface) {
                            if (isset($iface['name'])) {
                                $ifaceList[] = [
                                    'name' => $iface['name'],
                                    'type' => $iface['type'] ?? 'unknown',
                                    'running' => isset($iface['running']) && $iface['running'] == 'true',
                                ];
                            }
                        }
                        return $ifaceList;
                    });
                } catch (\Exception $e) {
                    $errorMessage = 'Gagal mengambil interface: ' . $e->getMessage();
                    if (str_contains($e->getMessage(), 'timed out')) {
                        $errorMessage .= '. Router mungkin offline atau port API salah.';
                    }
                    session()->flash('error', $errorMessage);
                    $selectedRouter = null; // Reset to hide interface dropdown
                }

                // If a period is selected that is NOT live, fetch from DB
                if ($selectedRouter && $selectedInterface && $period !== 'live') {
                    $historicalData = $this->getHistoricalData($selectedRouterId, $selectedInterface, $period);
                }
            }
        }

        return view('traffic.index', compact(
            'routers', 'selectedRouter', 'selectedRouterId', 'interfaces', 'selectedInterface', 'period', 'historicalData'
        ));
    }

    /**
     * Manually trigger the record-stats artisan command
     */
    public function recordStats()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('mantik:record-stats');
            return back()->with('success', 'Statistik traffic berhasil direkam secara manual.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal merekam statistik: ' . $e->getMessage());
        }
    }

    /**
     * MRTG-style overview: shows all interfaces of a selected router with mini graphs
     */
    public function mrtg(Request $request)
    {
        $user = auth()->user();
        
        if ($user->isTeknisi()) {
            $routers = $user->assignedRouters;
        } else {
            $routers = Router::where('is_active', true)->get();
        }

        $selectedRouterId = $request->get('router_id', $routers->first()?->id);
        
        if ($user->isTeknisi() && $selectedRouterId && !$user->canAccessRouter($selectedRouterId)) {
            $selectedRouterId = $routers->first()?->id;
        }

        $selectedRouter = null;
        $interfacesWithStats = [];

        if ($selectedRouterId) {
            $selectedRouter = Router::find($selectedRouterId);

            if ($selectedRouter) {
                // Cek apakah ada setting interface untuk router ini
                $hasSettings = \App\Models\RouterInterfaceSetting::where('router_id', $selectedRouter->id)->exists();
                
                if ($hasSettings) {
                    // Gunakan setting yang sudah dikonfigurasi (hanya is_visible = true)
                    // Gunakan cache layer "mrtg_visible_interfaces_{router_id}"
                    $activeInterfaces = \Illuminate\Support\Facades\Cache::remember(
                        "mrtg_visible_interfaces_{$selectedRouter->id}", 
                        now()->addHour(), 
                        function() use ($selectedRouter) {
                            return \App\Models\RouterInterfaceSetting::where('router_id', $selectedRouter->id)
                                ->where('is_visible', true)
                                ->orderBy('sort_order')
                                ->orderBy('interface_name')
                                ->get();
                        }
                    );

                    foreach ($activeInterfaces as $setting) {
                        $ifaceName = $setting->interface_name;
                        
                        $latestLog = TrafficLog::where('router_id', $selectedRouter->id)
                            ->where('interface', $ifaceName)
                            ->orderByDesc('recorded_at')
                            ->first();

                        $stats24h = TrafficLog::where('router_id', $selectedRouter->id)
                            ->where('interface', $ifaceName)
                            ->where('recorded_at', '>=', now()->subDay())
                            ->selectRaw('AVG(rx_bps) as avg_rx, MAX(rx_bps) as max_rx, AVG(tx_bps) as avg_tx, MAX(tx_bps) as max_tx')
                            ->first();

                        $interfacesWithStats[] = [
                            'name'         => $ifaceName,
                            'display_name' => $setting->effective_name, // label kustom
                            'current_rx'   => $latestLog ? $latestLog->rx_bps : 0,
                            'current_tx'   => $latestLog ? $latestLog->tx_bps : 0,
                            'avg_rx'       => $stats24h ? (int) $stats24h->avg_rx : 0,
                            'max_rx'       => $stats24h ? (int) $stats24h->max_rx : 0,
                            'avg_tx'       => $stats24h ? (int) $stats24h->avg_tx : 0,
                            'max_tx'       => $stats24h ? (int) $stats24h->max_tx : 0,
                            'last_seen'    => $latestLog ? $latestLog->recorded_at : null,
                        ];
                    }
                } else {
                    // Fallback: behavior lama jika belum ada setting, ambil semua interface
                    $activeInterfaces = TrafficLog::where('router_id', $selectedRouter->id)
                        ->where('recorded_at', '>=', now()->subDay())
                        ->select('interface')
                        ->groupBy('interface')
                        ->orderBy('interface')
                        ->pluck('interface');

                    // If no historical data yet, try to get from router directly
                    if ($activeInterfaces->isEmpty() && $selectedRouter->status === 'online') {
                        try {
                            $service = new MikrotikService($selectedRouter);
                            $cacheKey = "router_{$selectedRouterId}_interfaces";
                            $rawInterfaces = cache()->remember($cacheKey, now()->addMinutes(5), function() use ($service) {
                                return $service->getInterfaces();
                            });
                            $activeInterfaces = collect($rawInterfaces)
                                ->pluck('name')
                                ->filter();
                        } catch (\Exception $e) {
                            // silently skip
                        }
                    }

                    foreach ($activeInterfaces as $ifaceName) {
                        // Compute mini stats: last recorded bps + max in last 24h
                        $latestLog = TrafficLog::where('router_id', $selectedRouter->id)
                            ->where('interface', $ifaceName)
                            ->orderByDesc('recorded_at')
                            ->first();

                        $stats24h = TrafficLog::where('router_id', $selectedRouter->id)
                            ->where('interface', $ifaceName)
                            ->where('recorded_at', '>=', now()->subDay())
                            ->selectRaw('AVG(rx_bps) as avg_rx, MAX(rx_bps) as max_rx, AVG(tx_bps) as avg_tx, MAX(tx_bps) as max_tx')
                            ->first();

                        $interfacesWithStats[] = [
                            'name'         => $ifaceName,
                            'display_name' => $ifaceName,
                            'current_rx'   => $latestLog ? $latestLog->rx_bps : 0,
                            'current_tx'   => $latestLog ? $latestLog->tx_bps : 0,
                            'avg_rx'       => $stats24h ? (int) $stats24h->avg_rx : 0,
                            'max_rx'       => $stats24h ? (int) $stats24h->max_rx : 0,
                            'avg_tx'       => $stats24h ? (int) $stats24h->avg_tx : 0,
                            'max_tx'       => $stats24h ? (int) $stats24h->max_tx : 0,
                            'last_seen'    => $latestLog ? $latestLog->recorded_at : null,
                        ];
                    }
                }
            }
        }

        return view('traffic.mrtg', compact(
            'routers', 'selectedRouter', 'selectedRouterId', 'interfacesWithStats'
        ));
    }

    /**
     * MRTG detail page for a single interface — shows 4 panels: 5min, 1h, 1d, 1w
     */
    public function mrtgDetail(Request $request, Router $router, string $interface)
    {
        // Decode URL-encoded interface name
        $interface = urldecode($interface);

        return view('traffic.mrtg-detail', compact('router', 'interface'));
    }

    /**
     * API: Return aggregated traffic data for MRTG graphs
     * period: 5m (raw last 5min), 1h (raw last 1h), 1d (hourly last 24h), 1w (daily last 7d)
     */
    public function mrtgData(Request $request, Router $router, string $interface)
    {
        $interface = urldecode($interface);
        $period = $request->get('period', '5m');

        $labels = [];
        $rxData = [];
        $txData = [];
        $maxRx = [];
        $maxTx = [];

        switch ($period) {
            case '5m':
                // Raw data from last 5 minutes
                $logs = TrafficLog::where('router_id', $router->id)
                    ->where('interface', $interface)
                    ->orderByDesc('recorded_at')
                    ->take(30)
                    ->get()
                    ->reverse();

                foreach ($logs as $log) {
                    $labels[] = $log->recorded_at->format('H:i:s');
                    $rxData[] = round($log->rx_bps / 1_000_000, 3);
                    $txData[] = round($log->tx_bps / 1_000_000, 3);
                    $maxRx[] = round($log->rx_bps / 1_000_000, 3);
                    $maxTx[] = round($log->tx_bps / 1_000_000, 3);
                }
                break;

            case '1h':
                // Raw data from last 1 hour
                $logs = TrafficLog::where('router_id', $router->id)
                    ->where('interface', $interface)
                    ->where('recorded_at', '>=', now()->subHour())
                    ->orderBy('recorded_at')
                    ->get();

                foreach ($logs as $log) {
                    $labels[] = $log->recorded_at->format('H:i');
                    $rxData[] = round($log->rx_bps / 1_000_000, 3);
                    $txData[] = round($log->tx_bps / 1_000_000, 3);
                    $maxRx[] = round($log->rx_bps / 1_000_000, 3);
                    $maxTx[] = round($log->tx_bps / 1_000_000, 3);
                }
                break;

            case '1d':
                // Hourly aggregated data from last 24 hours
                $hourlyStats = TrafficHourlyStat::where('router_id', $router->id)
                    ->where('interface', $interface)
                    ->where('hour_at', '>=', now()->subDay())
                    ->orderBy('hour_at')
                    ->get();

                if ($hourlyStats->isEmpty()) {
                    // Fallback: aggregate from raw logs grouped by hour
                    $raw = TrafficLog::where('router_id', $router->id)
                        ->where('interface', $interface)
                        ->where('recorded_at', '>=', now()->subDay())
                        ->selectRaw('DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00:00") as hour_at, AVG(rx_bps) as avg_rx, MAX(rx_bps) as max_rx, AVG(tx_bps) as avg_tx, MAX(tx_bps) as max_tx')
                        ->groupByRaw('DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00:00")')
                        ->orderBy('hour_at')
                        ->get();

                    foreach ($raw as $row) {
                        $labels[] = Carbon::parse($row->hour_at)->format('H:i');
                        $rxData[] = round($row->avg_rx / 1_000_000, 3);
                        $txData[] = round($row->avg_tx / 1_000_000, 3);
                        $maxRx[] = round($row->max_rx / 1_000_000, 3);
                        $maxTx[] = round($row->max_tx / 1_000_000, 3);
                    }
                } else {
                    foreach ($hourlyStats as $row) {
                        $labels[] = $row->hour_at->format('H:i');
                        $rxData[] = round($row->avg_rx_bps / 1_000_000, 3);
                        $txData[] = round($row->avg_tx_bps / 1_000_000, 3);
                        $maxRx[] = round($row->max_rx_bps / 1_000_000, 3);
                        $maxTx[] = round($row->max_tx_bps / 1_000_000, 3);
                    }
                }
                break;

            case '1w':
                // Daily aggregated data from last 7 days
                $dailyStats = TrafficDailyStat::where('router_id', $router->id)
                    ->where('interface', $interface)
                    ->where('date_at', '>=', now()->subWeek()->toDateString())
                    ->orderBy('date_at')
                    ->get();

                if ($dailyStats->isEmpty()) {
                    // Fallback: aggregate from raw logs grouped by day
                    $raw = TrafficLog::where('router_id', $router->id)
                        ->where('interface', $interface)
                        ->where('recorded_at', '>=', now()->subWeek())
                        ->selectRaw('DATE(recorded_at) as date_at, AVG(rx_bps) as avg_rx, MAX(rx_bps) as max_rx, AVG(tx_bps) as avg_tx, MAX(tx_bps) as max_tx')
                        ->groupByRaw('DATE(recorded_at)')
                        ->orderBy('date_at')
                        ->get();

                    foreach ($raw as $row) {
                        $labels[] = Carbon::parse($row->date_at)->format('d M');
                        $rxData[] = round($row->avg_rx / 1_000_000, 3);
                        $txData[] = round($row->avg_tx / 1_000_000, 3);
                        $maxRx[] = round($row->max_rx / 1_000_000, 3);
                        $maxTx[] = round($row->max_tx / 1_000_000, 3);
                    }
                } else {
                    foreach ($dailyStats as $row) {
                        $labels[] = Carbon::parse($row->date_at)->format('d M');
                        $rxData[] = round($row->avg_rx_bps / 1_000_000, 3);
                        $txData[] = round($row->avg_tx_bps / 1_000_000, 3);
                        $maxRx[] = round($row->max_rx_bps / 1_000_000, 3);
                        $maxTx[] = round($row->max_tx_bps / 1_000_000, 3);
                    }
                }
                break;
        }

        // Compute summary stats
        $currentRx = !empty($rxData) ? end($rxData) : 0;
        $currentTx = !empty($txData) ? end($txData) : 0;
        $avgRx = !empty($rxData) ? round(array_sum($rxData) / count($rxData), 3) : 0;
        $avgTx = !empty($txData) ? round(array_sum($txData) / count($txData), 3) : 0;
        $peakRx = !empty($maxRx) ? max($maxRx) : 0;
        $peakTx = !empty($maxTx) ? max($maxTx) : 0;

        return response()->json([
            'success' => true,
            'period'  => $period,
            'labels'  => $labels,
            'rx'      => $rxData,
            'tx'      => $txData,
            'max_rx'  => $maxRx,
            'max_tx'  => $maxTx,
            'stats'   => [
                'current_rx' => $currentRx,
                'current_tx' => $currentTx,
                'avg_rx'     => $avgRx,
                'avg_tx'     => $avgTx,
                'peak_rx'    => $peakRx,
                'peak_tx'    => $peakTx,
            ],
        ]);
    }

    private function getHistoricalData($routerId, $interface, $period)
    {
        $query = TrafficLog::where('router_id', $routerId)
                           ->where('interface', $interface)
                           ->orderBy('recorded_at');

        $now = Carbon::now();

        switch ($period) {
            case '1h':
                $query->where('recorded_at', '>=', $now->subHour());
                break;
            case '1d':
                $query->where('recorded_at', '>=', $now->subDay());
                break;
            case '1w':
                $query->where('recorded_at', '>=', $now->subWeek());
                break;
            case '1m':
                $query->where('recorded_at', '>=', $now->subMonth());
                break;
            case '1y':
                $query->where('recorded_at', '>=', $now->subYear());
                break;
        }

        // Limit to prevent browser crash if data is too huge.
        // In a real prod environment, you'd aggregate this using AVG() grouped by HOUR/DAY
        $logs = $query->get();

        $labels = [];
        $rxData = [];
        $txData = [];

        foreach ($logs as $log) {
            // Format labels based on period
            if ($period === '1h' || $period === '1d') {
                $labels[] = $log->recorded_at->format('H:i');
            } else {
                $labels[] = $log->recorded_at->format('d M H:i');
            }
            // Convert bps to Mbps
            $rxData[] = round($log->rx_bps / 1000000, 2);
            $txData[] = round($log->tx_bps / 1000000, 2);
        }

        return [
            'labels' => $labels,
            'rx' => $rxData,
            'tx' => $txData,
        ];
    }
}
