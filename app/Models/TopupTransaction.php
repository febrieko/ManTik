<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TopupTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'reference_code',
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
        'notes',
        'created_by',
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

        static::creating(function ($topup) {
            if (empty($topup->reference_code)) {
                $topup->reference_code = 'TOPUP-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
            if (is_null($topup->total_amount)) {
                $topup->total_amount = (float) $topup->amount + (float) ($topup->fee ?? 0);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'admin_cash'    => 'Tunai (Admin)',
            'bank_transfer' => 'Transfer Bank (Manual)',
            'qris'          => 'QRIS',
            default         => strtoupper(str_replace('_', ' ', (string) ($this->payment_method ?: $this->gateway))),
        };
    }
}

