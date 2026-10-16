<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TelegramUser;
use App\Models\TelegramVoucherRequest;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramAdminController extends Controller
{
    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function settings()
    {
        $botToken = Setting::get('telegram_bot_token') ?: config('services.telegram.bot_token');
        $adminChatId = Setting::get('telegram_admin_chat_id', '');

        $botInfo = null;
        $webhookInfo = null;

        if (!empty($botToken)) {
            $botInfo = $this->telegram->getMe();
            $webhookInfo = $this->telegram->getWebhookInfo();
        }

        return view('telegram.settings', compact('botToken', 'adminChatId', 'botInfo', 'webhookInfo'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'bot_token'              => 'nullable|string|max:255',
            'telegram_admin_chat_id' => 'nullable|string|max:100',
        ]);

        $botToken = trim($validated['bot_token'] ?? '');
        $adminChatId = trim($validated['telegram_admin_chat_id'] ?? '');

        // Update token jika diisi
        if (!empty($botToken)) {
            Setting::set('telegram_bot_token', $botToken, 'telegram');

            // Simpan / update di file .env
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $content = file_get_contents($envPath);
                $key = 'TELEGRAM_BOT_TOKEN';
                if (preg_match("/^{$key}=.*/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}={$botToken}", $content);
                } else {
                    $content .= "\n{$key}={$botToken}";
                }
                file_put_contents($envPath, $content);
            }

            config(['services.telegram.bot_token' => $botToken]);
            $this->telegram->setToken($botToken);
        }

        Setting::set('telegram_admin_chat_id', $adminChatId, 'notification');

        return back()->with('success', 'Pengaturan Telegram Bot berhasil disimpan.');
    }

    public function setWebhook(Request $request)
    {
        $url = url('/api/telegram/webhook');
        $response = $this->telegram->setWebhook($url);
        
        if (isset($response['ok']) && $response['ok']) {
            return back()->with('success', 'Webhook berhasil diset ke: ' . $url);
        }
        
        return back()->with('error', 'Gagal set webhook: ' . ($response['description'] ?? 'Unknown Error'));
    }

    public function testSend(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'message' => 'nullable|string|max:500',
        ]);

        $message = $validated['message'] ?? "🔔 *Tes Notifikasi Telegram MANTIK*\n\nSelamat! Koneksi bot Telegram berhasil terhubung dan siap digunakan.";
        $res = $this->telegram->sendMessage($validated['chat_id'], $message);

        if (isset($res['ok']) && $res['ok']) {
            return back()->with('success', 'Pesan tes berhasil dikirim ke Chat ID: ' . $validated['chat_id']);
        }

        return back()->with('error', 'Gagal kirim pesan: ' . ($res['description'] ?? 'Pastikan Chat ID benar dan Anda sudah menekan /start di bot'));
    }

    public function users(Request $request)
    {
        $users = TelegramUser::latest()->paginate(20);
        return view('telegram.users', compact('users'));
    }

    public function topup(Request $request, TelegramUser $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user->balance += $request->amount;
        $user->save();
        
        // Notify user via Telegram
        $text = "🎉 *Top Up Berhasil!*\n\nSaldo Anda telah ditambahkan sebesar *Rp " . number_format($request->amount, 0, ',', '.') . "*.\nSaldo total saat ini: *Rp " . number_format($user->balance, 0, ',', '.') . "*";
        $this->telegram->sendMessage($user->chat_id, $text);

        return back()->with('success', "Berhasil menambahkan saldo Rp " . number_format($request->amount, 0, ',', '.') . " ke {$user->first_name}.");
    }

    public function requests()
    {
        $requests = TelegramVoucherRequest::with(['telegramUser', 'package', 'voucher'])->latest()->paginate(20);
        return view('telegram.requests', compact('requests'));
    }
}
