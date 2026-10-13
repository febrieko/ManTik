<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaVoucherRequest extends Model
{
    use HasFactory;

    protected $table = 'wa_voucher_requests';

    protected $fillable = [
        'phone',
        'requester_name',
        'package_code',
        'package_id',
        'voucher_id',
        'voucher_code',
        'status',
        'failure_reason',
        'raw_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Package.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Relasi ke HotspotVoucher.
     */
    public function voucher()
    {
        return $this->belongsTo(HotspotVoucher::class, 'voucher_id');
    }

    /**
     * Scope untuk request yang berhasil.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope untuk request yang gagal.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mendapatkan label status dengan warna badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed'  => 'danger',
            'pending' => 'warning',
            default   => 'secondary',
        };
    }

    /**
     * Format nomor telepon untuk tampilan.
     */
    public function getFormattedPhoneAttribute(): string
    {
        $phone = $this->phone;
        if (str_starts_with($phone, '62')) {
            $phone = '+62 ' . substr($phone, 2, 3) . '-' . substr($phone, 5, 4) . '-' . substr($phone, 9);
        }
        return $phone;
    }
}
