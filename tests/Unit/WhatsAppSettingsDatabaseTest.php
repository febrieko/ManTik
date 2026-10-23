<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use App\Http\Controllers\WhatsAppVoucherController;

class WhatsAppSettingsDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_whatsapp_settings_are_stored_in_database_and_retrieved(): void
    {
        $wa = new WhatsAppService();
        $controller = new WhatsAppVoucherController($wa);

        $request = Request::create('/whatsapp/settings', 'POST', [
            'api_url'        => 'https://wa.mitrapedia.my.id/api/sessions/mantik/messages/send-text',
            'gateway_type'   => 'openwa',
            'session'        => 'mantik',
            'api_key'        => 'secret-api-token-12345',
            'webhook_secret' => 'webhook-secret-999',
            'admin_phone'    => '08123456789',
            'brand_name'     => 'MitraNet Hotspot',
        ]);

        $response = $controller->updateSettings($request);

        // Pastikan tersimpan di database
        $this->assertEquals('https://wa.mitrapedia.my.id/api/sessions/mantik/messages/send-text', Setting::get('whatsapp_api_url'));
        $this->assertEquals('openwa', Setting::get('whatsapp_gateway_type'));
        $this->assertEquals('mantik', Setting::get('whatsapp_session'));
        $this->assertEquals('secret-api-token-12345', Setting::get('whatsapp_api_key'));
        $this->assertEquals('webhook-secret-999', Setting::get('whatsapp_webhook_secret'));
        $this->assertEquals('628123456789', Setting::get('whatsapp_admin_phone'));
        $this->assertEquals('MitraNet Hotspot', Setting::get('hotspot_brand_name'));

        // Pastikan WhatsAppService membaca konfigurasi terbaru
        $wa->loadConfiguration();
        $this->assertTrue($wa->isOpenWa());

        // Pastikan settings() mengembalikan data dari database
        $view = $controller->settings();
        $viewData = $view->getData()['settings'];

        $this->assertEquals('https://wa.mitrapedia.my.id/api/sessions/mantik/messages/send-text', $viewData['api_url']);
        $this->assertEquals('openwa', $viewData['gateway_type']);
        $this->assertEquals('mantik', $viewData['session']);
        $this->assertTrue($viewData['api_key_set']);
        $this->assertEquals('webhook-secret-999', $viewData['webhook_secret']);
        $this->assertEquals('628123456789', $viewData['admin_phone']);
        $this->assertEquals('MitraNet Hotspot', $viewData['brand_name']);
    }

    public function test_api_key_not_overwritten_if_left_empty(): void
    {
        Setting::set('whatsapp_api_key', 'existing-token-abc', 'whatsapp');

        $wa = new WhatsAppService();
        $controller = new WhatsAppVoucherController($wa);

        $request = Request::create('/whatsapp/settings', 'POST', [
            'api_url'        => 'https://wa.mitrapedia.my.id/api/sessions/mantik/messages/send-text',
            'gateway_type'   => 'openwa',
            'session'        => 'mantik',
            'api_key'        => '', // Dikosongkan oleh user
            'webhook_secret' => '',
            'admin_phone'    => '08123456789',
            'brand_name'     => 'MitraNet Hotspot',
        ]);

        $controller->updateSettings($request);

        // API Key lama harus tetap terjaga
        $this->assertEquals('existing-token-abc', Setting::get('whatsapp_api_key'));
    }

    public function test_auto_prefix_https_if_missing(): void
    {
        $wa = new WhatsAppService();
        $controller = new WhatsAppVoucherController($wa);

        $request = Request::create('/whatsapp/settings', 'POST', [
            'api_url'        => 'wa.mitrapedia.my.id/api/sessions/mantik/messages/send-text',
            'gateway_type'   => 'openwa',
            'session'        => 'mantik',
            'admin_phone'    => '08123456789',
        ]);

        $controller->updateSettings($request);

        $this->assertEquals('https://wa.mitrapedia.my.id/api/sessions/mantik/messages/send-text', Setting::get('whatsapp_api_url'));
    }
}
