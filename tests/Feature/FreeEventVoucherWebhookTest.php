<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Router;
use App\Models\Package;
use App\Models\HotspotVoucher;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FreeEventVoucherWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('whatsapp_webhook_secret', '', 'whatsapp');
    }

    public function test_user_can_request_voucher_with_3_parts_ssid_and_package(): void
    {
        $router = Router::create([
            'name'      => 'Router Kafe Event',
            'ssid'      => 'KAFE_NET',
            'host'      => '192.168.88.10',
            'username'  => 'admin',
            'password'  => 'admin',
            'port'      => 8728,
            'is_active' => true,
        ]);

        $package = Package::create([
            'name'          => 'Hotspot 1 Hari Event',
            'slug'          => 'hotspot-event-1hari',
            'type'          => 'hotspot',
            'price'         => 0, // Paket Free/Event
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id'   => $router->id,
            'package_id'  => $package->id,
            'code'        => 'FREE-VCR-1111',
            'is_used'     => false,
        ]);

        $phone = '089912345678';

        // 1. Request voucher dengan format 3 bagian: VOUCHER#KAFE_NET#HOTSPOT-EVENT-1HARI
        $response = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone, 'name' => 'Pengunjung Event'],
            'message' => 'VOUCHER#KAFE_NET#HOTSPOT-EVENT-1HARI',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'success',
            'voucher' => 'FREE-VCR-1111',
        ]);

        // Pastikan voucher ditandai terpakai
        $voucher->refresh();
        $this->assertTrue((bool)$voucher->is_used);

        // 2. Request kedua di hari yang sama dengan nomor WA yang sama harus ditolak (daily limit 1 per hari)
        $voucher2 = HotspotVoucher::create([
            'router_id'   => $router->id,
            'package_id'  => $package->id,
            'code'        => 'FREE-VCR-2222',
            'is_used'     => false,
        ]);

        $response2 = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone, 'name' => 'Pengunjung Event'],
            'message' => 'VOUCHER#KAFE_NET#HOTSPOT-EVENT-1HARI',
        ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'status' => 'daily_limit_reached',
        ]);

        // Voucher kedua tidak boleh terpakai
        $voucher2->refresh();
        $this->assertFalse((bool)$voucher2->is_used);

        // 3. Nomor WA berbeda di hari yang sama harus BERHASIL mendapatkan voucher
        $response3 = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => '089987654321', 'name' => 'Pengunjung Lain'],
            'message' => 'VOUCHER#KAFE_NET#HOTSPOT-EVENT-1HARI',
        ]);

        $response3->assertStatus(200);
        $response3->assertJson([
            'status'  => 'success',
            'voucher' => 'FREE-VCR-2222',
        ]);
        $voucher2->refresh();
        $this->assertTrue((bool)$voucher2->is_used);
    }

    public function test_user_can_request_free_voucher_using_free_keyword(): void
    {
        $router = Router::create([
            'name'      => 'Router Kafe 2',
            'ssid'      => 'KAFE_NET_2',
            'host'      => '192.168.88.20',
            'username'  => 'admin',
            'password'  => 'admin',
            'port'      => 8728,
            'is_active' => true,
        ]);

        $package = Package::create([
            'name'          => 'Paket FREE Hotspot',
            'slug'          => 'free',
            'type'          => 'hotspot',
            'price'         => 0,
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id'   => $router->id,
            'package_id'  => $package->id,
            'code'        => 'EVENT-FREE-777',
            'is_used'     => false,
        ]);

        // Request dengan format: VOUCHER#KAFE_NET_2#FREE
        $response = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => '087711223344', 'name' => 'Tamu'],
            'message' => 'VOUCHER#KAFE_NET_2#FREE',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'success',
            'voucher' => 'EVENT-FREE-777',
        ]);
    }
}
