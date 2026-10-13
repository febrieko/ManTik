<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Services\RouterMonitorService;

class CheckRouterStatusCommand extends Command
{
    protected $signature = 'mantik:check-routers {--router= : ID router spesifik yang ingin diperiksa}';
    protected $description = 'Otomatis mendeteksi status online/offline router MikroTik dan mencatat riwayat durasi uptime/downtime';

    public function handle(RouterMonitorService $monitorService)
    {
        $routerId = $this->option('router');

        if ($routerId) {
            $router = Router::find($routerId);
            if (!$router) {
                $this->error("Router dengan ID {$routerId} tidak ditemukan!");
                return 1;
            }

            $this->info("Memeriksa router: {$router->name} ({$router->host}:{$router->port})...");
            $res = $monitorService->checkRouter($router);

            $this->table(
                ['Router', 'Host', 'Status', 'Latensi', 'Durasi Berjalan', 'Keterangan'],
                [[
                    $res['router_name'],
                    $res['host'] . ':' . $res['port'],
                    $res['status'] === 'online' ? '<info>ONLINE</info>' : '<error>OFFLINE</error>',
                    $res['response_time_ms'] ? $res['response_time_ms'] . ' ms' : '-',
                    $res['duration'],
                    $res['reason'],
                ]]
            );

            return 0;
        }

        $routers = Router::where('is_active', true)->get();
        if ($routers->isEmpty()) {
            $this->warn('Tidak ada router aktif yang ditemukan.');
            return 0;
        }

        $this->info("Memeriksa status " . $routers->count() . " router aktif...");

        $summary = $monitorService->checkAll($routers);

        $rows = [];
        foreach ($summary['results'] as $r) {
            $rows[] = [
                $r['router_name'],
                $r['host'] . ':' . $r['port'],
                $r['status'] === 'online' ? '<info>ONLINE</info>' : '<error>OFFLINE</error>',
                $r['response_time_ms'] ? $r['response_time_ms'] . ' ms' : '-',
                $r['duration'],
                $r['reason'],
            ];
        }

        $this->table(
            ['Router', 'Host:Port', 'Status', 'Latensi', 'Durasi Berjalan', 'Keterangan'],
            $rows
        );

        $this->info("Selesai. Online: {$summary['online']} | Offline: {$summary['offline']}");

        return 0;
    }
}
