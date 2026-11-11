<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerBalanceTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'category',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'topup'           => 'Top-up Saldo',
            'voucher_wa'      => 'Beli Voucher (WA)',
            'voucher_portal'  => 'Beli Voucher (Portal)',
            'billing_payment' => 'Bayar Tagihan',
            'refund'          => 'Pengembalian Dana',
            'adjustment'      => 'Koreksi Admin',
            default           => ucfirst(str_replace('_', ' ', (string) $this->category)),
        };
    }
}

