# MANTIK — Manual Pengembangan Sistem

Dokumen ini adalah panduan teknis resmi bagi pengembang untuk memahami arsitektur, struktur kode, skema database, alur bisnis, integrasi eksternal, penjadwalan (*scheduler*), dan standar pengujian pada sistem **MANTIK** (MikroTik & Network Administration + Customer Billing/Portal).

---

## Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Tech Stack & Dependensi](#2-tech-stack--dependensi)
3. [Instalasi & Setup Pengembangan Lokal](#3-instalasi--setup-pengembangan-lokal)
4. [Struktur Direktori & Arsitektur Kode](#4-struktur-direktori--arsitektur-kode)
5. [Skema Database & Model Inti](#5-skema-database--model-inti)
6. [Autentikasi, Hak Akses (RBAC), & Isolasi Portal](#6-autentikasi-hak-akses-rbac--isolasi-portal)
7. [Alur Bisnis & Integrasi Utama](#7-alur-bisnis--integrasi-utama)
8. [Console Commands & Task Scheduler](#8-console-commands--task-scheduler)
9. [Konfigurasi Lingkungan & Pengaturan Sistem](#9-konfigurasi-lingkungan--pengaturan-sistem)
10. [Standar Pengembangan, Keamanan, & Pengujian](#10-standar-pengembangan-keamanan--pengujian)

---

## 1. Gambaran Umum Sistem

**MANTIK** adalah aplikasi web berbasis Laravel untuk manajemen infrastruktur ISP / RT-RW Net yang menggabungkan:

- **Manajemen MikroTik RouterOS**: Monitoring status router, *traffic/MRTG*, PPPoE, Hotspot, Voucher, Simple Queue, serta IP/DHCP.
- **Manajemen Pelanggan & Ledger Saldo Deposit**: Pengelolaan data pelanggan (Regular maupun Reseller/Agen), satu nomor WhatsApp unik per pelanggan, top-up saldo, penyesuaian saldo, dan buku mutasi (*ledger*).
- **Portal Khusus Pelanggan (`/portal`)**: Halaman *self-service* dengan login Nomor WhatsApp + Password agar pelanggan bisa mengecek saldo, membeli voucher hotspot, membayar tagihan bulanan dengan saldo, dan mengajukan top-up.
- **Sistem Billing & Invoice Bulanan**: Pembuatan tagihan otomatis bulanan untuk layanan PPPoE aktif, pembayaran tunai/transfer/potong saldo deposit, serta pengingat & struk via WhatsApp.
- **Integrasi WhatsApp & Telegram Bot Gateway**: Distribusi voucher hotspot otomatis (termasuk verifikasi saldo pelanggan & konfirmasi 2 langkah untuk voucher berbayar, serta resolusi pengirim `@lid` pada OpenWA).
- **Integrasi Payment Gateway Multi-Provider**: Mendukung **Tripay**, **Midtrans**, **Duitku**, dan **Xendit** (beserta mode manual) untuk top-up saldo otomatis via *webhook callback* yang idempoten.

---

## 2. Tech Stack & Dependensi

Berdasarkan `composer.json` dan `package.json`:

- **Bahasa & Framework Backend**: PHP `^8.2`, Laravel Framework `^11.31`
- **Frontend Build & Styling**: Vite `^6.0.11`, Tailwind CSS `^3.4.13`, PostCSS, Axios
- **Database**: MySQL (disarankan pada Laragon/produksi) atau SQLite (opsi `.env.example`)
- **Paket Backend Utama**:
  - `evilfreelancer/routeros-api-php` (`^1.7`) — komunikasi API RouterOS MikroTik
  - `spatie/laravel-permission` (`^6.25`) — manajemen *Role & Permission* (RBAC)
  - `barryvdh/laravel-dompdf` (`^3.1`) — ekspor laporan PDF
  - `phpoffice/phpspreadsheet` (`^5.10`) — ekspor laporan Excel
- **Testing & Dev Tools**: PHPUnit `^11.0.1`, Mockery `^1.6`, Laravel Pint `^1.13`, Laravel Pail

---

## 3. Instalasi & Setup Pengembangan Lokal

### 3.1. Prasyarat
- PHP >= 8.2 dengan ekstensi standar Laravel & ekstensi socket/cURL/PDO MySQL
- Composer & Node.js / NPM
- MySQL / MariaDB (misal melalui Laragon)

### 3.2. Langkah Setup Awal

```bash
# 1. Masuk ke direktori proyek
cd c:\laragon\www\mantik

# 2. Install dependensi PHP dan Node.js
composer install
npm install

# 3. Salin environment config dan generate app key
copy .env.example .env
php artisan key:generate
```

Sesuaikan koneksi database di `.env` (contoh untuk MySQL Laragon):

```env
APP_NAME=MANTIK
APP_URL=http://mantik.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mantik
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi dan seeder awal:

```bash
php artisan migrate
php artisan db:seed
```

### 3.3. Akun Bawaan Seeder (`DatabaseSeeder.php`)

Setelah menjalankan `php artisan db:seed`, tersedia tiga akun staff bawaan (password default: `password`):

| Role | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| `super-admin` | `superadmin@mantik.test` | `password` | Akses penuh seluruh sistem termasuk Settings & Payment Gateway |
| `admin` | `admin@mantik.test` | `password` | Operasional penuh kecuali `view settings`, `edit settings`, `delete users` |
| `teknisi` | `teknisi@mantik.test` | `password` | Operasional jaringan terbatas pada router yang ditugaskan (*scoped*) |

Seeder juga membuat 5 paket contoh (`Paket 5 Mbps`, `Paket 10 Mbps`, `Paket 20 Mbps` tipe PPPoE, serta `Hotspot 1 Hari` dan `Hotspot 1 Minggu` tipe Hotspot).

### 3.4. Menjalankan Mode Pengembangan

```bash
# Menjalankan Vite dev server atau build aset produksi
npm run dev
# atau:
npm run build

# Menjalankan server Laravel, queue listener, pail logs & Vite bersamaan:
composer dev
```

---

## 4. Struktur Direktori & Arsitektur Kode

Arsitektur MANTIK memisahkan logika HTTP (`Controllers`), logika bisnis transaksi/integrasi (`Services`), representasi data (`Models`), pembatasan akses (`Middleware`), dan proses terjadwal (`Console/Commands`).

```text
app/
├── Console/Commands/
│   ├── AggregateTrafficStats.php             # Agregasi log traffic per jam / per hari
│   ├── CheckCustomerNotificationsCommand.php # Cek voucher hampir expired & saldo menipis
│   ├── CheckRouterStatusCommand.php          # Cek konektivitas router & catat uptime
│   └── RecordMikrotikStats.php               # Rekam statistik traffic interface MikroTik
├── Http/
│   ├── Controllers/
│   │   ├── Api/                              # Endpoint API & Webhook publik (tanpa CSRF)
│   │   │   ├── HotspotWebhookController.php
│   │   │   ├── PaymentGatewayWebhookController.php
│   │   │   ├── RouterApiController.php
│   │   │   ├── TelegramWebhookController.php
│   │   │   └── WhatsAppWebhookController.php
│   │   ├── Auth/                             # Autentikasi Staff/Admin
│   │   ├── Portal/                           # Autentikasi & Dashboard Portal Pelanggan
│   │   │   ├── PortalAuthController.php
│   │   │   └── PortalDashboardController.php
│   │   ├── BillingController.php
│   │   ├── CustomerController.php
│   │   ├── HotspotController.php
│   │   ├── PackageController.php
│   │   ├── PaymentGatewayController.php
│   │   ├── PppoeController.php
│   │   ├── RouterController.php
│   │   ├── SettingController.php
│   │   ├── TrafficController.php
│   │   ├── UserController.php
│   │   └── WhatsAppVoucherController.php
│   └── Middleware/
│       ├── EnsureCustomerPortal.php          # Pastikan user Portal terhubung ke data Customer aktif
│       ├── RedirectCustomerFromAdmin.php     # Blokir role customer mengakses area staff
│       └── TeknisiRouterScope.php            # Batasi teknisi hanya pada router yang di-assign
├── Models/                                   # Eloquent Models (23 model)
└── Services/
    ├── BillingService.php                    # Pembayaran invoice, generate bulanan, pengingat WA
    ├── CustomerBalanceService.php            # Credit/debit saldo dengan lock transaksi & ledger
    ├── MikrotikService.php                   # Koneksi & eksekusi perintah RouterOS API
    ├── NotificationService.php               # Pengiriman notifikasi multi-channel
    ├── PaymentGatewayService.php             # Pembuatan invoice & verifikasi callback PG
    ├── RouterMonitorService.php              # Pengecekan ping/status router & log uptime
    ├── TelegramService.php                   # Integrasi API Bot Telegram
    └── WhatsAppService.php                   # Integrasi Fonnte / OpenWA (termasuk resolve @lid)
```

---

## 5. Skema Database & Model Inti

### 5.1. Manajemen Jaringan & Paket
- **`routers`** (`Router`): Menyimpan kredensial RouterOS (`host`, `port`, `username`, `password`, `ssid`, `ros_version`, `status`).
- **`packages`** (`Package`): Profil layanan PPPoE dan Hotspot (`name`, `slug`, `bandwidth_up`, `bandwidth_down`, `price`, `duration_days`, `type`).
- **`pppoe_users`** (`PppoeUser`): Pelanggan PPPoE pada router tertentu (`router_id`, `customer_id`, `package_id`, `username`, `is_disabled`, `expires_at`).
- **`hotspot_users`** (`HotspotUser`) & **`hotspot_vouchers`** (`HotspotVoucher`): Akun hotspot dan stok/riwayat voucher (`code`, `is_used`, `used_at`, `used_by`, `expires_at`).
- **`queues`** (`Queue`), **`router_stats`**, **`router_uptime_logs`**, **`traffic_logs`**, **`traffic_hourly_stats`**, **`traffic_daily_stats`**, **`router_interface_settings`**.

### 5.2. Pelanggan, Akun Portal, & Buku Mutasi Saldo
Migrasi tahap pelanggan (`2026_11_11_080002`, `2026_11_11_080015`, `2026_11_11_080023`) memperluas `customers` dan `users` serta menambahkan tabel finansial berikut:

- **`users`** (`User`): Memiliki `phone` (unik setelah normalisasi `62...`) untuk login Portal Pelanggan.
- **`customers`** (`Customer`):
  - `user_id` (relasi 1-ke-1 opsional ke akun login Portal `users`)
  - `phone` (unik, dinormalisasi otomatis via `Customer::normalizePhoneNumber()` ke format `62...`)
  - `customer_type` (`regular` | `reseller`)
  - `balance` (`decimal`, saldo deposit aktif dalam Rupiah)
  - `status` (`active` | `inactive` | `suspended`)
- **`customer_balance_transactions`** (`CustomerBalanceTransaction`):
  - Buku mutasi (*ledger*) pencatatan setiap pergerakan saldo.
  - Kolom utama: `customer_id`, `type` (`credit` / `debit`), `category` (`topup`, `voucher_wa`, `voucher_portal`, `billing_payment`, `adjustment`, dll.), `amount`, `balance_before`, `balance_after`, `reference_type`, `reference_id`, `notes`, `created_by`.
- **`topup_transactions`** (`TopupTransaction`):
  - Transaksi pengajuan maupun gateway top-up: `reference_code` (otomatis terisi saat pembuatan), `amount`, `fee`, `total_amount`, `gateway` (`manual`, `tripay`, `midtrans`, `duitku`, `xendit`), `payment_method`, `gateway_reference`, `payment_url`, `qr_url`, `status` (`pending`, `paid`, `expired`, `failed`), `paid_at`, `payload`.

### 5.3. Billing & Gateway
- **`billings`** (`Billing`):
  - `invoice_number` (otomatis `INV-YYYYMMDD-XXXXXX` saat `creating`), `customer_id`, `package_id`, `amount`, `paid_amount`, `period_start`, `period_end`, `due_date`, `paid_at`, `status` (`unpaid`, `partial`, `paid`, `overdue`, `cancelled`), `payment_method`.
- **`wa_voucher_requests`** (`WaVoucherRequest`): Riwayat permintaan voucher WhatsApp (`phone`, `customer_id`, `voucher_id`, `voucher_code`, `price_deducted`, `status`, `requester_name`).
- **`settings`** (`Setting`): Key-value konfigurasi dinamis aplikasi (`Setting::get()` / `Setting::set()`).

---

## 6. Autentikasi, Hak Akses (RBAC), & Isolasi Portal

Sistem memisahkan alur login antara **Staff/Admin** dan **Pelanggan**:

1. **Login Staff (`/login`)**: Menggunakan Email + Password melalui `AuthenticatedSessionController`. Jika user yang login hanya memiliki role `customer` (tanpa role `super-admin`, `admin`, `teknisi`), sistem otomatis mengarahkan ke `portal.dashboard`.
2. **Login Portal Pelanggan (`/portal/login`)**: Menggunakan **Nomor WhatsApp + Password** melalui `PortalAuthController`. Input nomor (`08...`, `+62...`, `62...`) dinormalisasi terlebih dahulu ke `62...` sebelum pencocokan.
3. **Middleware Isolasi (`bootstrap/app.php`)**:
   - `staff.only` (`RedirectCustomerFromAdmin`): Dipasang pada seluruh route panel admin. Mencegah akun dengan role `customer` mengakses halaman/route admin.
   - `customer.portal` (`EnsureCustomerPortal`): Dipasang pada grup route `/portal/*`. Memastikan pengguna memiliki relasi `customer` yang valid dan aktif (`status === 'active'`), atau menolak akses jika akun staff mencoba masuk ke portal pelanggan.
   - `teknisi.router.scope` (`TeknisiRouterScope`): Membatasi akses data jaringan teknisi hanya pada router yang ditetapkan melalui `UserRouterAssignment`.
4. **Pengecualian CSRF (`bootstrap/app.php`)**:
   - Seluruh endpoint berawalan `api/*` dikecualikan dari validasi CSRF agar webhook dari RouterOS, OpenWA/Fonnte, Telegram, dan Payment Gateway dapat diterima dengan kode `200`/`401` sesuai protokolnya.

---

## 7. Alur Bisnis & Integrasi Utama

### 7.1. Manajemen Saldo Deposit (`CustomerBalanceService`)
**Aturan wajib**: Jangan pernah mengubah kolom `customers.balance` secara langsung dari controller. Gunakan selalu [`CustomerBalanceService`](file:///c:/laragon/www/mantik/app/Services/CustomerBalanceService.php):
- `credit(...)` & `debit(...)`: Menggunakan `DB::transaction()` dan `Customer::lockForUpdate()` agar aman dari *race condition*, memvalidasi saldo cukup sebelum debit, serta otomatis mencatat baris pada `customer_balance_transactions`.
- `approveTopup(...)`: Menyetujui top-up `pending` secara **idempoten** (jika status sudah `paid`, tidak akan mengkredit saldo dua kali).

### 7.2. Permintaan Voucher via WhatsApp & OpenWA LID
Endpoint webhook: `POST|GET /api/whatsapp/webhook` → [`WhatsAppWebhookController::incoming`](file:///c:/laragon/www/mantik/app/Http/Controllers/Api/WhatsAppWebhookController.php).

Alur penting yang ditangani sistem:
1. **Filter Pesan**: Pesan dari grup (`@g.us`) dan pesan keluar (`outgoing` / `fromMe`) langsung diabaikan.
2. **Resolusi OpenWA `@lid`**: OpenWA versi terbaru sering mengirim `from` dalam format `<id>@lid` alih-alih `<nomor>@c.us`. [`WhatsAppService::resolveOpenWaLid`](file:///c:/laragon/www/mantik/app/Services/WhatsAppService.php) memanggil endpoint `/contacts/{lid}` pada OpenWA API (dengan caching) untuk mendapatkan nomor WhatsApp asli (`62...`) dan menyimpannya ke kolom `phone` pada `wa_voucher_requests`.
3. **Verifikasi Pelanggan & Konfirmasi 2-Langkah**:
   - Pelanggan terdaftar diidentifikasi berdasarkan nomor WhatsApp yang cocok di tabel `customers`.
   - Perintah seperti `CEK SALDO` hanya dilayani untuk nomor pelanggan terdaftar.
   - Untuk pembelian voucher berbayar, sistem memeriksa kecukupan saldo lalu mengirimkan **kode konfirmasi 2-langkah** yang harus dibalas oleh pelanggan sebelum saldo didebit melalui ledger dan voucher dikirim.

### 7.3. Portal Pelanggan (`/portal`)
Dikelola oleh [`PortalDashboardController`](file:///c:/laragon/www/mantik/app/Http/Controllers/Portal/PortalDashboardController.php):
- `GET /portal` (`portal.dashboard`): Menampilkan saldo deposit, daftar tagihan bulanan, paket hotspot yang stoknya tersedia, riwayat voucher, mutasi saldo, dan status top-up.
- `POST /portal/vouchers/buy`: Membeli voucher hotspot memakai saldo (mengunci stok voucher dan saldo pelanggan dalam satu transaksi database) serta mengirim salinan ke WA.
- `POST /portal/topup`: Membuat pengajuan top-up manual atau invoice otomatis Payment Gateway apabila gateway aktif.
- `POST /portal/billing/{billing}/pay-with-balance`: Melunasi invoice tagihan milik pelanggan sendiri dengan saldo deposit (memverifikasi `customer_id` pemilik tagihan agar tidak terjadi IDOR).

### 7.4. Billing Bulanan (`BillingService` & `BillingController`)
- `POST /billing/generate-monthly`: Membuat tagihan bulan target (`Y-m`) untuk setiap `PppoeUser` aktif (`is_disabled = false`, customer `active`, harga paket `> 0`) yang belum memiliki tagihan non-cancelled pada periode tersebut. Sekaligus menandai invoice lama yang melewati `due_date` sebagai `overdue`.
- `POST /billing/{billing}/pay`: Pembayaran dari panel admin via `cash`, `transfer`, atau `balance` (potong saldo deposit melalui `BillingService::payInvoice()`), disertai pengiriman struk WA otomatis.
- `POST /billing/{billing}/remind`: Mengirim pengingat tagihan via WhatsApp beserta tautan menuju `/portal/login`.

### 7.5. Payment Gateway & Webhook (`PaymentGatewayService`)
Konfigurasi disimpan di tabel `settings` melalui menu `/payment-gateway/settings` (`can:view settings`).

| Provider | Endpoint Callback Publik | Mekanisme Verifikasi Signature |
| :--- | :--- | :--- |
| **Tripay** | `POST /api/payment-gateway/callback/tripay` | HMAC-SHA256 raw request body dengan `private_key` pada header `X-Callback-Signature` |
| **Midtrans** | `POST /api/payment-gateway/callback/midtrans` | SHA-512(`order_id + status_code + gross_amount + api_key`) dibanding `signature_key` |
| **Duitku** | `POST /api/payment-gateway/callback/duitku` | MD5(`merchantCode + amount + merchantOrderId + api_key`) dibanding `signature` |
| **Xendit** | `POST /api/payment-gateway/callback/xendit` | Pencocokan `private_key` (Callback Token) terhadap header `x-callback-token` |

Seluruh callback diproses melalui [`PaymentGatewayService::handleCallback`](file:///c:/laragon/www/mantik/app/Services/PaymentGatewayService.php) → memverifikasi signature, mencari `TopupTransaction` berdasarkan `reference_code`, menjaga **idempotency** bila status sudah `paid`, lalu memanggil `CustomerBalanceService::approveTopup()`.

---

## 8. Console Commands & Task Scheduler

Terdaftar pada `routes/console.php`:

| Command | Jadwal | Fungsi |
| :--- | :--- | :--- |
| `php artisan mantik:check-routers` | Setiap 1 menit | Memeriksa status online/offline router dan mencatat `RouterUptimeLog` |
| `php artisan mantik:record-stats` | Setiap 1 menit | Mengambil statistik traffic interface router dari MikroTik (`TrafficLog`) |
| `php artisan mantik:aggregate-traffic --mode=hourly` | Setiap 1 jam | Merangkum log traffic mentah menjadi `TrafficHourlyStat` |
| `php artisan mantik:aggregate-traffic --mode=daily` | Harian (`00:05`) | Merangkum statistik per jam menjadi `TrafficDailyStat` |
| `php artisan mantik:check-customer-notifications` | Setiap 15 menit | Mengirim peringatan voucher hampir kadaluarsa dan saldo menipis via WA/Telegram |

Pada server produksi (Linux/Windows Task Scheduler), jalankan scheduler Laravel setiap menit:

```bash
php artisan schedule:run
```

---

## 9. Konfigurasi Lingkungan & Pengaturan Sistem

Pengaturan dibagi antara file `config/` (fallback `.env`) dan tabel database `settings` (prioritas utama saat runtime melalui `Setting::get()`):

- **MikroTik (`config/mikrotik.php`)**: `MIKROTIK_DEFAULT_PORT` (`8728`), `MIKROTIK_DEFAULT_TIMEOUT` (`30`).
- **WhatsApp (`config/whatsapp.php` & UI `/whatsapp/settings`)**: `whatsapp_api_url`, `whatsapp_api_key`, `whatsapp_gateway_type` (`auto`/`openwa`/`fonnte`), `whatsapp_session`, `whatsapp_webhook_secret`, `whatsapp_admin_phone`, `WA_RATE_LIMIT_MINUTES`, `WA_VOUCHER_CODE_LENGTH`, `WA_AUTO_GENERATE_VOUCHER`.
- **Payment Gateway (UI `/payment-gateway/settings`)**: `payment_gateway_enabled`, `payment_gateway_provider`, `payment_gateway_mode` (`sandbox`/`production`), `payment_gateway_merchant_code`, `payment_gateway_api_key`, `payment_gateway_private_key`, `payment_gateway_default_channel`.

---

## 10. Standar Pengembangan, Keamanan, & Pengujian

### 10.1. Standar Kode & Keamanan
1. **Normalisasi Nomor WhatsApp**: Selalu normalisasi input nomor telepon dengan `Customer::normalizePhoneNumber($phone)` sebelum query, validasi keunikan, maupun pengiriman pesan WA agar format `08...`, `+62...`, dan `62...` konsisten.
2. **Transaksi Finansial Atomik**: Setiap perubahan saldo atau pelunasan invoice **wajib** memakai `BillingService` / `CustomerBalanceService` (jangan update `balance` langsung dengan `increment`/`decrement` di luar lock transaksi).
3. **Urutan Route Resource**: Route statis di dalam grup (contoh: `/hotspot/vouchers`, `/customers/topups/...`, `/billing/generate-monthly`) harus dideklarasikan sebelum `Route::resource(...)` bila memiliki pola URL yang berpotensi bertabrakan dengan parameter wildcard `{id}`.
4. **Proteksi Data Pelanggan**: Pada endpoint portal (`/portal/*`), selalu validasi bahwa resource milik `auth()->user()->customer->id`.

### 10.2. Menjalankan Test Suite

Seluruh Feature Test menggunakan `DatabaseTransactions` sehingga tidak merusak data development setelah selesai, **namun wajib menggunakan `Http::fake()`** pada `setUp()` agar tidak memanggil API WhatsApp / Payment Gateway sungguhan yang tersimpan di tabel `settings`.

Daftar Feature Test yang tersedia:
- `tests/Feature/CustomerManagementTest.php` — CRUD pelanggan, validasi nomor WA duplikat lintas format, top-up/adjustment, proteksi hapus pelanggan bersaldo positif.
- `tests/Feature/CustomerPortalTest.php` — Login nomor WA format `08...`, isolasi redirect admin vs portal, pembelian voucher portal, pengajuan top-up & approval admin, update password.
- `tests/Feature/CustomerWhatsAppVoucherTest.php` — Cek saldo WA, penolakan nomor tak terdaftar/saldo kurang, konfirmasi 2-step + pencatatan ledger.
- `tests/Feature/WhatsAppWebhookTest.php` — Bebas CSRF pada webhook, resolusi `@lid` OpenWA ke nomor telepon asli, filter pesan grup & pesan keluar.
- `tests/Feature/BillingAndPaymentGatewayTest.php` — Generate tagihan bulanan idempoten, pembayaran tagihan via potong saldo (admin & portal), proteksi tagihan milik orang lain (403), konfigurasi & pembuatan invoice Tripay, serta verifikasi signature & idempotensi webhook Tripay & Midtrans.
- `tests/Feature/HotspotVoucherRouteTest.php` — Verifikasi urutan route voucher hotspot.

Perintah menjalankan seluruh test utama pelanggan, billing, portal, dan WhatsApp:

```bash
php artisan test tests/Feature/BillingAndPaymentGatewayTest.php tests/Feature/CustomerPortalTest.php tests/Feature/CustomerManagementTest.php tests/Feature/CustomerWhatsAppVoucherTest.php tests/Feature/WhatsAppWebhookTest.php
```

> **Catatan Test Webhook Event Gratis**: `tests/Feature/FreeEventVoucherWebhookTest.php` ditujukan untuk pengujian skenario event gratis tersendiri; pastikan HTTP client dipalsukan bila tabel `settings` lokal berisi kredensial WhatsApp produksi agar pesan uji tidak terkirim ke gateway eksternal.

