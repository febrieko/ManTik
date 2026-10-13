<?php

namespace App\Http\Controllers;

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
        $botToken = config('services.telegram.bot_token');
        return view('telegram.settings', compact('botToken'));
    }

    public function setWebhook(Request $request)
    {
        $url = url('/api/telegram/webhook');
        $response = $this->telegram->setWebhook($url);
        
        if ($response['ok']) {
            return back()->with('success', 'Webhook berhasil diset ke: ' . $url);
        }
        
        return back()->with('error', 'Gagal set webhook: ' . ($response['description'] ?? 'Unknown Error'));
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
