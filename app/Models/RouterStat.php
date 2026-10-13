<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouterStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'cpu_load', 'memory_used', 'memory_total',
        'hdd_used', 'hdd_total', 'uptime', 'version',
        'active_connections', 'recorded_at',
    ];

    protected $casts = [
        'cpu_load' => 'float',
        'memory_used' => 'float',
        'memory_total' => 'float',
        'hdd_used' => 'float',
        'hdd_total' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function getMemoryPercentAttribute(): float
    {
        if ($this->memory_total <= 0) return 0;
        return round(($this->memory_used / $this->memory_total) * 100, 2);
    }

    public function getHddPercentAttribute(): float
    {
        if ($this->hdd_total <= 0) return 0;
        return round(($this->hdd_used / $this->hdd_total) * 100, 2);
    }
}
