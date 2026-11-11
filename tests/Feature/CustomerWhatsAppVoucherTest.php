<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use App\Models\HotspotVoucher;
use App\Models\WaVoucherRequest;
use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CustomerWhatsAppVoucherTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'sent'], 200)]);
        Setting::set('whatsapp_webhook_secret', '', 'whatsapp');
        Cache::flush();
    }

    public function test_registered_customer_can_check_balance(): void
    {
        $phone = '081234567890';
        $customer = Customer::create([
            'name'    => 'Ahmad Pelanggan',
            'phone'   => $phone,
            'balance' => 50000,
            'status'  => 'active',
        ]);

        $response = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone],
            'message' => 'SALDO',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'success',
            'balance' => 50000,
            'name'    => 'Ahmad Pelanggan',
        ]);
    }

    public function test_unregistered_number_cannot_check_balance(): void
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => '089999999999'],
            'message' => 'SALDO',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'customer_not_found',
        ]);
    }

    public function test_unregistered_number_cannot_request_paid_voucher(): void
    {
        $package = Package::create([
            'name'          => 'Paket Berbayar 10K',
            'slug'          => 'paket-10k',
            'type'          => 'hotspot',
            'price'         => 10000,
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        $response = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => '089988776655'],
            'message' => 'VOUCHER#PAKET-10K',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'customer_not_found',
        ]);
    }

    public function test_registered_customer_with_insufficient_balance_is_rejected(): void
    {
        $phone = '081299887766';
        $customer = Customer::create([
            'name'    => 'Budi Santoso',
            'phone'   => $phone,
            'balance' => 3000, // Saldo hanya 3.000
            'status'  => 'active',
        ]);

        $package = Package::create([
            'name'          => 'Paket Hemat 5K',
            'slug'          => 'paket-5k',
            'type'          => 'hotspot',
            'price'         => 5000, // Harga 5.000
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        $response = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone],
            'message' => 'VOUCHER#PAKET-5K',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'insufficient_balance',
        ]);
    }

    public function test_registered_customer_with_sufficient_balance_gets_confirmation_request_and_can_confirm(): void
    {
        $router = Router::create([
            'name'      => 'Router Pusat',
            'ssid'      => 'HOTSPOT_PUSAT',
            'host'      => '192.168.88.1',
            'username'  => 'admin',
            'password'  => 'admin',
            'port'      => 8728,
            'is_active' => true,
        ]);

        $phone = '081311223344';
        $customer = Customer::create([
            'name'    => 'Citra Dewi',
            'phone'   => $phone,
            'balance' => 20000,
            'status'  => 'active',
        ]);

        $package = Package::create([
            'name'          => 'Paket Super 10K',
            'slug'          => 'super-10k',
            'type'          => 'hotspot',
            'price'         => 10000,
            'duration_days' => 2,
            'is_active'     => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id'  => $router->id,
            'package_id' => $package->id,
            'code'       => 'VCR-SUPER-999',
            'is_used'    => false,
        ]);

        // 1. Request voucher pertama kali -> Harus memicu konfirmasi
        $response1 = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone],
            'message' => 'VOUCHER#SUPER-10K',
        ]);

        $response1->assertStatus(200);
        $response1->assertJson([
            'status' => 'confirmation_required',
            'price'  => 10000,
        ]);

        $confirmCode = $response1->json('confirm_code');
        $this->assertNotEmpty($confirmCode);

        // Pastikan saldo belum dipotong sebelum dikonfirmasi
        $customer->refresh();
        $this->assertEquals(20000, (float) $customer->balance);

        // 2. Balas YA <confirm_code> untuk konfirmasi
        $response2 = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone],
            'message' => "YA {$confirmCode}",
        ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'status'  => 'success',
            'voucher' => 'VCR-SUPER-999',
            'balance' => 10000, // Saldo tersisa 10.000
        ]);

        // Pastikan saldo customer benar-benar terpotong di database
        $customer->refresh();
        $this->assertEquals(10000, (float) $customer->balance);

        // Pastikan voucher ditandai terpakai
        $voucher->refresh();
        $this->assertTrue((bool) $voucher->is_used);

        // Pastikan WaVoucherRequest tercatat dengan customer_id & price_deducted
        $requestLog = WaVoucherRequest::where('voucher_code', 'VCR-SUPER-999')->first();
        $this->assertNotNull($requestLog);
        $this->assertEquals($customer->id, $requestLog->customer_id);
        $this->assertEquals(10000, (float) $requestLog->price_deducted);
        $this->assertEquals('success', $requestLog->status);

        // Pastikan mutasi saldo tercatat di buku mutasi (ledger)
        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id'    => $customer->id,
            'type'           => 'debit',
            'category'       => 'voucher_wa',
            'amount'         => 10000,
            'balance_before' => 20000,
            'balance_after'  => 10000,
            'reference_id'   => $requestLog->id,
        ]);
    }

    public function test_customer_can_cancel_pending_order(): void
    {
        $router = Router::create([
            'name'      => 'Router Cabang',
            'ssid'      => 'HOTSPOT_CABANG',
            'host'      => '192.168.88.2',
            'username'  => 'admin',
            'password'  => 'admin',
            'port'      => 8728,
            'is_active' => true,
        ]);

        $phone = '081399887711';
        $customer = Customer::create([
            'name'    => 'Dedi Kurniawan',
            'phone'   => $phone,
            'balance' => 15000,
            'status'  => 'active',
        ]);

        $package = Package::create([
            'name'          => 'Paket 5K Test',
            'slug'          => 'paket-5k-test',
            'type'          => 'hotspot',
            'price'         => 5000,
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        HotspotVoucher::create([
            'router_id'  => $router->id,
            'package_id' => $package->id,
            'code'       => 'VCR-DEDI-111',
            'is_used'    => false,
        ]);

        // Request voucher
        $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone],
            'message' => 'VOUCHER#PAKET-5K-TEST',
        ]);

        // Balas BATAL
        $responseCancel = $this->postJson('/api/whatsapp/webhook', [
            'sender'  => ['phone' => $phone],
            'message' => 'BATAL',
        ]);

        $responseCancel->assertStatus(200);
        $responseCancel->assertJson([
            'status' => 'order_cancelled',
        ]);

        // Saldo tidak boleh berkurang
        $customer->refresh();
        $this->assertEquals(15000, (float) $customer->balance);
    }
}
