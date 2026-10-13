<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficHourlyStat extends Model
{
    protected $table = 'traffic_hourly_stats';

    protected $fillable = [
        'router_id', 'interface', 'hour_at',
        'avg_rx_bps', 'max_rx_bps', 'avg_tx_bps', 'max_tx_bps', 'sample_count',
    ];

    protected $casts = [
        'hour_at' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
