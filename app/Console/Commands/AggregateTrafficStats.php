<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Models\TrafficLog;
use App\Models\TrafficHourlyStat;
use App\Models\TrafficDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateTrafficStats extends Command
{
    protected $signature = 'mantik:aggregate-traffic
                            {--mode=hourly : Mode agregasi: hourly atau daily}
                            {--date= : Tanggal target (format Y-m-d), default: kemarin}';

    protected $description = 'Aggregate traffic_logs data into hourly/daily summary tables for MRTG-style graphs';

    public function handle()
    {
        $mode = $this->option('mode');

        if ($mode === 'hourly') {
            $this->aggregateHourly();
        } elseif ($mode === 'daily') {
            $this->aggregateDaily();
        } else {
            $this->error("Mode tidak valid: {$mode}. Gunakan 'hourly' atau 'daily'.");
            return 1;
        }

        return 0;
    }

    /**
     * Aggregate raw traffic_logs data into hourly summaries.
     * Runs every hour — aggregates the PREVIOUS hour.
     */
    private function aggregateHourly()
    {
        // Aggregasi jam lalu
        $targetHour = Carbon::now()->subHour()->startOfHour();

        $this->info("Aggregating hourly stats for: {$targetHour->toDateTimeString()}");

        // Dapatkan semua kombinasi router+interface yang punya data di jam itu
        $combinations = TrafficLog::whereBetween('recorded_at', [
            $targetHour,
            $targetHour->copy()->endOfHour(),
        ])
        ->select('router_id', 'interface')
        ->groupBy('router_id', 'interface')
        ->get();

        foreach ($combinations as $combo) {
            $stats = TrafficLog::where('router_id', $combo->router_id)
                ->where('interface', $combo->interface)
                ->whereBetween('recorded_at', [$targetHour, $targetHour->copy()->endOfHour()])
                ->selectRaw('
                    AVG(rx_bps) as avg_rx_bps,
                    MAX(rx_bps) as max_rx_bps,
                    AVG(tx_bps) as avg_tx_bps,
                    MAX(tx_bps) as max_tx_bps,
                    COUNT(*) as sample_count
                ')
                ->first();

            if ($stats && $stats->sample_count > 0) {
                TrafficHourlyStat::updateOrCreate(
                    [
                        'router_id' => $combo->router_id,
                        'interface' => $combo->interface,
                        'hour_at'   => $targetHour,
                    ],
                    [
                        'avg_rx_bps'   => (int) $stats->avg_rx_bps,
                        'max_rx_bps'   => (int) $stats->max_rx_bps,
                        'avg_tx_bps'   => (int) $stats->avg_tx_bps,
                        'max_tx_bps'   => (int) $stats->max_tx_bps,
                        'sample_count' => (int) $stats->sample_count,
                    ]
                );

                $this->line("  ✓ Router #{$combo->router_id} | {$combo->interface} | samples: {$stats->sample_count}");
            }
        }

        $this->info("Hourly aggregation done. {$combinations->count()} interface(s) processed.");
    }

    /**
     * Aggregate hourly stats into daily summaries.
     * Runs every midnight — aggregates the PREVIOUS day.
     */
    private function aggregateDaily()
    {
        $dateStr = $this->option('date');
        $targetDate = $dateStr
            ? Carbon::parse($dateStr)->startOfDay()
            : Carbon::yesterday()->startOfDay();

        $this->info("Aggregating daily stats for: {$targetDate->toDateString()}");

        $combinations = TrafficHourlyStat::whereDate('hour_at', $targetDate)
            ->select('router_id', 'interface')
            ->groupBy('router_id', 'interface')
            ->get();

        foreach ($combinations as $combo) {
            $stats = TrafficHourlyStat::where('router_id', $combo->router_id)
                ->where('interface', $combo->interface)
                ->whereDate('hour_at', $targetDate)
                ->selectRaw('
                    AVG(avg_rx_bps) as avg_rx_bps,
                    MAX(max_rx_bps) as max_rx_bps,
                    AVG(avg_tx_bps) as avg_tx_bps,
                    MAX(max_tx_bps) as max_tx_bps,
                    SUM(sample_count) as sample_count
                ')
                ->first();

            if ($stats && $stats->sample_count > 0) {
                TrafficDailyStat::updateOrCreate(
                    [
                        'router_id' => $combo->router_id,
                        'interface' => $combo->interface,
                        'date_at'   => $targetDate->toDateString(),
                    ],
                    [
                        'avg_rx_bps'   => (int) $stats->avg_rx_bps,
                        'max_rx_bps'   => (int) $stats->max_rx_bps,
                        'avg_tx_bps'   => (int) $stats->avg_tx_bps,
                        'max_tx_bps'   => (int) $stats->max_tx_bps,
                        'sample_count' => (int) $stats->sample_count,
                    ]
                );

                $this->line("  ✓ Router #{$combo->router_id} | {$combo->interface}");
            }
        }

        $this->info("Daily aggregation done. {$combinations->count()} interface(s) processed.");
    }
}
