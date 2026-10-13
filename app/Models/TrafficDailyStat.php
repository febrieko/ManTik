<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficDailyStat extends Model
{
    protected $table = 'traffic_daily_stats';

    protected $fillable = [
        'router_id', 'interface', 'date_at',
        'avg_rx_bps', 'max_rx_bps', 'avg_tx_bps', 'max_tx_bps', 'sample_count',
    ];

    protected $casts = [
        'date_at' => 'date',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
