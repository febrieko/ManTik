<?php

namespace App\Services;

use App\Models\Router;
use App\Models\RouterUptimeLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RouterMonitorService
{
    /**
     * Check single router status and record uptime/downtime log
     */
    public function checkRouter(Router $router, int $socketTimeout = 3): array
    {
        $now = now();
        $previousStatus = $router->status;
        $startMicro = microtime(true);
        $responseTimeMs = null;
        $isOnline = false;
        $reason = null;

        // 1. Check socket connection to host and port
        $host = trim($router->host);
        $port = (int) ($router->port ?: 8728);
        $errCode = 0;
        $errMsg = '';

        $fp = @fsockopen($host, $port, $errCode, $errMsg, $socketTimeout);

        if ($fp) {
            $endMicro = microtime(true);
            $responseTimeMs = (int) round(($endMicro - $startMicro) * 1000);
            fclose($fp);

            // 2. Optional: Verify MikroTik API query if needed
            try {
                $service = new MikrotikService($router);
                if ($service->connect()) {
                    $isOnline = true;
                    $reason = "Port {$port} aktif & API MikroTik terhubung ({$responseTimeMs}ms)";
                } else {
                    // Socket reachable, but API auth failed
                    $isOnline = false;
                    $reason = "Port {$port} terbuka, tetapi API MikroTik gagal: " . ($service->lastError ?: 'Gagal autentikasi');
                }
            } catch (\Exception $e) {
                // If API throws an exception
                $isOnline = false;
                $reason = "Port {$port} terbuka, tetapi error API: " . $e->getMessage();
            }
        } else {
            $isOnline = false;
            $reason = "Tidak dapat terhubung ke {$host}:{$port} (" . ($errMsg ?: "Timeout {$socketTimeout}s") . ")";
        }

        $newStatus = $isOnline ? 'online' : 'offline';

        // 3. Update or transition RouterUptimeLog
        $this->recordStatusTransition($router, $newStatus, $responseTimeMs, $reason, $now);

        // 4. Update router model
        $router->update([
            'status' => $newStatus,
            'last_checked_at' => $now,
        ]);

        return [
            'router_id'         => $router->id,
            'router_name'       => $router->name,
            'host'              => $router->host,
            'port'              => $router->port,
            'status'            => $newStatus,
            'previous_status'   => $previousStatus,
            'status_changed'    => $previousStatus !== $newStatus,
            'response_time_ms'  => $responseTimeMs,
            'reason'            => $reason,
            'checked_at'        => $now->toDateTimeString(),
            'duration'          => $router->fresh()->getCurrentStatusDurationFormatted(),
        ];
    }

    /**
     * Check all active routers
     */
    public function checkAll(?Collection $routers = null): array
    {
        if ($routers === null) {
            $routers = Router::where('is_active', true)->get();
        }

        $results = [];
        $totalOnline = 0;
        $totalOffline = 0;

        foreach ($routers as $router) {
            try {
                $res = $this->checkRouter($router);
                $results[] = $res;
                if ($res['status'] === 'online') {
                    $totalOnline++;
                } else {
                    $totalOffline++;
                }
            } catch (\Exception $e) {
                Log::error("Error checking router {$router->name} (ID: {$router->id}): " . $e->getMessage());
                $results[] = [
                    'router_id'        => $router->id,
                    'router_name'      => $router->name,
                    'status'           => 'offline',
                    'previous_status'  => $router->status,
                    'status_changed'   => $router->status !== 'offline',
                    'response_time_ms' => null,
                    'reason'           => 'Pemeriksaan gagal: ' . $e->getMessage(),
                    'checked_at'       => now()->toDateTimeString(),
                    'duration'         => '-',
                ];
                $totalOffline++;
            }
        }

        return [
            'checked_at' => now()->toDateTimeString(),
            'total'      => count($results),
            'online'     => $totalOnline,
            'offline'    => $totalOffline,
            'results'    => $results,
        ];
    }

    /**
     * Manage uptime log transitions
     */
    protected function recordStatusTransition(
        Router $router,
        string $newStatus,
        ?int $responseTimeMs,
        ?string $reason,
        \Carbon\Carbon $now
    ): void {
        // Find latest active log (ended_at is null)
        $activeLog = RouterUptimeLog::where('router_id', $router->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($activeLog) {
            if ($activeLog->status !== $newStatus) {
                $previousStatus = $activeLog->status;
                $previousDurationFormatted = $activeLog->duration_formatted;

                // Status changed! Close previous log
                $activeLog->ended_at = $now;
                $activeLog->duration_seconds = max(1, (int) ($now->timestamp - $activeLog->started_at->timestamp));
                $activeLog->save();

                // Close any other dangling unclosed logs for this router
                RouterUptimeLog::where('router_id', $router->id)
                    ->whereNull('ended_at')
                    ->where('id', '!=', $activeLog->id)
                    ->update([
                        'ended_at' => $now,
                        'duration_seconds' => max(1, (int) ($now->timestamp - $activeLog->started_at->timestamp)),
                    ]);

                // Open new log
                RouterUptimeLog::create([
                    'router_id'        => $router->id,
                    'status'           => $newStatus,
                    'started_at'       => $now,
                    'ended_at'         => null,
                    'duration_seconds' => null,
                    'response_time_ms' => $responseTimeMs,
                    'reason'           => $reason,
                ]);

                // Send notification to Super Admin, Admin, and Teknisi
                try {
                    app(NotificationService::class)->sendRouterStatusAlert(
                        $router,
                        $newStatus,
                        $previousStatus,
                        $reason,
                        $previousDurationFormatted,
                        $responseTimeMs
                    );
                } catch (\Exception $e) {
                    Log::error("[RouterMonitorService] Gagal kirim notifikasi router alert: " . $e->getMessage());
                }
            } else {
                // Same status: update ongoing duration & response time
                $activeLog->duration_seconds = max(1, (int) ($now->timestamp - $activeLog->started_at->timestamp));
                if ($responseTimeMs !== null) {
                    $activeLog->response_time_ms = $responseTimeMs;
                }
                if ($reason) {
                    $activeLog->reason = $reason;
                }
                $activeLog->save();
            }
        } else {
            // No active log exists, create initial log
            RouterUptimeLog::create([
                'router_id'        => $router->id,
                'status'           => $newStatus,
                'started_at'       => $now,
                'ended_at'         => null,
                'duration_seconds' => null,
                'response_time_ms' => $responseTimeMs,
                'reason'           => $reason,
            ]);
        }
    }
}
