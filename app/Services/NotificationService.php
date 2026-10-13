<?php

namespace App\Services;

use App\Models\Router;
use App\Models\User;
use App\Models\Customer;
use App\Models\TelegramUser;
use App\Models\HotspotVoucher;
use App\Models\HotspotUser;
use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected WhatsAppService $whatsapp;
    protected TelegramService $telegram;

    public function __construct(WhatsAppService $whatsapp, TelegramService $telegram)
    {
        $this->whatsapp = $whatsapp;
        $this->telegram = $telegram;
    }

    /**
     * Send router status change alert to Super Admin, Admin, and Teknisi
     */
    public function sendRouterStatusAlert(
        Router $router,
        string $newStatus,
        ?string $previousStatus = null,
        ?string $reason = null,
        ?string $downtimeDuration = null,
        ?int $responseTimeMs = null
    ): array {
        // Check if router alert is enabled
        $isEnabled = Setting::get('router_alert_enabled', true);
        if (!$isEnabled) {
            return ['sent' => false, 'reason' => 'Router alert is disabled in settings'];
        }

        $channelSetting = Setting::get('router_alert_channel', 'both'); // 'whatsapp', 'telegram', 'both'

        // Prepare message text
        $now = now()->translatedFormat('d F Y, H:i:s');
        $isDown = ($newStatus === 'offline');

        if ($isDown) {
            $subject = "PERINGATAN ROUTER OFFLINE: {$router->name}";
            $message = "🔴 *PERINGATAN ROUTER OFFLINE (DOWN)*\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "🖥 *Router:* {$router->name}\n"
                     . "🌐 *Host:* {$router->host}:{$router->port}\n"
                     . "📍 *Lokasi:* " . ($router->location ?: 'Tidak ada data') . "\n"
                     . "⏰ *Waktu:* {$now}\n"
                     . "⚠️ *Penyebab:* " . ($reason ?: 'Koneksi terputus / timeout') . "\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "⚠️ _Segera lakukan pengecekan fisik/jaringan pada perangkat._";
        } else {
            $subject = "ROUTER ONLINE KEMBALI: {$router->name}";
            $durationInfo = $downtimeDuration ? "⏱ *Downtime Sebelumnya:* {$downtimeDuration}\n" : "";
            $latencyInfo = $responseTimeMs ? "⚡ *Latensi Ping:* {$responseTimeMs} ms\n" : "";

            $message = "🟢 *ROUTER ONLINE KEMBALI (UP)*\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "🖥 *Router:* {$router->name}\n"
                     . "🌐 *Host:* {$router->host}:{$router->port}\n"
                     . "📍 *Lokasi:* " . ($router->location ?: 'Tidak ada data') . "\n"
                     . "⏰ *Waktu:* {$now}\n"
                     . $durationInfo
                     . $latencyInfo
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "✅ _Perangkat MikroTik kembali terhubung normal._";
        }

        // Collect recipient contact lists (Super Admin, Admin, Teknisi)
        $waRecipients = [];
        $tgRecipients = [];

        // 1. Global Admin WhatsApp from settings / config
        $globalWa = Setting::get('whatsapp_admin_phone', config('whatsapp.admin_phone'));
        if (!empty($globalWa)) {
            $waRecipients[] = $globalWa;
        }

        // 2. Global Telegram Chat ID / Group ID from settings
        $globalTg = Setting::get('telegram_admin_chat_id');
        if (!empty($globalTg)) {
            $tgRecipients[] = $globalTg;
        }

        // 3. Super Admin & Admin users
        $adminUsers = User::role(['super-admin', 'admin'])->where('is_active', true)->get();
        foreach ($adminUsers as $u) {
            if (!empty($u->phone)) $waRecipients[] = $u->phone;
            if (!empty($u->telegram_chat_id)) $tgRecipients[] = $u->telegram_chat_id;
        }

        // 4. Teknisi assigned to this router (or all teknisi if none assigned)
        $teknisiUsers = $router->users()->where('is_active', true)->get();
        if ($teknisiUsers->isEmpty()) {
            $teknisiUsers = User::role('teknisi')->where('is_active', true)->get();
        }

        foreach ($teknisiUsers as $tu) {
            if (!empty($tu->phone)) $waRecipients[] = $tu->phone;
            if (!empty($tu->telegram_chat_id)) $tgRecipients[] = $tu->telegram_chat_id;
        }

        // De-duplicate recipients
        $waRecipients = array_unique(array_filter($waRecipients));
        $tgRecipients = array_unique(array_filter($tgRecipients));

        $type = $isDown ? 'router_down' : 'router_up';
        $sentCount = 0;

        // Send WhatsApp
        if (in_array($channelSetting, ['whatsapp', 'both'])) {
            foreach ($waRecipients as $phone) {
                try {
                    $res = $this->whatsapp->sendMessage($phone, $message);
                    $success = $res['success'] ?? false;
                    $err = $res['error'] ?? null;

                    NotificationLog::create([
                        'router_id'     => $router->id,
                        'type'          => $type,
                        'channel'       => 'whatsapp',
                        'recipient'     => $phone,
                        'subject'       => $subject,
                        'message'       => $message,
                        'status'        => $success ? 'sent' : 'failed',
                        'error_message' => $err,
                        'sent_at'       => $success ? now() : null,
                    ]);

                    if ($success) $sentCount++;
                } catch (\Exception $e) {
                    Log::error("[NotificationService] Gagal kirim WA router alert ke {$phone}: " . $e->getMessage());
                }
            }
        }

        // Send Telegram
        if (in_array($channelSetting, ['telegram', 'both'])) {
            foreach ($tgRecipients as $chatId) {
                try {
                    $res = $this->telegram->sendMessage($chatId, $message);
                    $success = isset($res['ok']) && $res['ok'] === true;
                    $err = $res['description'] ?? null;

                    NotificationLog::create([
                        'router_id'     => $router->id,
                        'type'          => $type,
                        'channel'       => 'telegram',
                        'recipient'     => (string) $chatId,
                        'subject'       => $subject,
                        'message'       => $message,
                        'status'        => $success ? 'sent' : 'failed',
                        'error_message' => $err,
                        'sent_at'       => $success ? now() : null,
                    ]);

                    if ($success) $sentCount++;
                } catch (\Exception $e) {
                    Log::error("[NotificationService] Gagal kirim Telegram router alert ke {$chatId}: " . $e->getMessage());
                }
            }
        }

        return [
            'sent'          => true,
            'count'         => $sentCount,
            'wa_recipients' => count($waRecipients),
            'tg_recipients' => count($tgRecipients),
        ];
    }

    /**
     * Send voucher/hotspot expiry warning to customer
     */
    public function sendVoucherExpiryAlert(
        string $recipientName,
        string $code,
        string $packageName,
        $expiresAt,
        string $remainingDuration,
        ?string $phone = null,
        ?string $telegramChatId = null,
        ?int $customerId = null
    ): array {
        $isEnabled = Setting::get('customer_voucher_alert_enabled', true);
        if (!$isEnabled) {
            return ['sent' => false, 'reason' => 'Customer voucher alert is disabled'];
        }

        $channelSetting = Setting::get('customer_voucher_alert_channel', 'both');

        $expText = $expiresAt ? \Carbon\Carbon::parse($expiresAt)->translatedFormat('d M Y, H:i') : '-';

        $subject = "Pemberitahuan: Masa Aktif Voucher Hampir Habis ({$code})";
        $message = "⏳ *PEMBERITAHUAN MASA AKTIF VOUCHER*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Halo, *{$recipientName}*!\n\n"
                 . "Masa aktif akses internet voucher Anda akan segera berakhir:\n\n"
                 . "🎟 *Kode Voucher:* `{$code}`\n"
                 . "📦 *Paket:* {$packageName}\n"
                 . "📅 *Berlaku Hingga:* {$expText}\n"
                 . "⏱ *Sisa Waktu:* {$remainingDuration}\n\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "💡 _Silakan lakukan perpanjangan atau beli voucher baru agar koneksi internet Anda tidak terputus._ 🙏";

        $results = [];

        // Send via WhatsApp
        if (!empty($phone) && in_array($channelSetting, ['whatsapp', 'both'])) {
            try {
                $res = $this->whatsapp->sendMessage($phone, $message);
                $success = $res['success'] ?? false;
                NotificationLog::create([
                    'customer_id'   => $customerId,
                    'type'          => 'voucher_expiring',
                    'channel'       => 'whatsapp',
                    'recipient'     => $phone,
                    'subject'       => $subject,
                    'message'       => $message,
                    'status'        => $success ? 'sent' : 'failed',
                    'error_message' => $res['error'] ?? null,
                    'sent_at'       => $success ? now() : null,
                ]);
                $results['whatsapp'] = $success;
            } catch (\Exception $e) {
                Log::error("[Notification] WA Voucher Expiry Error: " . $e->getMessage());
            }
        }

        // Send via Telegram
        if (!empty($telegramChatId) && in_array($channelSetting, ['telegram', 'both'])) {
            try {
                $res = $this->telegram->sendMessage($telegramChatId, $message);
                $success = isset($res['ok']) && $res['ok'] === true;
                NotificationLog::create([
                    'customer_id'   => $customerId,
                    'type'          => 'voucher_expiring',
                    'channel'       => 'telegram',
                    'recipient'     => (string) $telegramChatId,
                    'subject'       => $subject,
                    'message'       => $message,
                    'status'        => $success ? 'sent' : 'failed',
                    'error_message' => $res['description'] ?? null,
                    'sent_at'       => $success ? now() : null,
                ]);
                $results['telegram'] = $success;
            } catch (\Exception $e) {
                Log::error("[Notification] TG Voucher Expiry Error: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Send low balance warning to customer
     */
    public function sendLowBalanceAlert(
        string $recipientName,
        float $balance,
        float $threshold,
        ?string $phone = null,
        ?string $telegramChatId = null,
        ?int $customerId = null
    ): array {
        $isEnabled = Setting::get('customer_balance_alert_enabled', true);
        if (!$isEnabled) {
            return ['sent' => false, 'reason' => 'Customer balance alert is disabled'];
        }

        $channelSetting = Setting::get('customer_balance_alert_channel', 'both');

        $formattedBalance = number_format($balance, 0, ',', '.');
        $formattedThreshold = number_format($threshold, 0, ',', '.');

        $subject = "Pemberitahuan: Saldo Anda Menipis";
        $message = "⚠️ *PERINGATAN SALDO MENIPIS*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Halo, *{$recipientName}*!\n\n"
                 . "Saldo akun deposit Anda saat ini hampir habis:\n\n"
                 . "💰 *Sisa Saldo:* Rp {$formattedBalance}\n"
                 . "⚠️ *Batas Minimum:* Rp {$formattedThreshold}\n\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "💡 _Silakan lakukan isi ulang (top-up) saldo Anda agar pembelian voucher atau perpanjangan paket internet dapat diproses tanpa gangguan._ 🙏";

        $results = [];

        // Send via WhatsApp
        if (!empty($phone) && in_array($channelSetting, ['whatsapp', 'both'])) {
            try {
                $res = $this->whatsapp->sendMessage($phone, $message);
                $success = $res['success'] ?? false;
                NotificationLog::create([
                    'customer_id'   => $customerId,
                    'type'          => 'balance_low',
                    'channel'       => 'whatsapp',
                    'recipient'     => $phone,
                    'subject'       => $subject,
                    'message'       => $message,
                    'status'        => $success ? 'sent' : 'failed',
                    'error_message' => $res['error'] ?? null,
                    'sent_at'       => $success ? now() : null,
                ]);
                $results['whatsapp'] = $success;
            } catch (\Exception $e) {
                Log::error("[Notification] WA Low Balance Error: " . $e->getMessage());
            }
        }

        // Send via Telegram
        if (!empty($telegramChatId) && in_array($channelSetting, ['telegram', 'both'])) {
            try {
                $res = $this->telegram->sendMessage($telegramChatId, $message);
                $success = isset($res['ok']) && $res['ok'] === true;
                NotificationLog::create([
                    'customer_id'   => $customerId,
                    'type'          => 'balance_low',
                    'channel'       => 'telegram',
                    'recipient'     => (string) $telegramChatId,
                    'subject'       => $subject,
                    'message'       => $message,
                    'status'        => $success ? 'sent' : 'failed',
                    'error_message' => $res['description'] ?? null,
                    'sent_at'       => $success ? now() : null,
                ]);
                $results['telegram'] = $success;
            } catch (\Exception $e) {
                Log::error("[Notification] TG Low Balance Error: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(string $channel, ?string $targetPhone = null, ?string $targetTelegramId = null): array
    {
        $time = now()->translatedFormat('d M Y, H:i:s');
        $message = "🔔 *TEST NOTIFIKASI MANTIK*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Ini adalah pesan uji coba dari sistem *MANTIK MikroTik Manager*.\n"
                 . "⏰ Waktu: {$time}\n"
                 . "Channel: *" . strtoupper($channel) . "*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "✅ Integrasi notifikasi berfungsi dengan baik!";

        $results = ['success' => false, 'messages' => []];

        if (in_array($channel, ['whatsapp', 'both'])) {
            $phone = $targetPhone ?: Setting::get('whatsapp_admin_phone', config('whatsapp.admin_phone'));
            if ($phone) {
                $res = $this->whatsapp->sendMessage($phone, $message);
                $success = $res['success'] ?? false;
                NotificationLog::create([
                    'type'          => 'custom',
                    'channel'       => 'whatsapp',
                    'recipient'     => $phone,
                    'subject'       => 'Test Notifikasi WhatsApp',
                    'message'       => $message,
                    'status'        => $success ? 'sent' : 'failed',
                    'error_message' => $res['error'] ?? null,
                    'sent_at'       => $success ? now() : null,
                ]);
                $results['whatsapp'] = $res;
                if ($success) $results['success'] = true;
            } else {
                $results['whatsapp'] = ['success' => false, 'error' => 'Nomor WhatsApp tujuan belum diatur'];
            }
        }

        if (in_array($channel, ['telegram', 'both'])) {
            $chatId = $targetTelegramId ?: Setting::get('telegram_admin_chat_id');
            if ($chatId) {
                $res = $this->telegram->sendMessage($chatId, $message);
                $success = isset($res['ok']) && $res['ok'] === true;
                NotificationLog::create([
                    'type'          => 'custom',
                    'channel'       => 'telegram',
                    'recipient'     => (string) $chatId,
                    'subject'       => 'Test Notifikasi Telegram',
                    'message'       => $message,
                    'status'        => $success ? 'sent' : 'failed',
                    'error_message' => $res['description'] ?? null,
                    'sent_at'       => $success ? now() : null,
                ]);
                $results['telegram'] = $res;
                if ($success) $results['success'] = true;
            } else {
                $results['telegram'] = ['success' => false, 'error' => 'Telegram Chat ID tujuan belum diatur'];
            }
        }

        return $results;
    }
}
