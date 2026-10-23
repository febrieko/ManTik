<?php

namespace App\Services;

use App\Models\Setting;
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
        $this->loadConfiguration();
    }

    /**
     * Muat konfigurasi dari database Setting (prioritas utama) atau config .env (fallback).
     */
    public function loadConfiguration(): void
    {
        $this->apiUrl      = (string) (Setting::get('whatsapp_api_url') ?: config('whatsapp.api_url', ''));
        $this->apiKey      = (string) (Setting::get('whatsapp_api_key') ?: config('whatsapp.api_key', ''));
        $this->gatewayType = (string) (Setting::get('whatsapp_gateway_type') ?: config('whatsapp.gateway_type', 'auto'));
        $this->session     = (string) (Setting::get('whatsapp_session') ?: config('whatsapp.session', 'default'));
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
     * Kirim pesan WhatsApp ke nomor atau chatId tertentu.
     */
    public function sendMessage(string $to, string $message, ?string $targetChatId = null): array
    {
        // Normalisasi nomor telepon (hilangkan karakter non-digit, tambah 62 jika perlu)
        $phone = $this->normalizePhone($to);
        $endpoint = $this->getEndpointUrl();

        try {
            if ($this->isOpenWa()) {
                // Tentukan chatId OpenWA yang tepat:
                // 1. Jika targetChatId disediakan (misal format @lid atau @c.us dari webhook)
                // 2. Jika $to sendiri sudah mengandung @lid atau @c.us
                // 3. Fallback standar ke {phone}@c.us
                if (!empty($targetChatId) && (str_contains($targetChatId, '@lid') || str_contains($targetChatId, '@c.us') || str_contains($targetChatId, '@s.whatsapp.net'))) {
                    $chatId = $targetChatId;
                } elseif (str_contains($to, '@lid') || str_contains($to, '@c.us') || str_contains($to, '@s.whatsapp.net')) {
                    $chatId = $to;
                } else {
                    $chatId = str_ends_with($phone, '@c.us') ? $phone : "{$phone}@c.us";
                }

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

                // Jika pengiriman ke chatId awal gagal dan kita memiliki alternatif nomor telepon (misal @lid gagal, coba @c.us atau sebaliknya)
                if (!$response->successful() && !empty($phone) && !str_ends_with($chatId, "{$phone}@c.us")) {
                    $altChatId = "{$phone}@c.us";
                    Log::info("[WhatsApp] Mencoba kirim ulang ke chatId alternatif: {$altChatId}");
                    $retryResponse = Http::timeout(15)
                        ->withHeaders([
                            'X-API-Key'    => $this->apiKey,
                            'Content-Type' => 'application/json',
                            'Accept'       => 'application/json',
                        ])
                        ->post($endpoint, [
                            'chatId' => $altChatId,
                            'text'   => $message,
                        ]);

                    if ($retryResponse->successful()) {
                        $response = $retryResponse;
                    }
                }
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

            Log::info('[WhatsApp] Pesan diproses ke ' . ($phone ?: $to), [
                'status'   => $response->status(),
                'success'  => $success,
                'endpoint' => $endpoint,
                'response' => $body,
            ]);

            return [
                'success'  => $success,
                'phone'    => $phone ?: $to,
                'response' => $body,
                'error'    => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Gagal kirim pesan ke ' . ($phone ?: $to) . ': ' . $e->getMessage());

            return [
                'success' => false,
                'phone'   => $phone ?: $to,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim voucher hotspot ke pengunjung.
     */
    public function sendVoucher(string $to, array $voucherData, ?string $targetChatId = null): array
    {
        $message = $this->buildVoucherMessage($voucherData);
        return $this->sendMessage($to, $message, $targetChatId);
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
    public function sendErrorMessage(string $to, string $reason, ?array $availablePackages = null, ?string $brandName = null, ?string $targetChatId = null): array
    {
        $brand = $brandName ?: Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot'));

        $message  = "📶 *{$brand}*\n";
        $message .= "❌ *Permintaan Voucher Gagal*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Alasan: {$reason}\n\n";
        $message .= "📋 *Format yang benar:*\n";
        $message .= "`VOUCHER#KODE_PAKET#NAMA_ANDA`\n";
        $message .= "Contoh:\n`VOUCHER#1JAM#Budi`\n\n";
        $message .= "Atau untuk lokasi/WiFi spesifik:\n";
        $message .= "`VOUCHER#NAMA_SSID#KODE_PAKET#NAMA_ANDA`\n\n";

        if ($availablePackages && count($availablePackages) > 0) {
            $message .= "📦 *Paket tersedia:*\n";
            foreach ($availablePackages as $pkg) {
                $price = number_format($pkg['price'], 0, ',', '.');
                $message .= "• *{$pkg['code']}* - {$pkg['name']} (Rp {$price})\n";
            }
        }

        $message .= "\nHubungi admin jika ada pertanyaan.";

        return $this->sendMessage($to, $message, $targetChatId);
    }

    /**
     * Kirim menu daftar paket ke pengunjung.
     */
    public function sendPackageMenu(string $to, array $packages, ?string $brandName = null, ?string $targetChatId = null): array
    {
        $brand = $brandName ?: Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot'));

        $message  = "📶 *{$brand}*\n";
        $message .= "Selamat Datang di Hotspot!\n";
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
        $message .= "Ketik: `VOUCHER#KODE_PAKET#NAMA`\n";
        $message .= "Contoh: `VOUCHER#1JAM#Budi`\n\n";
        $message .= "Atau untuk WiFi/lokasi spesifik:\n";
        $message .= "Ketik: `VOUCHER#SSID#KODE_PAKET#NAMA`";

        return $this->sendMessage($to, $message, $targetChatId);
    }

    /**
     * Build pesan voucher yang akan dikirim ke pengunjung.
     */
    public function buildVoucherMessage(array $data): string
    {
        $expiresAt = isset($data['expires_at'])
            ? \Carbon\Carbon::parse($data['expires_at'])->locale('id')->isoFormat('D MMMM YYYY, HH:mm')
            : '-';

        $brandName = !empty($data['brand_name']) 
            ? $data['brand_name'] 
            : Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot'));

        $ssid = !empty($data['ssid']) 
            ? $data['ssid'] 
            : (!empty($data['router_name']) ? $data['router_name'] : 'Hotspot');

        $message  = "📶 *{$brandName}*\n";
        $message .= "✅ *VOUCHER HOTSPOT AKTIF*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Halo, *{$data['name']}*!\n";
        $message .= "Berikut adalah kode akses internet Anda:\n\n";
        $message .= "🎟 *Kode Voucher:*\n";
        $message .= "```{$data['code']}```\n";
        $message .= "_(Sentuh/klik kode di atas untuk menyalin)_\n\n";
        $message .= "📋 *Rincian Paket:*\n";
        $message .= "• 📦 Paket: *{$data['package_name']}*\n";
        $message .= "• 🚀 Kecepatan: *{$data['speed']}*\n";
        $message .= "• ⏱ Masa Aktif: *{$data['duration']}*\n";
        $message .= "• 📅 Berlaku hingga: *{$expiresAt}*\n";
        $message .= "• 📡 WiFi / SSID: *{$ssid}*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📱 *Cara Menggunakan:*\n";
        $message .= "1. Sambungkan WiFi ke jaringan *\"{$ssid}\"*\n";
        $message .= "2. Buka browser & halaman login akan otomatis terbuka\n";
        $message .= "3. Masukkan kode voucher di atas\n";
        $message .= "4. Klik Login & selamat menikmati internet! 🌐\n\n";
        $message .= "⚠️ _Simpan pesan ini jika koneksi internet terputus sewaktu-waktu._\n";
        $message .= "_Terimakasih telah menggunakan layanan {$brandName}!_ 🙏";

        return $message;
    }

    /**
     * Build pesan notifikasi admin.
     */
    protected function buildAdminNotificationMessage(array $data): string
    {
        $ssidInfo = !empty($data['ssid']) ? $data['ssid'] : (!empty($data['router_name']) ? $data['router_name'] : '-');
        $phoneDisplay = !empty($data['phone']) ? $data['phone'] : '-';

        $message  = "🔔 *Notifikasi Admin: Permintaan Voucher Baru*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 *Nama*: {$data['requester_name']}\n";
        $message .= "📱 *No. WA*: {$phoneDisplay}\n";
        $message .= "📦 *Paket*: {$data['package_name']}\n";
        $message .= "🎟 *Voucher*: `{$data['voucher_code']}`\n";
        $message .= "📡 *WiFi / SSID*: *{$ssidInfo}*\n";
        $message .= "⏰ *Waktu*: " . now()->locale('id')->isoFormat('D MMM YYYY, HH:mm') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "ℹ️ _Pesan ini adalah laporan untuk Admin. Rincian voucher lengkap beserta panduan login hotspot telah dikirimkan otomatis ke nomor WhatsApp pengunjung._";

        return $message;
    }

    /**
     * Normalisasi nomor telepon ke format internasional Indonesia (diawali 62).
     * Menerima input yang diawali dengan 0, 62, +62, maupun 8.
     */
    public function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Hapus semua non-digit
        $phone = preg_replace('/\D/', '', $phone);

        if (empty($phone)) {
            return '';
        }

        // Jika diawali 0 (misal: 08123456789), ubah 0 di depan jadi 62 -> 628123456789
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9) {
            // Jika diawali 8 (misal: 8123456789) -> 628123456789
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
