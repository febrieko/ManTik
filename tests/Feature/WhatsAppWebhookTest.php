<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\Package;
use App\Models\Router;
use App\Models\HotspotVoucher;
use App\Models\WaVoucherRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WhatsAppWebhookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_webhook_post_without_csrf_token_is_not_blocked_by_419(): void
    {
        // Pastikan tidak ada webhook secret untuk tes umum
        Setting::set('whatsapp_webhook_secret', '', 'whatsapp');

        $response = $this->postJson('/api/whatsapp/webhook', [
            'event' => 'ping',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_webhook_get_returns_active_status(): void
    {
        $response = $this->getJson('/api/whatsapp/webhook');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'active',
        ]);
    }

    public function test_webhook_post_with_test_event(): void
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'event' => 'test',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_openwa_lid_sender_is_resolved_to_real_phone_number(): void
    {
        Cache::flush();
        Setting::set('whatsapp_webhook_secret', '', 'whatsapp');
        Setting::set('whatsapp_api_url', 'https://wa.mitrapedia.my.id/', 'whatsapp');
        Setting::set('whatsapp_api_key', 'test-api-key', 'whatsapp');
        Setting::set('whatsapp_gateway_type', 'openwa', 'whatsapp');

        Http::fake([
            '*/contacts/1696679882849*' => Http::response([
                'id'       => '628123454058@c.us',
                'name'     => 'febrieko',
                'pushName' => 'febrieko',
                'number'   => '1696679882849',
            ], 200),
            '*' => Http::response(['status' => 'sent'], 201),
        ]);

        $router = Router::create([
            'name'      => 'Router TKJ',
            'ssid'      => 'TKJ_HOTSPOT',
            'host'      => '192.168.88.1',
            'username'  => 'admin',
            'password'  => 'admin',
            'port'      => 8728,
            'is_active' => true,
        ]);

        $package = Package::create([
            'name'          => 'Paket Free LID Test',
            'slug'          => 'free-lid-test',
            'type'          => 'hotspot',
            'price'         => 0,
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        HotspotVoucher::create([
            'router_id'  => $router->id,
            'package_id' => $package->id,
            'code'       => 'LID-VOUCHER-01',
            'is_used'    => false,
        ]);

        $response = $this->postJson('/api/whatsapp/webhook', [
            'payload' => [
                'sessionId' => '9202c23e-7782-4a94-b01c-fd22490f0b80',
                'from'      => '1696679882849@lid',
                'chatId'    => '1696679882849@lid',
                'chatName'  => 'febrieko',
                'body'      => 'VOUCHER#TKJ_HOTSPOT#FREE-LID-TEST',
                'direction' => 'incoming',
                'sender'    => [
                    'id'     => '1696679882849@lid',
                    'number' => '1696679882849',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'success',
            'voucher' => 'LID-VOUCHER-01',
            'phone'   => '628123454058',
        ]);

        // Pastikan yang tersimpan di WaVoucherRequest adalah nomor WA asli, bukan angka LID
        $record = WaVoucherRequest::where('voucher_code', 'LID-VOUCHER-01')->first();
        $this->assertNotNull($record);
        $this->assertEquals('628123454058', $record->phone);
        $this->assertEquals('febrieko', $record->requester_name);
    }

    public function test_webhook_ignores_group_messages_and_outgoing_messages(): void
    {
        Setting::set('whatsapp_webhook_secret', '', 'whatsapp');

        $groupResp = $this->postJson('/api/whatsapp/webhook', [
            'payload' => [
                'from'      => '120363028093809413@g.us',
                'chatId'    => '120363028093809413@g.us',
                'author'    => '206098468438076@lid',
                'body'      => 'Halo grup',
                'direction' => 'incoming',
            ],
        ]);
        $groupResp->assertStatus(200);
        $groupResp->assertJson(['status' => 'ignored', 'reason' => 'group_or_broadcast']);

        $outResp = $this->postJson('/api/whatsapp/webhook', [
            'payload' => [
                'from'      => '60250539434020@lid',
                'chatId'    => '1696679882849@lid',
                'body'      => 'Balasan bot',
                'direction' => 'outgoing',
            ],
        ]);
        $outResp->assertStatus(200);
        $outResp->assertJson(['status' => 'ignored', 'reason' => 'from_me']);
    }
}
