<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficLog extends Model
{
    public $timestamps = false; // We use recorded_at

    protected $fillable = [
        'router_id',
        'interface',
        'rx_bytes',
        'tx_bytes',
        'rx_bps',
        'tx_bps',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'rx_bytes' => 'integer',
        'tx_bytes' => 'integer',
        'rx_bps' => 'integer',
        'tx_bps' => 'integer',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}
