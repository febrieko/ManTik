<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
}
