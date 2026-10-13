<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramUser;
use App\Models\TelegramVoucherRequest;
use App\Models\Package;
use App\Models\HotspotVoucher;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TelegramWebhookController extends Controller
{
    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(Request $request)
    {
        $update = $request->all();

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function getOrCreateUser($from)
    {
        return TelegramUser::firstOrCreate(
            ['chat_id' => $from['id']],
            [
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
            ]
        );
    }

    protected function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = $this->getOrCreateUser($message['from']);

        if (strpos($text, '/start') === 0) {
            $this->sendMainMenu($chatId, $user);
        } elseif (strpos($text, '/cek_saldo') === 0) {
            $this->telegram->sendMessage($chatId, "💰 Saldo Anda saat ini: *Rp " . number_format($user->balance, 0, ',', '.') . "*");
        } elseif (strtoupper(substr(trim($text), 0, 8)) === 'VOUCHER#') {
            // Parsing format: VOUCHER#ROUTER_ID#KODE_PAKET#NAMA
            $this->processTextVoucherRequest($chatId, $user, trim($text));
        } else {
            $this->telegram->sendMessage($chatId, "Ketik /start untuk melihat menu, atau kirim pesan dengan format:\n`VOUCHER#ID_ROUTER#KODE_PAKET#NAMA`");
        }
    }

    protected function processTextVoucherRequest($chatId, $user, $text)
    {
        $parts = explode('#', strtoupper($text));
        
        if (count($parts) < 3) {
            $this->telegram->sendMessage($chatId, "❌ *Format Salah*\n\nFormat yang benar:\n`VOUCHER#ID_ROUTER#KODE_PAKET#NAMA_ANDA`\n\nContoh:\n`VOUCHER#1#1JAM#Budi`");
            return;
        }

        $routerId = trim($parts[1]);
        $packageCode = trim($parts[2]);
        $requesterName = count($parts) >= 4 ? trim(implode(' ', array_slice(array_map('trim', explode('#', $text)), 3))) : $user->first_name;

        // Cari Router
        $router = \App\Models\Router::find($routerId);
        if (!$router) {
            $this->telegram->sendMessage($chatId, "❌ Router dengan ID *{$routerId}* tidak ditemukan.");
            return;
        }

        // Cari Paket
        $package = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where(function ($q) use ($packageCode) {
                $q->whereRaw('UPPER(slug) = ?', [$packageCode])
                  ->orWhereRaw('UPPER(name) = ?', [$packageCode]);
            })
            ->first();

        if (!$package) {
            $this->telegram->sendMessage($chatId, "❌ Paket *{$packageCode}* tidak ditemukan.");
            return;
        }

        // Cek Saldo
        if ($user->balance < $package->price) {
            $this->telegram->sendMessage($chatId, "❌ Saldo Anda tidak mencukupi untuk paket ini.\nHarga Paket: Rp " . number_format($package->price, 0, ',', '.') . "\nSaldo Anda: Rp " . number_format($user->balance, 0, ',', '.'));
            return;
        }

        // Cari Voucher untuk Router dan Paket spesifik
        DB::beginTransaction();
        try {
            $voucher = HotspotVoucher::where('router_id', $router->id)
                ->where('package_id', $package->id)
                ->where('is_used', false)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                DB::rollBack();
                $this->telegram->sendMessage($chatId, "❌ Maaf, stok voucher untuk paket ini di Router *{$router->name}* sedang habis.");
                return;
            }

            // Potong saldo jika tidak gratis
            if ($package->price > 0) {
                $user->balance -= $package->price;
                $user->save();
            }

            $expiresAt = now()->addDays($package->duration_days);

            $voucher->update([
                'is_used'  => true,
                'used_by'  => "telegram:{$user->chat_id}",
                'used_at'  => now(),
                'expires_at' => $expiresAt,
            ]);

            TelegramVoucherRequest::create([
                'telegram_user_id' => $user->id,
                'package_id' => $package->id,
                'voucher_id' => $voucher->id,
                'price_deducted' => $package->price,
                'status' => 'success',
            ]);

            DB::commit();

            $reply  = "✅ *Permintaan Voucher Berhasil!*\n\n";
            $reply .= "🎟 Kode: *{$voucher->code}*\n";
            $reply .= "📡 Router: {$router->name}\n";
            $reply .= "📦 Paket: {$package->name}\n";
            $reply .= "💰 Harga: " . ($package->price == 0 ? "GRATIS" : "Rp " . number_format($package->price, 0, ',', '.')) . "\n";
            $reply .= "⏱ Berlaku hingga: " . $expiresAt->format('d M Y H:i') . "\n\n";
            $reply .= "Terima kasih, *{$requesterName}*. Silakan gunakan kode tersebut di halaman login hotspot.";

            $this->telegram->sendMessage($chatId, $reply);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Telegram Purchase Error] " . $e->getMessage());
            $this->telegram->sendMessage($chatId, "❌ Terjadi kesalahan sistem saat memproses voucher Anda.");
        }
    }

    protected function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        $user = $this->getOrCreateUser($callbackQuery['from']);

        // Format data: action_id
        $parts = explode('_', $data);
        $action = $parts[0];

        if ($action === 'main') {
            $this->editToMainMenu($chatId, $messageId, $user);
            $this->telegram->answerCallbackQuery($callbackQuery['id']);
        } elseif ($action === 'paket') {
            $this->showPackages($chatId, $messageId);
            $this->telegram->answerCallbackQuery($callbackQuery['id']);
        } elseif ($action === 'buy' && isset($parts[1])) {
            $packageId = $parts[1];
            $this->processPurchase($chatId, $messageId, $user, $packageId, $callbackQuery['id']);
        } else {
            $this->telegram->answerCallbackQuery($callbackQuery['id'], "Aksi tidak dikenali.");
        }
    }

    protected function sendMainMenu($chatId, $user)
    {
        $text = "Selamat datang di *MANTIK Hotspot* 🌐\n\n";
        $text .= "Hai {$user->first_name}, silakan pilih menu di bawah ini. Saldo Anda: *Rp " . number_format($user->balance, 0, ',', '.') . "*";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📦 Lihat Paket Internet', 'callback_data' => 'paket'],
                ],
                [
                    ['text' => '❓ Bantuan', 'callback_data' => 'help'],
                    ['text' => '💰 Cek Saldo', 'callback_data' => 'cek_saldo']
                ]
            ]
        ];

        $this->telegram->sendMessage($chatId, $text, $keyboard);
    }
    
    protected function editToMainMenu($chatId, $messageId, $user)
    {
        $text = "Selamat datang di *MANTIK Hotspot* 🌐\n\n";
        $text .= "Hai {$user->first_name}, silakan pilih menu di bawah ini. Saldo Anda: *Rp " . number_format($user->balance, 0, ',', '.') . "*";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📦 Lihat Paket Internet', 'callback_data' => 'paket'],
                ],
                [
                    ['text' => '💰 Cek Saldo', 'callback_data' => 'cek_saldo']
                ]
            ]
        ];

        $this->telegram->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    protected function showPackages($chatId, $messageId)
    {
        $packages = Package::where('type', 'hotspot')->where('is_active', true)->get();
        
        $text = "Berikut adalah paket yang tersedia:\nPilih paket untuk membeli:";
        
        $inlineKeyboard = [];
        foreach ($packages as $pkg) {
            $priceText = $pkg->price == 0 ? "Gratis" : "Rp " . number_format($pkg->price, 0, ',', '.');
            $inlineKeyboard[] = [
                ['text' => "{$pkg->name} - {$priceText}", 'callback_data' => "buy_{$pkg->id}"]
            ];
        }
        
        $inlineKeyboard[] = [
            ['text' => '🔙 Kembali', 'callback_data' => 'main']
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];

        $this->telegram->editMessageText($chatId, $messageId, $text, $keyboard);
    }

    protected function processPurchase($chatId, $messageId, $user, $packageId, $callbackQueryId)
    {
        $package = Package::find($packageId);
        if (!$package) {
            $this->telegram->answerCallbackQuery($callbackQueryId, "Paket tidak ditemukan.", true);
            return;
        }

        if ($user->balance < $package->price) {
            $this->telegram->answerCallbackQuery($callbackQueryId, "Saldo Anda tidak mencukupi untuk membeli paket ini.", true);
            return;
        }

        DB::beginTransaction();
        try {
            // Find available voucher
            $voucher = HotspotVoucher::where('package_id', $package->id)
                ->where('is_used', false)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                DB::rollBack();
                $this->telegram->answerCallbackQuery($callbackQueryId, "Maaf, stok voucher untuk paket ini sedang habis.", true);
                return;
            }

            // Deduct balance
            if ($package->price > 0) {
                $user->balance -= $package->price;
                $user->save();
            }

            $expiresAt = now()->addDays($package->duration_days);

            $voucher->update([
                'is_used'  => true,
                'used_by'  => "telegram:{$user->chat_id}",
                'used_at'  => now(),
                'expires_at' => $expiresAt,
            ]);

            TelegramVoucherRequest::create([
                'telegram_user_id' => $user->id,
                'package_id' => $package->id,
                'voucher_id' => $voucher->id,
                'price_deducted' => $package->price,
                'status' => 'success',
            ]);

            DB::commit();

            $this->telegram->answerCallbackQuery($callbackQueryId, "Pembelian berhasil!");

            $text = "✅ *Pembelian Berhasil!*\n\n";
            $text .= "🎟 Kode: *{$voucher->code}*\n";
            $text .= "📦 Paket: {$package->name}\n";
            $text .= "💰 Harga: Rp " . number_format($package->price, 0, ',', '.') . "\n";
            $text .= "⏱ Berlaku hingga: " . $expiresAt->format('d M Y H:i') . "\n\n";
            $text .= "Sisa Saldo Anda: *Rp " . number_format($user->balance, 0, ',', '.') . "*\n\n";
            $text .= "Silakan gunakan kode tersebut di halaman login hotspot.";

            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔙 Menu Utama', 'callback_data' => 'main']]
                ]
            ];

            $this->telegram->editMessageText($chatId, $messageId, $text, $keyboard);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Telegram Purchase Error] " . $e->getMessage());
            $this->telegram->answerCallbackQuery($callbackQueryId, "Terjadi kesalahan sistem.", true);
        }
    }
}
