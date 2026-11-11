<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicVoucherOrder extends Model
{
    protected $fillable = [
        'reference_code',
        'package_id',
        'router_id',
        'voucher_id',
        'buyer_name',
        'buyer_phone',
        'amount',
        'fee',
        'total_amount',
        'gateway',
        'payment_method',
        'gateway_reference',
        'payment_url',
        'qr_url',
        'status',
        'paid_at',
        'expired_at',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'fee'          => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at'      => 'datetime',
        'expired_at'   => 'datetime',
        'payload'      => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->reference_code)) {
                $order->reference_code = 'VCR-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
            if (is_null($order->total_amount)) {
                $order->total_amount = (float) $order->amount + (float) ($order->fee ?? 0);
            }
        });
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function voucher()
    {
        return $this->belongsTo(HotspotVoucher::class, 'voucher_id');
    }
}

