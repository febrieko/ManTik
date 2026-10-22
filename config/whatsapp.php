<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk WhatsApp Gateway API.
    | Sesuaikan dengan provider gateway yang Anda gunakan.
    |
    */

    // URL endpoint API untuk mengirim pesan
    // Contoh Fonnte: https://api.fonnte.com/send
    // Contoh WaBlas: https://solo.wablas.com/api/send-message
    // Custom API: https://yourdomain.com/api/send
    'api_url' => env('WHATSAPP_API_URL', 'https://api.fonnte.com/send'),

    // API Key / Token untuk autentikasi ke gateway
    'api_key' => env('WHATSAPP_API_KEY', ''),

    // Provider / Gateway Type: 'auto', 'openwa', atau 'fonnte'
    'gateway_type' => env('WHATSAPP_GATEWAY_TYPE', 'auto'),

    // Session name (khusus OpenWA, misal: default)
    'session' => env('WHATSAPP_SESSION', 'default'),

    // Secret token untuk memvalidasi webhook yang masuk dari gateway
    // Kosongkan jika tidak menggunakan validasi secret
    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET', ''),

    // Nomor WhatsApp admin yang akan menerima notifikasi setiap ada request voucher
    // Format: 6281234567890 (tanpa + dan spasi)
    // Kosongkan jika tidak ingin notifikasi ke admin
    'admin_phone' => env('WHATSAPP_ADMIN_PHONE', ''),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Pembatasan request per nomor WhatsApp untuk mencegah penyalahgunaan.
    |
    */

    // Interval minimum (dalam menit) antara permintaan voucher yang berhasil
    // dari satu nomor yang sama.
    'rate_limit_minutes' => env('WA_RATE_LIMIT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Voucher Settings
    |--------------------------------------------------------------------------
    */

    // Panjang kode voucher yang digenerate otomatis
    'voucher_code_length' => env('WA_VOUCHER_CODE_LENGTH', 8),

    // Apakah voucher otomatis dibuat jika stok habis?
    // true  = buat voucher on-demand
    // false = tolak request jika stok habis
    'auto_generate_voucher' => env('WA_AUTO_GENERATE_VOUCHER', false),

];
