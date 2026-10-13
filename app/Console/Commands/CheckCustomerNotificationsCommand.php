<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\TelegramUser;
use App\Models\HotspotVoucher;
use App\Models\HotspotUser;
use App\Models\WaVoucherRequest;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\NotificationService;

class CheckCustomerNotificationsCommand extends Command
{
    protected $signature = 'mantik:check-customer-notifications {--force : Abaikan jeda waktu pencegahan duplikat}';
    protected $description = 'Periksa voucher hampir kadaluarsa dan saldo pelanggan yang menipis, lalu kirim notifikasi via WhatsApp / Telegram';

    public function handle(NotificationService $notificationService)
    {
        $this->info('Memulai pengecekan notifikasi pelanggan...');
        $force = $this->option('force');

        // ============================================
        // 1. CEK VOUCHER HAMPIR KADALUARSA
        // ============================================
        $voucherAlertEnabled = Setting::get('customer_voucher_alert_enabled', true);
        $expiryHours = (int) Setting::get('customer_voucher_expiry_hours', 2);
        $vouchersNotified = 0;

        if ($voucherAlertEnabled) {
            $this->info("Memeriksa voucher aktif yang kadaluarsa dalam {$expiryHours} jam ke depan...");
            $windowEnd = now()->addHours($expiryHours);

            // A. Dari HotspotVoucher (digunakan dan punya expires_at)
            $vouchers = HotspotVoucher::with('package')
                ->where('is_used', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', $windowEnd)
                ->get();

            foreach ($vouchers as $voucher) {
                // Cari nomor penerima dari WaVoucherRequest atau Customer
                $phone = null;
                $telegramChatId = null;
                $recipientName = 'Pelanggan Hotspot';
                $customerId = null;

                $waReq = WaVoucherRequest::where('voucher_id', $voucher->id)->latest()->first();
                if ($waReq) {
                    $phone = $waReq->phone;
                    $recipientName = $waReq->requester_name ?: $recipientName;
                } elseif (!empty($voucher->used_by) && is_numeric($voucher->used_by)) {
                    $phone = $voucher->used_by;
                }

                // Cek duplikat notifikasi dalam 12 jam terakhir
                $alreadyNotified = false;
                if (!$force && $phone) {
                    $alreadyNotified = NotificationLog::where('type', 'voucher_expiring')
                        ->where('recipient', $phone)
                        ->where('created_at', '>=', now()->subHours(12))
                        ->exists();
                }

                if (!$alreadyNotified && ($phone || $telegramChatId)) {
                    $diffSec = max(0, $voucher->expires_at->diffInSeconds(now()));
                    $remaining = \App\Models\RouterUptimeLog::formatDuration($diffSec);

                    $notificationService->sendVoucherExpiryAlert(
                        $recipientName,
                        $voucher->code,
                        $voucher->package->name ?? 'Hotspot Regular',
                        $voucher->expires_at,
                        $remaining,
                        $phone,
                        $telegramChatId,
                        $customerId
                    );
                    $vouchersNotified++;
                }
            }

            // B. Dari HotspotUser yang berelasi dengan Customer
            $hotspotUsers = HotspotUser::with(['customer', 'package'])
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', $windowEnd)
                ->get();

            foreach ($hotspotUsers as $hUser) {
                if (!$hUser->customer) continue;
                $customer = $hUser->customer;
                $phone = $customer->phone;
                $telegramChatId = $customer->telegram_chat_id;

                $alreadyNotified = false;
                if (!$force && $phone) {
                    $alreadyNotified = NotificationLog::where('type', 'voucher_expiring')
                        ->where('customer_id', $customer->id)
                        ->where('created_at', '>=', now()->subHours(12))
                        ->exists();
                }

                if (!$alreadyNotified && ($phone || $telegramChatId)) {
                    $diffSec = max(0, $hUser->expires_at->diffInSeconds(now()));
                    $remaining = \App\Models\RouterUptimeLog::formatDuration($diffSec);

                    $notificationService->sendVoucherExpiryAlert(
                        $customer->name,
                        $hUser->username,
                        $hUser->package->name ?? 'Hotspot Member',
                        $hUser->expires_at,
                        $remaining,
                        $phone,
                        $telegramChatId,
                        $customer->id
                    );
                    $vouchersNotified++;
                }
            }
        }

        // ============================================
        // 2. CEK SALDO PELANGGAN HAMPIR HABIS
        // ============================================
        $balanceAlertEnabled = Setting::get('customer_balance_alert_enabled', true);
        $threshold = (float) Setting::get('customer_balance_threshold', 10000);
        $balanceNotified = 0;

        if ($balanceAlertEnabled) {
            $this->info("Memeriksa pelanggan dengan saldo <= Rp " . number_format($threshold, 0, ',', '.') . "...");

            // A. Dari tabel Customer
            $customers = Customer::where('balance', '<=', $threshold)
                ->where(function ($q) {
                    $q->whereNotNull('phone')->orWhereNotNull('telegram_chat_id');
                })
                ->get();

            foreach ($customers as $cust) {
                $alreadyNotified = false;
                if (!$force) {
                    $alreadyNotified = NotificationLog::where('type', 'balance_low')
                        ->where('customer_id', $cust->id)
                        ->where('created_at', '>=', now()->subHours(24))
                        ->exists();
                }

                if (!$alreadyNotified) {
                    $notificationService->sendLowBalanceAlert(
                        $cust->name,
                        (float) $cust->balance,
                        $threshold,
                        $cust->phone,
                        $cust->telegram_chat_id,
                        $cust->id
                    );
                    $balanceNotified++;
                }
            }

            // B. Dari tabel TelegramUser
            $tgUsers = TelegramUser::where('balance', '<=', $threshold)
                ->whereNotNull('chat_id')
                ->get();

            foreach ($tgUsers as $tgUser) {
                $alreadyNotified = false;
                if (!$force) {
                    $alreadyNotified = NotificationLog::where('type', 'balance_low')
                        ->where('recipient', (string) $tgUser->chat_id)
                        ->where('created_at', '>=', now()->subHours(24))
                        ->exists();
                }

                if (!$alreadyNotified) {
                    $notificationService->sendLowBalanceAlert(
                        $tgUser->full_name ?: 'Pengguna Telegram',
                        (float) $tgUser->balance,
                        $threshold,
                        null,
                        $tgUser->chat_id,
                        null
                    );
                    $balanceNotified++;
                }
            }
        }

        $this->info("Selesai. Notifikasi terkirim: Voucher Expiring ({$vouchersNotified}), Saldo Menipis ({$balanceNotified}).");
        return 0;
    }
}
