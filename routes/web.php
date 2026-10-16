<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\PppoeController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\IpController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\Api\RouterApiController;
use App\Http\Controllers\WhatsAppVoucherController;

// Redirect root to dashboard or login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Auth routes
require __DIR__ . '/auth.php';

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ============================================
    // ROUTER MANAGEMENT
    // ============================================
    Route::resource('routers', RouterController::class);
    Route::post('/routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test');

    // ============================================
    // ROUTER STATUS & UPTIME MONITORING
    // ============================================
    Route::prefix('router-status')->name('router-status.')->group(function () {
        Route::get('/', [\App\Http\Controllers\RouterStatusController::class, 'index'])->name('index');
        Route::post('/check-all', [\App\Http\Controllers\RouterStatusController::class, 'checkAll'])->name('check-all');
        Route::post('/{router}/check', [\App\Http\Controllers\RouterStatusController::class, 'checkSingle'])->name('check');
        Route::get('/{router}', [\App\Http\Controllers\RouterStatusController::class, 'show'])->name('show');
    });

    // ============================================
    // PPPOE MANAGEMENT
    // ============================================
    Route::middleware(['can:view pppoe', \App\Http\Middleware\TeknisiRouterScope::class])->group(function () {
        Route::resource('pppoe', PppoeController::class);
        Route::post('/pppoe/{pppoeUser}/toggle', [PppoeController::class, 'toggle'])->name('pppoe.toggle');
        Route::post('/pppoe/{pppoeUser}/sync', [PppoeController::class, 'sync'])->name('pppoe.sync');
        Route::post('/pppoe/sync-router/{router}', [PppoeController::class, 'syncFromRouter'])->name('pppoe.sync-router');
    });

    // ============================================
    // HOTSPOT MANAGEMENT
    // ============================================
    Route::middleware(['can:view hotspot', \App\Http\Middleware\TeknisiRouterScope::class])->group(function () {
        Route::resource('hotspot', HotspotController::class);
        Route::post('/hotspot/{hotspot}/toggle', [HotspotController::class, 'toggle'])->name('hotspot.toggle');
        Route::post('/hotspot/sync-router/{router}', [HotspotController::class, 'syncFromRouter'])->name('hotspot.sync-router');
        Route::get('/hotspot/vouchers', [HotspotController::class, 'vouchers'])->name('hotspot.vouchers');
        Route::get('/hotspot/vouchers/generate', [HotspotController::class, 'generateVouchers'])->name('hotspot.vouchers.generate');
        Route::post('/hotspot/vouchers/store', [HotspotController::class, 'storeVouchers'])->name('hotspot.vouchers.store');
        Route::get('/hotspot/vouchers/{voucher}/print', [HotspotController::class, 'printVoucher'])->name('hotspot.vouchers.print');
        Route::post('/hotspot/vouchers/print-bulk', [HotspotController::class, 'printBulk'])->name('hotspot.vouchers.print-bulk');
    });

    // ============================================
    // QUEUE / BANDWIDTH MANAGEMENT
    // ============================================
    Route::middleware(['can:view queue', \App\Http\Middleware\TeknisiRouterScope::class])->group(function () {
        Route::resource('queue', QueueController::class);
        Route::post('/queue/{queue}/toggle', [QueueController::class, 'toggle'])->name('queue.toggle');
        Route::post('/queue/sync-router/{router}', [QueueController::class, 'syncFromRouter'])->name('queue.sync-router');
    });

    // ============================================
    // IP & DHCP
    // ============================================
    Route::middleware(['can:view ip'])->group(function () {
        Route::get('/ip', [IpController::class, 'index'])->name('ip.index');
        Route::get('/ip/{router}/addresses', [IpController::class, 'addresses'])->name('ip.addresses');
        Route::get('/ip/{router}/dhcp', [IpController::class, 'dhcp'])->name('ip.dhcp');
        Route::get('/ip/{router}/leases', [IpController::class, 'leases'])->name('ip.leases');
    });

    // ============================================
    // TRAFFIC MONITOR
    // ============================================
    Route::middleware(['can:view traffic', \App\Http\Middleware\TeknisiRouterScope::class])->group(function () {
        Route::get('/traffic', [TrafficController::class, 'index'])->name('traffic.index');
        Route::post('/traffic/record-stats', [TrafficController::class, 'recordStats'])->name('traffic.record-stats');
        Route::get('/traffic/mrtg', [TrafficController::class, 'mrtg'])->name('traffic.mrtg');
        Route::get('/traffic/{router}/{interface}/mrtg-detail', [TrafficController::class, 'mrtgDetail'])->name('traffic.mrtg-detail');
        Route::get('/traffic/{router}/{interface}/mrtg-data', [TrafficController::class, 'mrtgData'])->name('traffic.mrtg-data');
        Route::get('/traffic/{router}/interfaces', [TrafficController::class, 'interfaces'])->name('traffic.interfaces');
        Route::get('/traffic/{router}/{interface}/data', [TrafficController::class, 'data'])->name('traffic.data');

        // MRTG Settings
        Route::get('/traffic/mrtg/settings/{router}', [\App\Http\Controllers\MrtgSettingController::class, 'index'])->name('mrtg.settings');
        Route::post('/traffic/mrtg/settings/{router}/sync', [\App\Http\Controllers\MrtgSettingController::class, 'sync'])->name('mrtg.settings.sync');
        Route::post('/traffic/mrtg/settings/{router}/save', [\App\Http\Controllers\MrtgSettingController::class, 'save'])->name('mrtg.settings.save');
        Route::post('/traffic/mrtg/settings/{router}/toggle/{setting}', [\App\Http\Controllers\MrtgSettingController::class, 'toggle'])->name('mrtg.settings.toggle');
        Route::post('/traffic/mrtg/settings/{router}/reorder', [\App\Http\Controllers\MrtgSettingController::class, 'reorder'])->name('mrtg.settings.reorder');
    });

    // ============================================
    // CUSTOMERS
    // ============================================
    Route::middleware(['can:view customers'])->group(function () {
        Route::resource('customers', CustomerController::class);
    });

    // ============================================
    // BILLING
    // ============================================
    Route::middleware(['can:view billing'])->group(function () {
        Route::resource('billing', BillingController::class);
        Route::post('/billing/{billing}/pay', [BillingController::class, 'pay'])->name('billing.pay');
        Route::get('/billing/{billing}/invoice', [BillingController::class, 'invoice'])->name('billing.invoice');
        Route::post('/billing/generate-monthly', [BillingController::class, 'generateMonthly'])->name('billing.generate-monthly');
    });

    // ============================================
    // REPORTS
    // ============================================
    Route::middleware(['can:view reports'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/pppoe', [ReportController::class, 'pppoe'])->name('reports.pppoe');
        Route::get('/reports/billing', [ReportController::class, 'billing'])->name('reports.billing');
        Route::get('/reports/traffic', [ReportController::class, 'traffic'])->name('reports.traffic');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    });

    // ============================================
    // USERS MANAGEMENT
    // ============================================
    Route::middleware(['can:view users'])->group(function () {
        Route::resource('users', UserController::class);
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.update-password');
        
        Route::get('/users/{user}/router-assignments', [\App\Http\Controllers\UserRouterAssignmentController::class, 'index'])->name('users.router-assignments');
        Route::post('/users/{user}/router-assignments', [\App\Http\Controllers\UserRouterAssignmentController::class, 'assign'])->name('users.router-assignments.assign');
        Route::delete('/users/{user}/router-assignments/{router}', [\App\Http\Controllers\UserRouterAssignmentController::class, 'revoke'])->name('users.router-assignments.revoke');
    });

    // ============================================
    // USER PROFILE & PASSWORD
    // ============================================
    Route::get('/profile/password', [UserController::class, 'showChangePasswordForm'])->name('profile.password');
    Route::put('/profile/password', [UserController::class, 'changeOwnPassword'])->name('profile.password.update');

    // ============================================
    // PACKAGES / PROFILES
    // ============================================
    Route::resource('packages', PackageController::class);
    Route::post('/packages/{package}/toggle', [PackageController::class, 'toggle'])->name('packages.toggle');
    Route::post('/packages/sync-router', [PackageController::class, 'syncFromRouter'])->name('packages.sync-router');

    // ============================================
    // NOTIFICATIONS
    // ============================================
    Route::middleware(['can:view notifications'])->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/test', [NotificationController::class, 'test'])->name('notifications.test');
        Route::post('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.settings.update');
        Route::post('/notifications/scan-customers', [NotificationController::class, 'scanCustomers'])->name('notifications.scan-customers');
    });

    // ============================================
    // SETTINGS
    // ============================================
    Route::middleware(['can:view settings'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // ============================================
    // WHATSAPP VOUCHER GATEWAY
    // ============================================
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/voucher-requests', [WhatsAppVoucherController::class, 'index'])->name('voucher-requests');
        Route::get('/settings', [WhatsAppVoucherController::class, 'settings'])->name('settings');
        Route::post('/settings', [WhatsAppVoucherController::class, 'updateSettings'])->name('settings.update');
        Route::post('/test-send', [WhatsAppVoucherController::class, 'testSend'])->name('test-send');
        Route::post('/voucher-requests/{waRequest}/resend', [WhatsAppVoucherController::class, 'resendVoucher'])->name('voucher-requests.resend');
    });

    // ============================================
    // TELEGRAM BOT GATEWAY
    // ============================================
    Route::prefix('telegram')->name('telegram.')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\TelegramAdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\TelegramAdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/webhook', [\App\Http\Controllers\TelegramAdminController::class, 'setWebhook'])->name('set-webhook');
        Route::post('/settings/test', [\App\Http\Controllers\TelegramAdminController::class, 'testSend'])->name('test-send');
        Route::get('/users', [\App\Http\Controllers\TelegramAdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/topup', [\App\Http\Controllers\TelegramAdminController::class, 'topup'])->name('users.topup');
        Route::get('/requests', [\App\Http\Controllers\TelegramAdminController::class, 'requests'])->name('requests');
    });
});

// ============================================
// API ROUTES (for AJAX / frontend use)
// ============================================
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/routers/status', [RouterApiController::class, 'status'])->name('routers.status');
    Route::get('/routers/{router}/stats', [RouterApiController::class, 'stats'])->name('routers.stats');
    Route::get('/routers/{router}/live-stats', [RouterApiController::class, 'liveStats'])->name('routers.live-stats');
    Route::get('/routers/{router}/live-all-traffic', [RouterApiController::class, 'liveAllTraffic'])->name('routers.live-all-traffic');
    Route::get('/routers/{router}/traffic/{interface}', [RouterApiController::class, 'traffic'])->name('routers.traffic');
    Route::get('/routers/{router}/pppoe/active', [RouterApiController::class, 'pppoeActive'])->name('routers.pppoe.active');
    Route::get('/routers/{router}/hotspot/active', [RouterApiController::class, 'hotspotActive'])->name('routers.hotspot.active');
});

// ============================================
// PUBLIC API WEBHOOKS (Called by RouterOS)
// ============================================
Route::get('/api/hotspot/webhook/login', [\App\Http\Controllers\Api\HotspotWebhookController::class, 'onLogin']);

// ============================================
// WHATSAPP GATEWAY WEBHOOK (Pesan Masuk dari WA)
// ============================================
Route::post('/api/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'incoming'])
    ->name('api.whatsapp.webhook');
Route::get('/api/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'incoming'])
    ->name('api.whatsapp.webhook.get'); // Beberapa gateway mengirim GET

// ============================================
// TELEGRAM BOT WEBHOOK
// ============================================
Route::post('/api/telegram/webhook', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle'])
    ->name('api.telegram.webhook');
