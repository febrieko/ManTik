<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WhatsAppService;
use App\Services\TelegramService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Router;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class PhoneNormalizationAndRouterAlertTest extends TestCase
{
    use DatabaseTransactions;

    public function test_whatsapp_service_normalizes_various_phone_formats(): void
    {
        $wa = new WhatsAppService();

        // Diawali 0
        $this->assertEquals('628123456789', $wa->normalizePhone('08123456789'));
        $this->assertEquals('6281234567890', $wa->normalizePhone('0812-3456-7890'));
        $this->assertEquals('6281234567890', $wa->normalizePhone('(0812) 3456 7890'));

        // Diawali 62
        $this->assertEquals('628123456789', $wa->normalizePhone('628123456789'));

        // Diawali +62
        $this->assertEquals('628123456789', $wa->normalizePhone('+628123456789'));
        $this->assertEquals('628123456789', $wa->normalizePhone('+62 812-3456-789'));

        // Diawali 8
        $this->assertEquals('628123456789', $wa->normalizePhone('8123456789'));

        // Kosong / null
        $this->assertEquals('', $wa->normalizePhone(''));
        $this->assertEquals('', $wa->normalizePhone(null));
    }

    public function test_user_model_phone_mutator_normalizes_zero_and_62(): void
    {
        $user1 = new User();
        $user1->phone = '081234567890';
        $this->assertEquals('6281234567890', $user1->phone);

        $user2 = new User();
        $user2->phone = '6281234567890';
        $this->assertEquals('6281234567890', $user2->phone);

        $user3 = new User();
        $user3->phone = '+62 812-3456-7890';
        $this->assertEquals('6281234567890', $user3->phone);
    }

    public function test_router_status_alert_only_sends_to_assigned_teknisi(): void
    {
        // Pastikan role ada
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teknisi', 'guard_name' => 'web']);

        // Buat router
        $router = Router::create([
            'name' => 'Test Router Unit',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
            'status' => 'online',
        ]);

        // Buat Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin_test_' . uniqid() . '@test.com',
            'password' => 'secret123',
            'phone' => '081111111111', // Diawali 0
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super-admin');

        // Buat Teknisi A (dienrol ke router ini)
        $teknisiA = User::create([
            'name' => 'Teknisi A Enrolled',
            'email' => 'teknisia_test_' . uniqid() . '@test.com',
            'password' => 'secret123',
            'phone' => '082222222222', // Diawali 0
            'is_active' => true,
        ]);
        $teknisiA->assignRole('teknisi');
        $router->users()->attach($teknisiA->id);

        // Buat Teknisi B (TIDAK dienrol ke router ini)
        $teknisiB = User::create([
            'name' => 'Teknisi B Not Enrolled',
            'email' => 'teknisib_test_' . uniqid() . '@test.com',
            'password' => 'secret123',
            'phone' => '083333333333',
            'is_active' => true,
        ]);
        $teknisiB->assignRole('teknisi');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Mock WhatsAppService untuk menangkap nomor tujuan
        $sentRecipients = [];
        $mockWa = $this->createMock(WhatsAppService::class);
        $mockWa->method('normalizePhone')->willReturnCallback(function ($p) {
            $c = preg_replace('/\D/', '', $p ?? '');
            return str_starts_with($c, '0') ? '62' . substr($c, 1) : $c;
        });
        $mockWa->method('sendMessage')->willReturnCallback(function ($to, $msg) use (&$sentRecipients) {
            $sentRecipients[] = $to;
            return ['success' => true, 'phone' => $to];
        });

        $mockTg = $this->createMock(TelegramService::class);

        $notificationService = new NotificationService($mockWa, $mockTg);
        Setting::set('router_alert_enabled', true);
        Setting::set('router_alert_channel', 'whatsapp');
        Setting::set('whatsapp_admin_phone', '');

        $notificationService->sendRouterStatusAlert($router, 'offline', 'online', 'Kabel terputus');

        // Verifikasi bahwa Super Admin menerima notifikasi (dengan nomor ternormalisasi 62...)
        $this->assertContains('6281111111111', $sentRecipients);

        // Verifikasi bahwa Teknisi A (dienrol) menerima notifikasi (dengan nomor ternormalisasi 62...)
        $this->assertContains('6282222222222', $sentRecipients);

        // Verifikasi bahwa Teknisi B (TIDAK dienrol) TIDAK menerima notifikasi
        $this->assertNotContains('6283333333333', $sentRecipients);
        $this->assertNotContains('083333333333', $sentRecipients);
    }

    public function test_router_saves_and_retrieves_ssid(): void
    {
        $router = Router::create([
            'name' => 'Router Cafe Utama',
            'ssid' => '@Cafe_Utama_Hotspot',
            'host' => '192.168.10.1',
            'username' => 'admin',
            'password' => 'secret',
            'port' => 8728,
            'is_active' => true,
        ]);

        $this->assertEquals('@Cafe_Utama_Hotspot', $router->ssid);
        $fresh = Router::find($router->id);
        $this->assertEquals('@Cafe_Utama_Hotspot', $fresh->ssid);
    }

    public function test_whatsapp_service_build_voucher_message_with_brand_and_ssid(): void
    {
        $wa = new WhatsAppService();
        Setting::set('hotspot_brand_name', 'MANTIK DIGITAL NET');

        $message = $wa->buildVoucherMessage([
            'code' => 'VCHR-12345',
            'package_name' => 'Paket 5 Jam',
            'speed' => '5 Mbps',
            'duration' => '5 Jam',
            'price' => 5000,
            'expires_at' => now()->addHours(5),
            'name' => 'Ahmad',
            'router_name' => 'Router Lantai 2',
            'ssid' => '@Cafe_Utama_Hotspot',
            'brand_name' => 'MANTIK DIGITAL NET'
        ]);

        $this->assertStringContainsString('MANTIK DIGITAL NET', $message);
        $this->assertStringContainsString('VCHR-12345', $message);
        $this->assertStringContainsString('@Cafe_Utama_Hotspot', $message);
        $this->assertStringContainsString('Paket 5 Jam', $message);
        $this->assertStringContainsString('Ahmad', $message);
    }
}

