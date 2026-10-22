<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotspotVoucher;
use App\Models\Package;
use App\Models\Router;
use App\Models\WaVoucherRequest;
use App\Services\MikrotikService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        // Validasi webhook secret jika dikonfigurasi
        $webhookSecret = config('whatsapp.webhook_secret');
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

        if (is_array($payload) && !empty($payload)) {
            $senderPhone = $payload['from'] ?? $payload['sender']['id'] ?? $payload['chatId'] ?? null;
            $messageBody = $payload['body'] ?? $payload['text'] ?? $payload['message'] ?? null;
            $senderName  = $payload['sender']['pushname'] ?? $payload['sender']['name'] ?? null;
        }

        if (!$senderPhone) {
            $senderRaw = $request->input('sender');
            if (is_array($senderRaw)) {
                $senderPhone = $senderRaw['id'] ?? $senderRaw['phone'] ?? null;
                $senderName  = $senderRaw['pushname'] ?? $senderRaw['name'] ?? null;
            } else {
                $senderPhone = $senderRaw ?? $request->input('from') ?? $request->input('phone');
            }
        }

        if (empty($messageBody)) {
            $messageBody = $request->input('message') ?? $request->input('text') ?? $request->input('body') ?? '';
        }

        $messageBody = trim((string) $messageBody);

        if (!$senderPhone || !$messageBody) {
            return response()->json(['status' => 'ignored', 'reason' => 'empty_message']);
        }

        $phone = $this->wa->normalizePhone($senderPhone);

        Log::info('[WA Webhook] Pesan masuk dari ' . $phone . ': ' . $messageBody);

        // Cek pesan INFO untuk mendapatkan daftar paket
        if (strtoupper($messageBody) === 'INFO' || strtoupper($messageBody) === 'PAKET') {
            return $this->handleInfoRequest($phone);
        }

        // Parse format: VOUCHER#KODE_PAKET#NAMA
        $parts = explode('#', strtoupper($messageBody));

        if (count($parts) < 2 || trim($parts[0]) !== 'VOUCHER') {
            // Pesan tidak dikenali, kirim petunjuk
            $availablePackages = $this->getAvailablePackagesFormatted();
            $this->wa->sendErrorMessage($phone, 'Format pesan tidak valid.', $availablePackages);
            return response()->json(['status' => 'invalid_format']);
        }

        $packageCode = trim($parts[1]);
        // Nama jika diisi setelah pemisah kedua
        $rawParts = explode('#', $messageBody);
        $requesterName = count($rawParts) >= 3 
            ? trim(implode(' ', array_slice(array_map('trim', $rawParts), 2))) 
            : ($senderName ?: 'Pengunjung');

        if (empty($requesterName)) {
            $requesterName = 'Pengunjung';
        }

        return $this->processVoucherRequest($phone, $packageCode, $requesterName);
    }

    /**
     * Handle permintaan INFO paket.
     */
    protected function handleInfoRequest(string $phone)
    {
        $packages = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        if ($packages->isEmpty()) {
            $this->wa->sendMessage($phone, "❌ Maaf, saat ini tidak ada paket hotspot yang tersedia. Silakan hubungi admin.");
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

        $this->wa->sendPackageMenu($phone, $packageList);
        return response()->json(['status' => 'menu_sent']);
    }

    /**
     * Proses permintaan voucher dari pengunjung.
     */
    protected function processVoucherRequest(string $phone, string $packageCode, string $requesterName)
    {
        // Cek apakah nomor ini sudah pernah request dalam 1 jam terakhir (anti-spam)
        $recentRequest = WaVoucherRequest::where('phone', $phone)
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($recentRequest) {
            $this->wa->sendMessage(
                $phone,
                "⏳ *Permintaan Terlalu Sering*\n\nAnda baru saja mendapatkan voucher. Harap tunggu minimal 1 jam sebelum meminta voucher baru.\n\nHubungi admin jika ada masalah."
            );
            return response()->json(['status' => 'rate_limited']);
        }

        // Cari paket berdasarkan kode (slug)
        $package = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where(function ($q) use ($packageCode) {
                $q->whereRaw('UPPER(slug) = ?', [$packageCode])
                  ->orWhereRaw('UPPER(name) = ?', [$packageCode]);
            })
            ->first();

        if (!$package) {
            $availablePackages = $this->getAvailablePackagesFormatted();
            $this->wa->sendErrorMessage(
                $phone,
                "Paket *{$packageCode}* tidak ditemukan.",
                $availablePackages
            );

            // Log request gagal
            WaVoucherRequest::create([
                'phone'          => $phone,
                'requester_name' => $requesterName,
                'package_code'   => $packageCode,
                'status'         => 'failed',
                'failure_reason' => 'Package not found: ' . $packageCode,
                'raw_message'    => "VOUCHER#{$packageCode}#{$requesterName}",
            ]);

            return response()->json(['status' => 'package_not_found']);
        }

        // Cari voucher yang tersedia di database
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
            // Tidak ada stok voucher
            $this->wa->sendMessage(
                $phone,
                "❌ *Voucher Tidak Tersedia*\n\nMaaf, stok voucher untuk paket *{$package->name}* sedang habis.\n\nSilakan coba paket lain atau hubungi admin kami."
            );

            WaVoucherRequest::create([
                'phone'          => $phone,
                'requester_name' => $requesterName,
                'package_code'   => strtoupper($package->slug),
                'package_id'     => $package->id,
                'status'         => 'failed',
                'failure_reason' => 'No voucher stock',
                'raw_message'    => "VOUCHER#{$packageCode}#{$requesterName}",
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
                'raw_message'    => "VOUCHER#{$packageCode}#{$requesterName}",
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
                "❌ Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin."
            );

            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

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
        ]);

        // Kirim notifikasi ke admin (jika dikonfigurasi)
        $adminPhone = config('whatsapp.admin_phone');
        if ($adminPhone) {
            $this->wa->sendAdminNotification($adminPhone, [
                'requester_name' => $requesterName,
                'phone'          => $phone,
                'package_name'   => $package->name,
                'voucher_code'   => $voucher->code,
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
}
