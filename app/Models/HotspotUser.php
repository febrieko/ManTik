<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotspotUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'router_id', 'customer_id', 'package_id', 'username', 'password',
        'profile', 'server', 'mac_address', 'ip_address',
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
}
