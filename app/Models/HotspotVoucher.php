<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HotspotVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'package_id', 'code', 'profile', 'bandwidth_limit',
        'duration_minutes', 'price', 'is_used', 'used_by', 'used_at', 'expires_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($voucher) {
            if (empty($voucher->code)) {
                $voucher->code = strtoupper(Str::random(8));
            }
        });
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function isAvailable(): bool
    {
        return !$this->is_used && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
