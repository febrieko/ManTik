<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramVoucherRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'package_id',
        'voucher_id',
        'price_deducted',
        'status',
        'failure_reason',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function voucher()
    {
        return $this->belongsTo(HotspotVoucher::class, 'voucher_id');
    }
}
