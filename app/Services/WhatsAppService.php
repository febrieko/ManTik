<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $gatewayType;
    protected string $session;

    public function __construct()
    {
        $this->apiUrl      = (string) config('whatsapp.api_url', '');
        $this->apiKey      = (string) config('whatsapp.api_key', '');
        $this->gatewayType = (string) config('whatsapp.gateway_type', 'auto');
        $this->session     = (string) config('whatsapp.session', 'default');
    }

    /**
     * Tentukan apakah gateway yang dipakai adalah OpenWA.
     */
    public function isOpenWa(): bool
    {
        if ($this->gatewayType === 'openwa') {
            return true;
        }
        if ($this->gatewayType === 'fonnte') {
            return false;
        }

        // Auto-detect berdasarkan URL
        return str_contains($this->apiUrl, 'openwa') ||
               str_contains($this->apiUrl, 'mitrapedia') ||
               str_contains($this->apiUrl, '/sessions/') ||
               str_contains($this->apiUrl, '/messages/send-text');
    }

    /**
     * Dapatkan endpoint URL yang tepat untuk kirim pesan.
     */
    public function getEndpointUrl(): string
    {
        $url = rtrim($this->apiUrl, '/');

        if ($this->isOpenWa()) {
            // Jika user hanya memasukkan domain misal https://wa.mitrapedia.my.id
            if (!str_contains($url, '/messages/send-text')) {
                $base = $url;
                if (!str_contains($url, '/api')) {
                    $url .= '/api';
                }
                $sessionId = $this->resolveOpenWaSessionId($base);
                $url .= "/sessions/{$sessionId}/messages/send-text";
            }
        }

        return $url;
    }

    /**
     * Dapatkan UUID session OpenWA jika yang diinput adalah nama session (seperti 'mantik').
     */
    public function resolveOpenWaSessionId(string $baseUrl): string
    {
        $session = trim($this->session ?: 'default');

        // Jika sudah berbentuk UUID (misal: 2b376a31-46e8-4255-8aa8-53b329b583ea)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $session)) {
            return $session;
        }

        // Cek cache session UUID
        $cacheKey = 'openwa_session_uuid_' . md5($baseUrl . '_' . $session);
        if ($cachedId = cache($cacheKey)) {
            return $cachedId;
        }

        try {
            $sessionsUrl = rtrim($baseUrl, '/') . (str_contains($baseUrl, '/api') ? '' : '/api') . '/sessions';
            $response = Http::timeout(5)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get($sessionsUrl);

            if ($response->successful()) {
                $sessions = $response->json();
                if (is_array($sessions)) {
                    // 1. Cari session yang namanya sama persis
                    foreach ($sessions as $s) {
                        if (isset($s['name']) && strtolower($s['name']) === strtolower($session)) {
                            cache([$cacheKey => $s['id']], now()->addHours(6));
                            return $s['id'];
                        }
                    }

                    // 2. Jika tidak ditemukan nama yang sama, cari yang ready/connected
                    foreach ($sessions as $s) {
                        if (isset($s['status']) && in_array(strtolower($s['status']), ['ready', 'connected'])) {
                            cache([$cacheKey => $s['id']], now()->addHours(6));
                            return $s['id'];
                        }
                    }

                    // 3. Fallback ke session pertama jika ada
                    if (!empty($sessions[0]['id'])) {
                        cache([$cacheKey => $sessions[0]['id']], now()->addHours(6));
                        return $sessions[0]['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('[WhatsApp] Gagal auto-resolve session OpenWA: ' . $e->getMessage());
        }

        return $session;
    }

    /**
     * Kirim pesan WhatsApp ke nomor tertentu.
     */
    public function sendMessage(string $to, string $message): array
    {
        // Normalisasi nomor telepon (hilangkan karakter non-digit, tambah 62 jika perlu)
        $phone = $this->normalizePhone($to);
        $endpoint = $this->getEndpointUrl();

        try {
            if ($this->isOpenWa()) {
                $chatId = str_ends_with($phone, '@c.us') ? $phone : "{$phone}@c.us";
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-API-Key'    => $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ])
                    ->post($endpoint, [
                        'chatId' => $chatId,
                        'text'   => $message,
                    ]);
            } else {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => $this->apiKey,
                    ])
                    ->post($endpoint, [
                        'target'  => $phone,
                        'message' => $message,
                        'token'   => $this->apiKey,
                    ]);
            }

            $body = $response->json();
            $success = $response->successful();

            $errorMessage = null;
            if (!$success) {
                $errorMessage = is_array($body)
                    ? ($body['message'] ?? $body['reason'] ?? $body['error'] ?? ('HTTP ' . $response->status()))
                    : ('HTTP ' . $response->status());
            }

            Log::info('[WhatsApp] Pesan diproses ke ' . $phone, [
                'status'   => $response->status(),
                'success'  => $success,
                'endpoint' => $endpoint,
                'response' => $body,
            ]);

            return [
                'success'  => $success,
                'phone'    => $phone,
                'response' => $body,
                'error'    => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Gagal kirim pesan ke ' . $phone . ': ' . $e->getMessage());

            return [
                'success' => false,
                'phone'   => $phone,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim voucher hotspot ke pengunjung.
     */
    public function sendVoucher(string $to, array $voucherData): array
    {
        $message = $this->buildVoucherMessage($voucherData);
        return $this->sendMessage($to, $message);
    }

    /**
     * Kirim notifikasi ke admin.
     */
    public function sendAdminNotification(string $adminPhone, array $requestData): array
    {
        $message = $this->buildAdminNotificationMessage($requestData);
        return $this->sendMessage($adminPhone, $message);
    }

    /**
     * Kirim pesan error/tidak valid ke pengunjung.
     */
    public function sendErrorMessage(string $to, string $reason, ?array $availablePackages = null): array
    {
        $message  = "❌ *Request Voucher Gagal*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Alasan: {$reason}\n\n";
        $message .= "📋 *Format yang benar:*\n";
        $message .= "`VOUCHER#KODE_PAKET#NAMA_ANDA`\n\n";
        $message .= "Contoh:\n`VOUCHER#1JAM#Budi Santoso`\n\n";

        if ($availablePackages && count($availablePackages) > 0) {
            $message .= "📦 *Paket tersedia:*\n";
            foreach ($availablePackages as $pkg) {
                $price = number_format($pkg['price'], 0, ',', '.');
                $message .= "• *{$pkg['code']}* - {$pkg['name']} (Rp {$price})\n";
            }
        }

        $message .= "\nHubungi admin jika ada pertanyaan.";

        return $this->sendMessage($to, $message);
    }

    /**
     * Kirim menu daftar paket ke pengunjung.
     */
    public function sendPackageMenu(string $to, array $packages): array
    {
        $message  = "📶 *Selamat Datang di Hotspot!*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Silakan pilih paket internet Anda:\n\n";

        foreach ($packages as $pkg) {
            $price = number_format($pkg['price'], 0, ',', '.');
            $duration = $pkg['duration'];
            $speed = $pkg['speed'];
            $message .= "📦 *{$pkg['code']}* - {$pkg['name']}\n";
            $message .= "   ⏱ Durasi: {$duration}\n";
            $message .= "   🚀 Kecepatan: {$speed}\n";
            $message .= "   💰 Harga: Rp {$price}\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📱 *Cara pesan voucher:*\n";
        $message .= "Kirim pesan dengan format:\n";
        $message .= "`VOUCHER#KODE_PAKET#NAMA_ANDA`\n\n";
        $message .= "Contoh:\n`VOUCHER#1JAM#Budi Santoso`";

        return $this->sendMessage($to, $message);
    }

    /**
     * Build pesan voucher yang akan dikirim ke pengunjung.
     */
    protected function buildVoucherMessage(array $data): string
    {
        $expiresAt = isset($data['expires_at'])
            ? \Carbon\Carbon::parse($data['expires_at'])->locale('id')->isoFormat('D MMMM YYYY, HH:mm')
            : '-';

        $message  = "✅ *Voucher Hotspot Berhasil!*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Halo, *{$data['name']}*!\n\n";
        $message .= "🎟 *Kode Voucher:*\n";
        $message .= "```{$data['code']}```\n\n";
        $message .= "📦 Paket: *{$data['package_name']}*\n";
        $message .= "🚀 Kecepatan: *{$data['speed']}*\n";
        $message .= "⏱ Masa Aktif: *{$data['duration']}*\n";
        $message .= "📅 Berlaku hingga: *{$expiresAt}*\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📋 *Cara menggunakan:*\n";
        $message .= "1. Sambungkan WiFi ke jaringan hotspot\n";
        $message .= "2. Buka browser & akan diarahkan ke halaman login\n";
        $message .= "3. Masukkan kode voucher di atas\n";
        $message .= "4. Selamat menikmati internet! 🌐\n\n";
        $message .= "_Terimakasih telah menggunakan layanan kami_ 🙏";

        return $message;
    }

    /**
     * Build pesan notifikasi admin.
     */
    protected function buildAdminNotificationMessage(array $data): string
    {
        $message  = "🔔 *Permintaan Voucher Baru*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 Nama: {$data['requester_name']}\n";
        $message .= "📱 No. WA: {$data['phone']}\n";
        $message .= "📦 Paket: {$data['package_name']}\n";
        $message .= "🎟 Voucher: {$data['voucher_code']}\n";
        $message .= "⏰ Waktu: " . now()->locale('id')->isoFormat('D MMM YYYY, HH:mm') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "✅ Voucher telah dikirim otomatis.";

        return $message;
    }

    /**
     * Normalisasi nomor telepon ke format internasional Indonesia.
     */
    public function normalizePhone(string $phone): string
    {
        // Hapus semua non-digit
        $phone = preg_replace('/\D/', '', $phone);

        // Konversi awalan
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9) {
            $phone = '62' . $phone;
        } elseif (str_starts_with($phone, '+62')) {
            $phone = '62' . substr($phone, 3);
        }

        return $phone;
    }
}
