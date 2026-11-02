<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotspotVoucher;
use App\Models\Package;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Customer;
use App\Models\WaVoucherRequest;
use App\Services\MikrotikService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppService $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Menerima webhook dari WhatsApp Gateway (pesan masuk dari pengunjung).
     *
     * Format pesan yang diterima:
     *   VOUCHER#KODE_PAKET#NAMA_PENGUNJUNG
     *   Contoh: VOUCHER#1JAM#Budi Santoso
     *
     * Atau pesan INFO untuk mendapatkan daftar paket:
     *   INFO
     */
    public function incoming(Request $request)
    {
        // Respons GET untuk healthcheck / verifikasi webhook dari browser atau dashboard gateway
        if ($request->isMethod('get') && !$request->has('message') && !$request->has('text')) {
            return response()->json([
                'status'  => 'active',
                'service' => 'MANTIK WhatsApp Webhook Gateway',
                'time'    => now()->toDateTimeString(),
            ]);
        }

        // Handle tes ping/verifikasi dari dashboard OpenWA atau gateway lain
        $event = $request->input('event') ?? $request->input('type');
        if (in_array(strtolower((string)$event), ['ping', 'test', 'webhook.test']) || $request->has('ping')) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Webhook connection verified successfully (pong)',
            ]);
        }

        // Validasi webhook secret jika dikonfigurasi
        $webhookSecret = Setting::get('whatsapp_webhook_secret') ?: config('whatsapp.webhook_secret');
        if ($webhookSecret) {
            $receivedSecret = $request->header('X-Webhook-Secret') ?? $request->get('secret');
            if ($receivedSecret !== $webhookSecret) {
                Log::warning('[WA Webhook] Invalid secret received');
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        // Abaikan jika pesan dikirim oleh bot/nomor gateway itu sendiri (mencegah auto-reply loop)
        if ($request->input('payload.fromMe') === true || 
            $request->input('data.fromMe') === true || 
            $request->input('fromMe') === true) {
            return response()->json(['status' => 'ignored', 'reason' => 'from_me']);
        }

        // Ambil data pesan dari request (Mendukung OpenWA, Fonnte, Wablas, dan generic gateway)
        $payload = $request->input('payload') ?? $request->input('data') ?? [];

        $senderPhone = null;
        $senderName  = null;
        $messageBody = null;
        $rawChatId   = null;

        if (is_array($payload) && !empty($payload)) {
            $rawChatId   = $payload['from'] ?? $payload['chatId'] ?? $payload['sender']['id'] ?? null;
            $messageBody = $payload['body'] ?? $payload['text'] ?? $payload['message'] ?? null;
            $senderName  = $payload['sender']['pushname'] ?? $payload['sender']['name'] ?? $payload['pushname'] ?? $payload['name'] ?? null;

            // Prioritaskan nomor HP asli dari sender
            $senderPhone = $payload['sender']['number']
                ?? $payload['sender']['formattedPhone']
                ?? $payload['sender']['phoneNumber']
                ?? $payload['author']
                ?? $payload['sender']['phone']
                ?? null;

            // Jika senderPhone masih kosong atau berformat LID (@lid), cari yang berformat nomor biasa
            if (!$senderPhone || str_contains((string)$senderPhone, '@lid')) {
                if (!empty($payload['author']) && !str_contains($payload['author'], '@lid')) {
                    $senderPhone = $payload['author'];
                } elseif (!empty($payload['from']) && !str_contains($payload['from'], '@lid')) {
                    $senderPhone = $payload['from'];
                } elseif (!empty($payload['chatId']) && !str_contains($payload['chatId'], '@lid')) {
                    $senderPhone = $payload['chatId'];
                }
            }
        }

        if (!$senderPhone) {
            $senderRaw = $request->input('sender');
            if (is_array($senderRaw)) {
                $senderPhone = $senderRaw['number'] ?? $senderRaw['formattedPhone'] ?? $senderRaw['phoneNumber'] ?? $senderRaw['phone'] ?? $senderRaw['id'] ?? null;
                $senderName  = $senderName ?: ($senderRaw['pushname'] ?? $senderRaw['name'] ?? null);
            } else {
                $senderPhone = $senderRaw ?? $request->input('from') ?? $request->input('phone');
            }
        }

        if (!$senderPhone && $rawChatId) {
            $senderPhone = $rawChatId;
        }

        if (empty($messageBody)) {
            $messageBody = $request->input('message') ?? $request->input('text') ?? $request->input('body') ?? '';
        }

        $messageBody = trim((string) $messageBody);

        if (!$senderPhone || !$messageBody) {
            return response()->json(['status' => 'success', 'message' => 'Webhook event received (non-message event)']);
        }

        $phone = $this->wa->normalizePhone($senderPhone);
        if (empty($phone) && $rawChatId) {
            $phone = preg_replace('/\D/', '', $rawChatId);
        }

        Log::info('[WA Webhook] Pesan masuk dari ' . $phone . ' (rawChatId: ' . $rawChatId . '): ' . $messageBody);

        $bodyUpper = strtoupper($messageBody);

        // 1. Cek jika pengirim sedang dalam sesi konfirmasi pembelian voucher
        if (Cache::has("wa_voucher_order:{$phone}")) {
            $confirmationResult = $this->handleOrderConfirmation($phone, $messageBody, $rawChatId);
            if ($confirmationResult !== null) {
                return $confirmationResult;
            }
        }

        // 2. Cek pesan langsung seperti INFO, PAKET, atau SALDO
        if ($bodyUpper === 'INFO' || $bodyUpper === 'PAKET') {
            return $this->handleInfoRequest($phone, $rawChatId);
        }

        if (in_array($bodyUpper, ['SALDO', 'CEK SALDO', 'CEKSALDO', 'INFO SALDO', 'MY SALDO'])) {
            return $this->handleCheckBalance($phone, $rawChatId);
        }

        if ($bodyUpper === 'BATAL') {
            $this->wa->sendMessage($phone, "ℹ️ Tidak ada transaksi pembelian voucher yang sedang menunggu konfirmasi.", $rawChatId);
            return response()->json(['status' => 'no_pending_order']);
        }

        if ($bodyUpper === 'FREE' || $bodyUpper === 'GRATIS') {
            return $this->processVoucherRequest($phone, 'FREE', $senderName ?: 'Pengunjung', null, $rawChatId);
        }

        // Parse format diawali VOUCHER
        $parts = explode('#', strtoupper($messageBody));
        $rawParts = explode('#', $messageBody);

        if (count($parts) < 2 || trim($parts[0]) !== 'VOUCHER') {
            // Pesan tidak dikenali, kirim petunjuk
            $availablePackages = $this->getAvailablePackagesFormatted();
            $this->wa->sendErrorMessage($phone, 'Format pesan tidak valid.', $availablePackages, null, $rawChatId);
            return response()->json(['status' => 'invalid_format']);
        }

        $routerQuery   = null;
        $packageCode   = null;
        $requesterName = $senderName ?: 'Pengunjung';

        if (count($parts) === 2) {
            // Format 2 bagian: VOUCHER#KODE_PAKET atau VOUCHER#SSID
            $token = trim($parts[1]);
            if ($this->isRouterQuery($token) && !$this->isPackageQuery($token)) {
                $routerQuery = $token;
                $packageCode = 'FREE';
            } else {
                $packageCode = $token;
            }
        } elseif (count($parts) === 3) {
            $token1 = trim($parts[1]);
            $token2 = trim($parts[2]);

            // Cek apakah token1 adalah SSID Router ATAU token2 adalah Kode Paket
            // Contoh format: VOUCHER#KAFE_NET#HOTSPOT-1HARI atau VOUCHER#KAFE_NET#FREE
            if ($this->isRouterQuery($token1) || $this->isPackageQuery($token2)) {
                $routerQuery   = $token1;
                $packageCode   = $token2;
                $requesterName = $senderName ?: 'Pengunjung';
            } else {
                // Contoh format: VOUCHER#HOTSPOT-1HARI#Budi
                $packageCode   = $token1;
                $requesterName = trim($rawParts[2]) ?: ($senderName ?: 'Pengunjung');
            }
        } else {
            // Format 4 bagian atau lebih: VOUCHER#SSID_OR_ROUTER#KODE_PAKET#NAMA_ANDA...
            $routerQuery   = trim($parts[1]);
            $packageCode   = trim($parts[2]);
            $requesterName = trim(implode(' ', array_slice(array_map('trim', $rawParts), 3))) ?: ($senderName ?: 'Pengunjung');
        }

        if (empty($requesterName)) {
            $requesterName = 'Pengunjung';
        }

        return $this->processVoucherRequest($phone, $packageCode, $requesterName, $routerQuery, $rawChatId);
    }

    /**
     * Cari pelanggan aktif berdasarkan nomor telepon.
     */
    protected function findCustomerByPhone(string $phone): ?Customer
    {
        $normalized = $this->wa->normalizePhone($phone);
        $localZero  = str_starts_with($normalized, '62') ? '0' . substr($normalized, 2) : null;

        return Customer::where('status', 'active')
            ->where(function ($q) use ($normalized, $localZero, $phone) {
                $q->where('phone', $normalized)
                  ->orWhere('phone', $phone);
                if ($localZero) {
                    $q->orWhere('phone', $localZero);
                }
            })
            ->first();
    }

    /**
     * Handle permintaan cek saldo dari pelanggan.
     */
    protected function handleCheckBalance(string $phone, ?string $rawChatId = null)
    {
        $customer = $this->findCustomerByPhone($phone);

        if (!$customer) {
            $this->wa->sendMessage(
                $phone,
                "❌ *Nomor Belum Terdaftar*\n\nNomor WhatsApp Anda ({$phone}) belum terdaftar sebagai pelanggan aktif MANTIK.\n\nSilakan hubungi admin kami untuk pendaftaran dan aktivasi layanan.",
                $rawChatId
            );
            return response()->json(['status' => 'customer_not_found']);
        }

        $balanceFormatted = number_format($customer->balance, 0, ',', '.');
        $activePackage = $customer->active_package;
        $packageName = $activePackage ? $activePackage->name : 'Tidak ada paket langganan aktif';

        $message  = "👤 *Informasi Pelanggan MANTIK*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Nama: *{$customer->name}*\n";
        $message .= "Nomor WA: *{$customer->phone}*\n";
        $message .= "💰 Saldo Deposit: *Rp {$balanceFormatted}*\n";
        $message .= "📦 Paket Langganan: {$packageName}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📱 Ketik *PAKET* untuk melihat daftar paket voucher hotspot yang dapat Anda beli dengan saldo.";

        $this->wa->sendMessage($phone, $message, $rawChatId);
        return response()->json([
            'status'  => 'success',
            'balance' => (float) $customer->balance,
            'name'    => $customer->name,
        ]);
    }

    /**
     * Handle konfirmasi pembelian voucher dengan saldo (Two-step verification).
     */
    protected function handleOrderConfirmation(string $phone, string $messageBody, ?string $rawChatId = null)
    {
        $cacheKey = "wa_voucher_order:{$phone}";
        $order = Cache::get($cacheKey);

        if (!$order) {
            return null;
        }

        $bodyClean = strtoupper(trim($messageBody));

        // 1. Tangani pembatalan pesanan
        if ($bodyClean === 'BATAL') {
            Cache::forget($cacheKey);
            $this->wa->sendMessage(
                $phone,
                "✅ Permintaan pembelian voucher hotspot telah dibatalkan. Saldo deposit Anda aman dan tidak terpotong.",
                $rawChatId
            );
            return response()->json(['status' => 'order_cancelled']);
        }

        // 2. Validasi format kode konfirmasi: "YA <KODE>", "YA<KODE>", atau langsung "<KODE>"
        $expectedCode = (string) $order['code'];
        $validFormats = [
            "YA {$expectedCode}",
            "YA{$expectedCode}",
            $expectedCode,
        ];

        // Jika user hanya membalas YA atau memasukkan kode yang salah
        if (!in_array($bodyClean, $validFormats)) {
            // Jika pesan tidak menyerupai konfirmasi (misal mengetik pesan lain), cek apakah diawali YA atau angka
            $isAttempt = str_starts_with($bodyClean, 'YA') || preg_match('/^\d{3,6}$/', $bodyClean);

            if ($isAttempt) {
                $order['attempts'] = ($order['attempts'] ?? 0) + 1;
                if ($order['attempts'] >= 3) {
                    Cache::forget($cacheKey);
                    $this->wa->sendMessage(
                        $phone,
                        "❌ *Batas Percobaan Habis*\n\nKode konfirmasi salah sebanyak 3 kali. Sesi pembelian voucher dibatalkan demi keamanan saldo Anda.",
                        $rawChatId
                    );
                    return response()->json(['status' => 'max_attempts_exceeded']);
                }

                Cache::put($cacheKey, $order, now()->addMinutes(2));
                $this->wa->sendMessage(
                    $phone,
                    "❌ Kode konfirmasi salah.\n\nSilakan balas *YA {$expectedCode}* untuk memproses, atau ketik *BATAL* untuk membatalkan.",
                    $rawChatId
                );
                return response()->json(['status' => 'invalid_confirmation_code']);
            }

            // Jika bukan format konfirmasi sama sekali, abaikan agar alur normal (misal ketik INFO) bisa berjalan
            return null;
        }

        // 3. Eksekusi Pembelian dengan Pessimistic Lock & Database Transaction
        DB::beginTransaction();
        try {
            $lockedCustomer = Customer::where('id', $order['customer_id'])
                ->lockForUpdate()
                ->first();

            if (!$lockedCustomer || (float) $lockedCustomer->balance < (float) $order['price']) {
                DB::rollBack();
                Cache::forget($cacheKey);
                $this->wa->sendMessage(
                    $phone,
                    "❌ *Saldo Tidak Mencukupi*\n\nTransaksi gagal karena saldo deposit Anda tidak mencukupi untuk menyelesaikan transaksi ini.",
                    $rawChatId
                );
                return response()->json(['status' => 'insufficient_balance']);
            }

            $package = Package::find($order['package_id']);
            if (!$package) {
                DB::rollBack();
                Cache::forget($cacheKey);
                $this->wa->sendMessage($phone, "❌ Paket tidak ditemukan atau sudah tidak aktif.", $rawChatId);
                return response()->json(['status' => 'package_not_found']);
            }

            // Cari voucher yang tersedia untuk router & paket ini
            $voucherQuery = HotspotVoucher::with('router')
                ->where('package_id', $order['package_id'])
                ->where('is_used', false)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });

            if (!empty($order['router_id'])) {
                $voucherQuery->where('router_id', $order['router_id']);
            }

            $voucher = $voucherQuery->lockForUpdate()->first();

            if (!$voucher) {
                DB::rollBack();
                Cache::forget($cacheKey);
                $this->wa->sendMessage(
                    $phone,
                    "❌ *Stok Voucher Habis*\n\nMaaf, stok voucher untuk paket *{$package->name}* baru saja habis. Saldo deposit Anda aman dan tidak terpotong.",
                    $rawChatId
                );
                return response()->json(['status' => 'out_of_stock']);
            }

            // 1. Potong Saldo Customer
            $lockedCustomer->decrement('balance', $order['price']);

            // 2. Tandai Voucher Terpakai
            $expiresAt = now()->addDays($package->duration_days);
            $voucher->update([
                'is_used'    => true,
                'used_by'    => $lockedCustomer->name . ' (' . $phone . ')',
                'used_at'    => now(),
                'expires_at' => $expiresAt,
            ]);

            // 3. Catat di WaVoucherRequest
            WaVoucherRequest::create([
                'phone'          => $phone,
                'customer_id'    => $lockedCustomer->id,
                'requester_name' => $order['requester_name'] ?: $lockedCustomer->name,
                'package_code'   => strtoupper($order['package_slug']),
                'package_id'     => $package->id,
                'voucher_id'     => $voucher->id,
                'voucher_code'   => $voucher->code,
                'price_deducted' => $order['price'],
                'status'         => 'success',
                'raw_message'    => "CONFIRM_BUY#{$expectedCode}",
            ]);

            // Hapus cache sesi pesanan
            Cache::forget($cacheKey);

            DB::commit();

            // Ambil data router & brand hotspot
            $router = $voucher->router;
            $ssid = $router?->ssid ?: $router?->name;
            $brandName = Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot'));

            $durationLabel = $package->duration_days >= 1
                ? $package->duration_days . ' Hari'
                : ($package->duration_days * 24) . ' Jam';

            // Kirim voucher ke pelanggan
            $this->wa->sendVoucher($phone, [
                'name'         => $lockedCustomer->name,
                'code'         => $voucher->code,
                'package_name' => $package->name,
                'speed'        => "{$package->bandwidth_up}/{$package->bandwidth_down} Mbps",
                'duration'     => $durationLabel,
                'expires_at'   => $expiresAt->toDateTimeString(),
                'price'        => $order['price'],
                'router_name'  => $router?->name,
                'ssid'         => $ssid,
                'brand_name'   => $brandName,
            ], $rawChatId);

            // Kirim notifikasi konfirmasi saldo terpotong
            $remainingBalance = number_format($lockedCustomer->balance, 0, ',', '.');
            $priceFormatted = number_format($order['price'], 0, ',', '.');
            $this->wa->sendMessage(
                $phone,
                "💰 *Pembayaran Saldo Berhasil*\nSaldo deposit Anda terpotong: *Rp {$priceFormatted}*\nSisa saldo saat ini: *Rp {$remainingBalance}*.",
                $rawChatId
            );

            // Kirim notifikasi admin jika disetel
            $adminPhone = Setting::get('whatsapp_admin_phone') ?: config('whatsapp.admin_phone');
            if ($adminPhone) {
                $this->wa->sendAdminNotification($adminPhone, [
                    'requester_name' => $lockedCustomer->name . " (Pelanggan Saldo)",
                    'phone'          => $phone,
                    'package_name'   => $package->name,
                    'voucher_code'   => $voucher->code,
                    'ssid'           => $ssid,
                    'router_name'    => $router?->name,
                ]);
            }

            Log::info("[WA Webhook] Pembelian voucher berbayar sukses: {$voucher->code} oleh customer ID {$lockedCustomer->id} ({$phone})");

            return response()->json([
                'status'  => 'success',
                'voucher' => $voucher->code,
                'balance' => (float) $lockedCustomer->balance,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[WA Webhook] Gagal konfirmasi voucher berbayar: ' . $e->getMessage(), [
                'phone' => $phone,
                'order' => $order,
            ]);

            $this->wa->sendMessage(
                $phone,
                "❌ Terjadi kesalahan sistem saat memproses pemotongan saldo. Saldo Anda aman dan tidak terpotong.",
                $rawChatId
            );

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle permintaan INFO paket.
     */
    protected function handleInfoRequest(string $phone, ?string $rawChatId = null)
    {
        $packages = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        if ($packages->isEmpty()) {
            $this->wa->sendMessage($phone, "❌ Maaf, saat ini tidak ada paket hotspot yang tersedia. Silakan hubungi admin.", $rawChatId);
            return response()->json(['status' => 'no_packages']);
        }

        $packageList = $packages->map(function ($pkg) {
            $hours = $pkg->duration_days * 24;
            $duration = $pkg->duration_days >= 1
                ? $pkg->duration_days . ' Hari'
                : $hours . ' Jam';

            return [
                'code'     => strtoupper($pkg->slug),
                'name'     => $pkg->name,
                'duration' => $duration,
                'speed'    => "{$pkg->bandwidth_up}/{$pkg->bandwidth_down} Mbps",
                'price'    => $pkg->price,
            ];
        })->toArray();

        $this->wa->sendPackageMenu($phone, $packageList, null, $rawChatId);
        return response()->json(['status' => 'menu_sent']);
    }

    /**
     * Proses permintaan voucher dari pengunjung / non-pelanggan.
     */
    protected function processVoucherRequest(string $phone, string $packageCode, string $requesterName, ?string $routerQuery = null, ?string $rawChatId = null)
    {
        // 1. Tentukan Router jika routerQuery disertakan (misal SSID atau nama router)
        $targetRouter = null;
        if (!empty($routerQuery)) {
            $targetRouter = Router::where('is_active', true)
                ->where(function ($q) use ($routerQuery) {
                    $q->where('id', $routerQuery)
                      ->orWhereRaw('UPPER(ssid) = ?', [strtoupper($routerQuery)])
                      ->orWhereRaw('UPPER(name) = ?', [strtoupper($routerQuery)])
                      ->orWhere('ssid', 'like', "%{$routerQuery}%")
                      ->orWhere('name', 'like', "%{$routerQuery}%");
                })
                ->first();

            if (!$targetRouter) {
                $this->wa->sendMessage(
                    $phone,
                    "❌ *Lokasi WiFi / Router Tidak Ditemukan*\n\nTidak ditemukan WiFi hotspot dengan nama/kode *{$routerQuery}*.\n\nSilakan periksa nama WiFi yang tertera pada banner lokasi Anda atau ketik *PAKET*.",
                    $rawChatId
                );
                return response()->json(['status' => 'router_not_found']);
            }
        }

        // 2. Cari paket hotspot
        $package = null;
        $isFreeKeyword = in_array(strtoupper($packageCode), ['FREE', 'GRATIS']);

        if ($isFreeKeyword) {
            // 1. Cek paket event gratis yang disetel di Pengaturan
            $eventPkgId = Setting::get('whatsapp_event_package_id');
            if ($eventPkgId) {
                $package = Package::where('type', 'hotspot')->where('is_active', true)->find($eventPkgId);
            }

            // 2. Jika tidak disetel, cari paket yang slug atau namanya eksplisit mengandung 'FREE'
            if (!$package) {
                $package = Package::where('type', 'hotspot')
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereRaw('UPPER(slug) = ?', ['FREE'])
                          ->orWhereRaw('UPPER(name) = ?', ['FREE'])
                          ->orWhereRaw('UPPER(slug) LIKE ?', ['%FREE%'])
                          ->orWhereRaw('UPPER(name) LIKE ?', ['%FREE%']);
                    })
                    ->first();
            }

            // 3. Jika belum ada paket berlabel free, cari paket berharga 0
            if (!$package) {
                $package = Package::where('type', 'hotspot')
                    ->where('is_active', true)
                    ->where('price', '<=', 0)
                    ->first();
            }

            // 4. Fallback: ambil paket termurah/pertama
            if (!$package) {
                $package = Package::where('type', 'hotspot')
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->first();
            }
        } else {
            $package = Package::where('type', 'hotspot')
                ->where('is_active', true)
                ->where(function ($q) use ($packageCode) {
                    $q->whereRaw('UPPER(slug) = ?', [strtoupper($packageCode)])
                      ->orWhereRaw('UPPER(name) = ?', [strtoupper($packageCode)]);
                })
                ->first();
        }

        if (!$package) {
            $availablePackages = $this->getAvailablePackagesFormatted();
            $this->wa->sendErrorMessage(
                $phone,
                "Paket *{$packageCode}* tidak ditemukan.",
                $availablePackages,
                null,
                $rawChatId
            );

            // Log request gagal
            WaVoucherRequest::create([
                'phone'          => $phone,
                'requester_name' => $requesterName,
                'package_code'   => $packageCode,
                'status'         => 'failed',
                'failure_reason' => 'Package not found: ' . $packageCode,
                'raw_message'    => "VOUCHER#" . ($routerQuery ? "{$routerQuery}#" : "") . "{$packageCode}#{$requesterName}",
            ]);

            return response()->json(['status' => 'package_not_found']);
        }

        // 3. Cek Status Paket Gratis / Mode Event
        $eventMode = (bool) Setting::get('whatsapp_event_mode', false);
        $eventPackageId = Setting::get('whatsapp_event_package_id');

        $isFree = $isFreeKeyword
            || ((float)$package->price <= 0)
            || str_contains(strtolower($package->slug), 'free')
            || str_contains(strtolower($package->name), 'free')
            || ($eventMode && (!$eventPackageId || $eventPackageId == $package->id));

        // 4. Pembatasan Request & Verifikasi Akun Saldo Pelanggan
        if ($isFree) {
            // BATAS VOUCHER GRATIS: 1 nomor WA hanya bisa menerima 1 voucher gratis dalam satu hari (sejak jam 00:00:00 hari ini)
            $claimedToday = WaVoucherRequest::where('phone', $phone)
                ->where('status', 'success')
                ->where('created_at', '>=', now()->startOfDay())
                ->where(function ($q) use ($package, $eventMode) {
                    $q->where('package_id', $package->id)
                      ->orWhere('package_code', 'FREE')
                      ->orWhere('package_code', 'GRATIS')
                      ->orWhereHas('package', function ($pq) {
                          $pq->where('price', '<=', 0)
                             ->orWhere('slug', 'like', '%free%')
                             ->orWhere('name', 'like', '%free%');
                      });
                    if ($eventMode) {
                        $q->orWhereNotNull('id');
                    }
                })
                ->exists();

            if ($claimedToday) {
                $this->wa->sendMessage(
                    $phone,
                    "⏳ *Batas Voucher Gratis Harian*\n\nNomor WhatsApp Anda ({$phone}) sudah mengklaim 1 voucher gratis hari ini.\n\nSetiap nomor hanya berhak mendapatkan *1 voucher gratis dalam satu hari*.\nSilakan coba lagi besok! 🙏",
                    $rawChatId
                );
                return response()->json(['status' => 'daily_limit_reached']);
            }
        } else {
            // PAKET BERBAYAR: Wajib diverifikasi nomor WhatsApp sesuai data Customer terdaftar yang memiliki saldo
            $customer = $this->findCustomerByPhone($phone);

            if (!$customer) {
                $priceFormatted = number_format($package->price, 0, ',', '.');
                $this->wa->sendMessage(
                    $phone,
                    "❌ *Pelanggan Belum Terdaftar*\n\nPaket *{$package->name}* seharga *Rp {$priceFormatted}* hanya dapat dibeli oleh pelanggan MANTIK menggunakan saldo deposit.\n\nNomor WhatsApp Anda ({$phone}) belum terdaftar sebagai pelanggan aktif kami. Silakan hubungi admin kami untuk pendaftaran dan pengisian saldo deposit.",
                    $rawChatId
                );

                WaVoucherRequest::create([
                    'phone'          => $phone,
                    'requester_name' => $requesterName,
                    'package_code'   => strtoupper($package->slug),
                    'package_id'     => $package->id,
                    'status'         => 'failed',
                    'failure_reason' => 'Customer not registered or inactive',
                    'raw_message'    => "VOUCHER#" . ($routerQuery ? "{$routerQuery}#" : "") . "{$packageCode}#{$requesterName}",
                ]);

                return response()->json(['status' => 'customer_not_found']);
            }

            // Cek kecukupan saldo customer
            if ((float) $customer->balance < (float) $package->price) {
                $priceFormatted = number_format($package->price, 0, ',', '.');
                $balanceFormatted = number_format($customer->balance, 0, ',', '.');

                $this->wa->sendMessage(
                    $phone,
                    "❌ *Saldo Tidak Mencukupi*\n\nHalo *{$customer->name}*,\nSaldo deposit Anda tidak mencukupi untuk membeli paket ini.\n\n"
                    . "📦 Paket: *{$package->name}*\n"
                    . "💰 Harga Paket: *Rp {$priceFormatted}*\n"
                    . "💳 Saldo Anda: *Rp {$balanceFormatted}*\n\n"
                    . "Silakan lakukan isi ulang (top-up) saldo Anda melalui admin kami.",
                    $rawChatId
                );

                WaVoucherRequest::create([
                    'phone'          => $phone,
                    'customer_id'    => $customer->id,
                    'requester_name' => $customer->name,
                    'package_code'   => strtoupper($package->slug),
                    'package_id'     => $package->id,
                    'status'         => 'failed',
                    'failure_reason' => 'Insufficient balance: need ' . $package->price . ', has ' . $customer->balance,
                    'raw_message'    => "VOUCHER#" . ($routerQuery ? "{$routerQuery}#" : "") . "{$packageCode}#{$requesterName}",
                ]);

                return response()->json(['status' => 'insufficient_balance']);
            }

            // Cek ketersediaan stok voucher di database sebelum membuat sesi konfirmasi
            $voucherQuery = HotspotVoucher::where('package_id', $package->id)
                ->where('is_used', false)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });

            if ($targetRouter) {
                $voucherQuery->where('router_id', $targetRouter->id);
            }

            if (!$voucherQuery->exists()) {
                $locationInfo = $targetRouter ? " untuk WiFi *{$targetRouter->ssid}*" : "";
                $this->wa->sendMessage(
                    $phone,
                    "❌ *Stok Voucher Habis*\n\nMaaf, stok voucher untuk paket *{$package->name}*{$locationInfo} sedang kosong.\n\nSaldo deposit Anda aman dan belum terpotong.",
                    $rawChatId
                );
                return response()->json(['status' => 'out_of_stock']);
            }

            // Buat kode verifikasi/konfirmasi acak 4 digit
            $confirmCode = (string) random_int(1000, 9999);
            $cacheKey = "wa_voucher_order:{$phone}";

            Cache::put($cacheKey, [
                'customer_id'    => $customer->id,
                'customer_name'  => $customer->name,
                'package_id'     => $package->id,
                'package_name'   => $package->name,
                'package_slug'   => $package->slug,
                'router_id'      => $targetRouter?->id,
                'price'          => (float) $package->price,
                'code'           => $confirmCode,
                'attempts'       => 0,
                'requester_name' => $requesterName ?: $customer->name,
            ], now()->addMinutes(3));

            $priceFormatted   = number_format($package->price, 0, ',', '.');
            $balanceFormatted = number_format($customer->balance, 0, ',', '.');
            $estSisaFormatted = number_format($customer->balance - $package->price, 0, ',', '.');
            $locationNotice   = $targetRouter ? "\n📡 WiFi / Lokasi: *{$targetRouter->ssid}*" : "";

            $confirmMessage  = "⚠️ *Konfirmasi Pembelian Voucher*\n";
            $confirmMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $confirmMessage .= "Halo, *{$customer->name}*!\n";
            $confirmMessage .= "Anda akan membeli voucher hotspot:{$locationNotice}\n";
            $confirmMessage .= "• 📦 Paket: *{$package->name}*\n";
            $confirmMessage .= "• 💰 Harga: *Rp {$priceFormatted}*\n";
            $confirmMessage .= "• 💳 Saldo Anda saat ini: *Rp {$balanceFormatted}*\n";
            $confirmMessage .= "• 💵 Sisa saldo setelah beli: *Rp {$estSisaFormatted}*\n";
            $confirmMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $confirmMessage .= "👉 Balas *YA {$confirmCode}* untuk memproses pemotongan saldo.\n";
            $confirmMessage .= "👉 Balas *BATAL* untuk membatalkan.\n\n";
            $confirmMessage .= "⏳ _Konfirmasi ini berlaku selama 3 menit._";

            $this->wa->sendMessage($phone, $confirmMessage, $rawChatId);

            return response()->json([
                'status'       => 'confirmation_required',
                'confirm_code' => $confirmCode,
                'price'        => (float) $package->price,
            ]);
        }

        // Cari voucher yang tersedia di database (dengan relasi router)
        $voucherQuery = HotspotVoucher::with('router')
            ->where('package_id', $package->id)
            ->where('is_used', false)
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });

        if ($targetRouter) {
            $voucherQuery->where('router_id', $targetRouter->id);
        }

        $voucher = $voucherQuery->lockForUpdate()->first();

        if (!$voucher) {
            // Tidak ada stok voucher
            $locationInfo = $targetRouter ? " untuk WiFi *{$targetRouter->ssid}*" : "";
            $this->wa->sendMessage(
                $phone,
                "❌ *Voucher Tidak Tersedia*\n\nMaaf, stok voucher untuk paket *{$package->name}*{$locationInfo} sedang habis.\n\nSilakan coba paket lain atau hubungi admin kami.",
                $rawChatId
            );

            WaVoucherRequest::create([
                'phone'          => $phone,
                'requester_name' => $requesterName,
                'package_code'   => strtoupper($package->slug),
                'package_id'     => $package->id,
                'status'         => 'failed',
                'failure_reason' => 'No voucher stock',
                'raw_message'    => "VOUCHER#" . ($routerQuery ? "{$routerQuery}#" : "") . "{$packageCode}#{$requesterName}",
            ]);

            return response()->json(['status' => 'out_of_stock']);
        }

        DB::beginTransaction();
        try {
            // Tentukan masa berlaku berdasarkan paket
            $expiresAt = now()->addDays($package->duration_days);

            // Tandai voucher sebagai terpakai
            $voucher->update([
                'is_used'  => true,
                'used_by'  => $phone,
                'used_at'  => now(),
                'expires_at' => $expiresAt,
            ]);

            // Catat request voucher via WA
            $waRequest = WaVoucherRequest::create([
                'phone'          => $phone,
                'requester_name' => $requesterName,
                'package_code'   => strtoupper($package->slug),
                'package_id'     => $package->id,
                'voucher_id'     => $voucher->id,
                'voucher_code'   => $voucher->code,
                'status'         => 'success',
                'raw_message'    => "VOUCHER#" . ($routerQuery ? "{$routerQuery}#" : "") . "{$packageCode}#{$requesterName}",
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WA Webhook] Gagal memproses voucher: ' . $e->getMessage(), [
                'phone'   => $phone,
                'package' => $packageCode,
            ]);

            $this->wa->sendMessage(
                $phone,
                "❌ Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin.",
                $rawChatId
            );

            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        // Ambil data router & brand hotspot
        $router = $voucher->router;
        $ssid = $router?->ssid ?: $router?->name;
        $brandName = Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot'));

        // Hitung durasi display
        $durationLabel = $package->duration_days >= 1
            ? $package->duration_days . ' Hari'
            : ($package->duration_days * 24) . ' Jam';

        // Kirim voucher ke pengunjung
        $this->wa->sendVoucher($phone, [
            'name'         => $requesterName,
            'code'         => $voucher->code,
            'package_name' => $package->name,
            'speed'        => "{$package->bandwidth_up}/{$package->bandwidth_down} Mbps",
            'duration'     => $durationLabel,
            'expires_at'   => $expiresAt->toDateTimeString(),
            'price'        => $package->price,
            'router_name'  => $router?->name,
            'ssid'         => $ssid,
            'brand_name'   => $brandName,
        ], $rawChatId);

        // Kirim notifikasi ke admin (jika dikonfigurasi)
        $adminPhone = Setting::get('whatsapp_admin_phone') ?: config('whatsapp.admin_phone');
        if ($adminPhone) {
            $this->wa->sendAdminNotification($adminPhone, [
                'requester_name' => $requesterName,
                'phone'          => $phone,
                'package_name'   => $package->name,
                'voucher_code'   => $voucher->code,
                'ssid'           => $ssid,
                'router_name'    => $router?->name,
            ]);
        }

        Log::info('[WA Webhook] Voucher berhasil dikirim ke ' . $phone, [
            'voucher'  => $voucher->code,
            'package'  => $package->name,
        ]);

        return response()->json([
            'status'  => 'success',
            'voucher' => $voucher->code,
            'package' => $package->name,
            'phone'   => $phone,
        ]);
    }

    /**
     * Mendapatkan daftar paket yang tersedia dalam format array.
     */
    protected function getAvailablePackagesFormatted(): array
    {
        return Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(fn($pkg) => [
                'code'  => strtoupper($pkg->slug),
                'name'  => $pkg->name,
                'price' => $pkg->price,
            ])
            ->toArray();
    }

    /**
     * Cek apakah query cocok dengan SSID atau nama router aktif.
     */
    protected function isRouterQuery(?string $query): bool
    {
        if (empty($query)) {
            return false;
        }

        return Router::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('id', $query)
                  ->orWhereRaw('UPPER(ssid) = ?', [strtoupper($query)])
                  ->orWhereRaw('UPPER(name) = ?', [strtoupper($query)])
                  ->orWhere('ssid', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->exists();
    }

    /**
     * Cek apakah query cocok dengan paket hotspot aktif atau kode FREE.
     */
    protected function isPackageQuery(?string $query): bool
    {
        if (empty($query)) {
            return false;
        }

        if (in_array(strtoupper($query), ['FREE', 'GRATIS'])) {
            return true;
        }

        return Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->whereRaw('UPPER(slug) = ?', [strtoupper($query)])
                  ->orWhereRaw('UPPER(name) = ?', [strtoupper($query)]);
            })
            ->exists();
    }
}
