<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'bandwidth_up', 'bandwidth_down',
        'price', 'duration_days', 'type', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->name);
            }
        });
    }

    public function pppoeUsers()
    {
        return $this->hasMany(PppoeUser::class);
    }

    public function hotspotUsers()
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function hotspotVouchers()
    {
        return $this->hasMany(HotspotVoucher::class);
    }

    public function billings()
    {
        return $this->hasMany(Billing::class);
    }

    public function getBandwidthLabelAttribute(): string
    {
        return "{$this->bandwidth_up}/{$this->bandwidth_down}";
    }
}
