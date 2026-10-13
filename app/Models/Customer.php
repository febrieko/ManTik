<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'telegram_chat_id', 'address', 
        'nik', 'status', 'balance', 'profile_photo',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pppoeUsers()
    {
        return $this->hasMany(PppoeUser::class);
    }

    public function hotspotUsers()
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function billings()
    {
        return $this->hasMany(Billing::class);
    }

    public function activeBillings()
    {
        return $this->hasMany(Billing::class)->whereIn('status', ['unpaid', 'partial', 'overdue']);
    }

    public function getActivePackageAttribute()
    {
        return $this->pppoeUsers()->with('package')->first()?->package
            ?? $this->hotspotUsers()->with('package')->first()?->package;
    }
}
