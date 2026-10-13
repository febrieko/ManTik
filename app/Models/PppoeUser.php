<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PppoeUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'router_id', 'customer_id', 'package_id', 'username', 'password',
        'profile', 'service', 'local_address', 'remote_address',
        'is_disabled', 'comment', 'mikrotik_id', 'expires_at',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function isActive(): bool
    {
        return !$this->is_disabled;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
