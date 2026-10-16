<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Models\TrafficLog;
use App\Models\RouterStat;
use App\Services\MikrotikService;
use Illuminate\Support\Facades\Log;

class RecordMikrotikStats extends Command
{
    protected $signature = 'mantik:record-stats';
    protected $description = 'Fetch and record traffic and resource stats from all online routers';

    public function handle()
    {
        $monitorService = app(\App\Services\RouterMonitorService::class);

        // 1. Periksa router yang saat ini berstatus offline / non-online untuk mendeteksi apakah sudah kembali online
        $offlineRouters = Router::where('is_active', true)->where('status', '!=', 'online')->get();
        foreach ($offlineRouters as $offlineRouter) {
            $monitorService->checkRouter($offlineRouter);
        }

        // 2. Ambil semua router yang online (termasuk yang baru saja pulih menjadi online)
        $routers = Router::where('is_active', true)->where('status', 'online')->get();
        $now = now();

        foreach ($routers as $router) {
            try {
                $service = new MikrotikService($router);
                
                // 1. Record Router Resources (CPU & RAM)
                $sysResource = $service->getSystemResource();
                if (!empty($sysResource)) {
                    $totalMem = $sysResource['total-memory'] ?? 0;
                    $freeMem = $sysResource['free-memory'] ?? 0;
                    $usedMem = max(0, $totalMem - $freeMem);
                    $memPercent = $totalMem > 0 ? round(($usedMem / $totalMem) * 100, 2) : 0;
                    
                    RouterStat::create([
                        'router_id' => $router->id,
                        'cpu_load' => $sysResource['cpu-load'] ?? 0,
                        'memory_used' => $usedMem,
                        'memory_total' => $totalMem,
                        'memory_percent' => $memPercent,
                        'uptime' => $sysResource['uptime'] ?? '',
                        'version' => $sysResource['version'] ?? '',
                        'recorded_at' => $now,
                    ]);
                }

                // 2. Record Traffic Stats
                $interfaces = $service->getInterfaceStats();

                foreach ($interfaces as $iface) {
                    if (!isset($iface['name']) || empty($iface['name'])) continue;

                    // Calculate bps if previous record exists
                    $lastLog = TrafficLog::where('router_id', $router->id)
                        ->where('interface', $iface['name'])
                        ->orderByDesc('recorded_at')
                        ->first();

                    $currentRxBytes = $iface['rx-byte'] ?? 0;
                    $currentTxBytes = $iface['tx-byte'] ?? 0;
                    
                    $rxBps = 0;
                    $txBps = 0;

                    if ($lastLog) {
                        $timeDiff = abs($now->diffInSeconds($lastLog->recorded_at));
                        if ($timeDiff > 0) {
                            $rxDiff = max(0, $currentRxBytes - $lastLog->rx_bytes);
                            $txDiff = max(0, $currentTxBytes - $lastLog->tx_bytes);
                            
                            // Convert bytes to bits per second
                            $rxBps = ($rxDiff * 8) / $timeDiff;
                            $txBps = ($txDiff * 8) / $timeDiff;
                        }
                    }

                    TrafficLog::create([
                        'router_id' => $router->id,
                        'interface' => $iface['name'],
                        'rx_bytes' => $currentRxBytes,
                        'tx_bytes' => $currentTxBytes,
                        'rx_bps' => $rxBps,
                        'tx_bps' => $txBps,
                        'recorded_at' => $now,
                    ]);
                }
                $this->info("Recorded stats for router: {$router->name}");
            } catch (\Exception $e) {
                Log::error("Failed to record stats for router {$router->name}: " . $e->getMessage());
                // Mark offline and update uptime log if connection drops
                app(\App\Services\RouterMonitorService::class)->checkRouter($router);
            }
        }
        
        $this->info('Stats recording completed.');
    }
}
