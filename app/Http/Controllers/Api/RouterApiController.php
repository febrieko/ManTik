<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class RouterApiController extends Controller
{
    public function status()
    {
        $routers = Router::all()->map(function ($router) {
            return [
                'id'     => $router->id,
                'name'   => $router->name,
                'status' => $router->status,
            ];
        });

        return response()->json(['routers' => $routers]);
    }

    public function stats(Router $router)
    {
        $stat = $router->latestStat;
        if (!$stat) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'cpu_load'      => $stat->cpu_load,
                'memory_used'   => $stat->memory_used,
                'memory_total'  => $stat->memory_total,
                'memory_percent' => $stat->memory_percent,
                'uptime'        => $stat->uptime,
                'recorded_at'   => $stat->recorded_at,
            ]
        ]);
    }

    public function liveStats(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            $sysResource = $service->getSystemResource();
            if (empty($sysResource)) {
                return response()->json(['success' => false, 'message' => 'No data']);
            }

            $totalMem = $sysResource['total-memory'] ?? 0;
            $freeMem = $sysResource['free-memory'] ?? 0;
            $usedMem = max(0, $totalMem - $freeMem);
            $memPercent = $totalMem > 0 ? round(($usedMem / $totalMem) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'cpu_load'      => (int)($sysResource['cpu-load'] ?? 0),
                    'memory_percent' => (float)$memPercent,
                    'uptime'        => $sysResource['uptime'] ?? '',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function traffic(Router $router, string $interface)
    {
        try {
            $service = new MikrotikService($router);
            $stats = $service->query('/interface/monitor-traffic', ['interface' => $interface, 'once' => '']);
            
            $data = [];
            if (!empty($stats) && isset($stats[0])) {
                $data = [
                    'rx-bps' => (int)($stats[0]['rx-bits-per-second'] ?? 0),
                    'tx-bps' => (int)($stats[0]['tx-bits-per-second'] ?? 0),
                ];
            }
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function liveAllTraffic(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            // Fetch rx/tx bytes for all interfaces
            $interfaces = $service->query('/interface/print', ['.proplist' => 'name,rx-byte,tx-byte,type,running,disabled']);
            
            $data = [];
            foreach ($interfaces as $iface) {
                if (!isset($iface['name'])) continue;
                $data[$iface['name']] = [
                    'rx-byte' => (int)($iface['rx-byte'] ?? 0),
                    'tx-byte' => (int)($iface['tx-byte'] ?? 0),
                ];
            }
            return response()->json(['success' => true, 'timestamp' => microtime(true), 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function pppoeActive(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            // Gunakan .proplist=.id untuk menghindari bug parsing/timeout di data besar
            $data = $service->query('/ppp/active/print', ['.proplist' => '.id']);
            return response()->json(['success' => true, 'data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function hotspotActive(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            // Gunakan .proplist=.id untuk menghindari bug parsing/timeout di data besar
            $data = $service->query('/ip/hotspot/active/print', ['.proplist' => '.id']);
            
            // Cek apakah data yang dikembalikan adalah array error dari MikroTik
            if (isset($data['after']['message'])) {
                return response()->json(['success' => false, 'message' => $data['after']['message'], 'count' => 0]);
            }

            return response()->json(['success' => true, 'data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }
}
