<?php

namespace Tests\Feature;

use App\Models\HotspotVoucher;
use App\Models\Package;
use App\Models\PublicVoucherOrder;
use App\Models\Router;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicVoucherCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected Package $hotspotPackage;
    protected Router $router;
    protected HotspotVoucher $voucher1;
    protected HotspotVoucher $voucher2;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'tripay.co.id/*' => Http::response([
                'success' => true,
                'data'    => [
                    'reference'    => 'T-PUBLIC-999',
                    'checkout_url' => 'https://tripay.co.id/checkout/T-PUBLIC-999',
                    'qr_url'       => 'https://tripay.co.id/qr/T-PUBLIC-999.png',
                    'total_fee'    => 750,
                    'amount'       => 10750,
                ],
            ], 200),
            '*' => Http::response(['status' => 'sent'], 200),
        ]);

        Permission::firstOrCreate(['name' => 'view billing']);
        Permission::firstOrCreate(['name' => 'view dashboard']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view billing', 'view dashboard']);

        $this->adminUser = User::create([
            'name'      => 'Admin Public Orders',
            'email'     => 'admin.public.orders@example.com',
            'phone'     => '081111333444',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->router = Router::create([
            'name'      => 'Router Hotspot Public',
            'ssid'      => 'MANTIK-WIFI-PUBLIC',
            'host'      => '10.10.20.1',
            'port'      => 8728,
            'username'  => 'admin',
            'password'  => 'secret',
            'is_active' => true,
        ]);

        $this->hotspotPackage = Package::create([
            'name'           => 'Voucher Harian 10K Test',
            'bandwidth_up'   => 5,
            'bandwidth_down' => 10,
            'price'          => 10000,
            'duration_days'  => 1,
            'type'           => 'hotspot',
            'is_active'      => true,
        ]);

        $this->voucher1 = HotspotVoucher::create([
            'router_id'  => $this->router->id,
            'package_id' => $this->hotspotPackage->id,
            'code'       => 'PUBTEST01',
            'username'   => 'PUBTEST01',
            'password'   => 'PUBTEST01',
            'is_used'    => false,
        ]);

        $this->voucher2 = HotspotVoucher::create([
            'router_id'  => $this->router->id,
            'package_id' => $this->hotspotPackage->id,
            'code'       => 'PUBTEST02',
            'username'   => 'PUBTEST02',
            'password'   => 'PUBTEST02',
            'is_used'    => false,
        ]);
    }

    public function test_guest_can_view_public_voucher_checkout_page(): void
    {
        $this->get(route('public.voucher.index'))
            ->assertStatus(200)
            ->assertSee('Voucher Harian 10K Test')
            ->assertSee('Stok:');
    }

    public function test_guest_cannot_checkout_when_payment_gateway_disabled(): void
    {
        Setting::set('payment_gateway_enabled', false, 'payment_gateway');

        $this->post(route('public.voucher.checkout'), [
            'package_id'  => $this->hotspotPackage->id,
            'buyer_name'  => 'Tamu Test',
            'buyer_phone' => '081234567890',
        ])->assertSessionHas('error');

        $this->assertEquals(0, PublicVoucherOrder::where('buyer_name', 'Tamu Test')->count());
    }

    public function test_guest_can_checkout_and_receive_voucher_automatically_after_tripay_callback(): void
    {
        Setting::set('payment_gateway_enabled', true, 'payment_gateway');
        Setting::set('payment_gateway_provider', 'tripay', 'payment_gateway');
        Setting::set('payment_gateway_mode', 'sandbox', 'payment_gateway');
        Setting::set('payment_gateway_merchant_code', 'T0001', 'payment_gateway');
        Setting::set('payment_gateway_api_key', 'DEV-APIKEY123', 'payment_gateway');
        Setting::set('payment_gateway_private_key', 'PRIV-PUBLIC-KEY', 'payment_gateway');

        $res = $this->post(route('public.voucher.checkout'), [
            'package_id'  => $this->hotspotPackage->id,
            'router_id'   => $this->router->id,
            'buyer_name'  => 'Budi Tamu',
            'buyer_phone' => '081299887766',
        ]);

        $order = PublicVoucherOrder::where('buyer_name', 'Budi Tamu')->firstOrFail();

        $res->assertRedirect(route('public.voucher.status', $order->reference_code));
        $this->assertEquals('6281299887766', $order->buyer_phone);
        $this->assertEquals('tripay', $order->gateway);
        $this->assertEquals('https://tripay.co.id/checkout/T-PUBLIC-999', $order->payment_url);
        $this->assertEquals('pending', $order->status);

        // Sebelum bayar: polling status masih pending & voucher_code null
        $this->getJson(route('public.voucher.poll', $order->reference_code))
            ->assertStatus(200)
            ->assertJson([
                'status'       => 'pending',
                'voucher_code' => null,
            ]);

        // Simulasi webhook PAID dari Tripay
        $payload = json_encode([
            'merchant_ref' => $order->reference_code,
            'status'       => 'PAID',
        ]);
        $sig = hash_hmac('sha256', $payload, 'PRIV-PUBLIC-KEY');

        $this->call(
            'POST',
            '/api/payment-gateway/callback/tripay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => $sig],
            $payload
        )->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'Public voucher fulfilled',
        ]);

        $order->refresh();
        $this->voucher1->refresh();
        $this->voucher2->refresh();

        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->voucher_id);
        $this->assertTrue($this->voucher1->is_used);
        $this->assertFalse($this->voucher2->is_used);

        // Duplicate callback harus idempoten (tidak boleh memakan voucher kedua)
        $this->call(
            'POST',
            '/api/payment-gateway/callback/tripay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => $sig],
            $payload
        )->assertStatus(200)->assertJson(['message' => 'Already paid']);

        $this->voucher2->refresh();
        $this->assertFalse($this->voucher2->is_used);

        // Poll & status page sekarang menampilkan kode voucher
        $this->getJson(route('public.voucher.poll', $order->reference_code))
            ->assertStatus(200)
            ->assertJson([
                'status'       => 'paid',
                'voucher_code' => 'PUBTEST01',
                'ssid'         => 'MANTIK-WIFI-PUBLIC',
            ]);

        $this->get(route('public.voucher.status', $order->reference_code))
            ->assertStatus(200)
            ->assertSee('PUBTEST01');
    }

    public function test_paid_out_of_stock_order_can_be_retried_by_admin_and_admin_page_renders(): void
    {
        Setting::set('payment_gateway_enabled', true, 'payment_gateway');
        Setting::set('payment_gateway_provider', 'tripay', 'payment_gateway');
        Setting::set('payment_gateway_private_key', 'PRIV-PUBLIC-KEY', 'payment_gateway');
        Setting::set('payment_gateway_api_key', 'APIKEY', 'payment_gateway');

        // Habiskan kedua stok sementara
        $this->voucher1->update(['is_used' => true, 'used_at' => now()]);
        $this->voucher2->update(['is_used' => true, 'used_at' => now()]);

        $order = PublicVoucherOrder::create([
            'package_id'   => $this->hotspotPackage->id,
            'router_id'    => $this->router->id,
            'buyer_name'   => 'Tamu Kehabisan Stok',
            'amount'       => 10000,
            'total_amount' => 10000,
            'gateway'      => 'tripay',
            'status'       => 'pending',
        ]);

        $payload = json_encode([
            'merchant_ref' => $order->reference_code,
            'status'       => 'PAID',
        ]);
        $sig = hash_hmac('sha256', $payload, 'PRIV-PUBLIC-KEY');

        $this->call(
            'POST',
            '/api/payment-gateway/callback/tripay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => $sig],
            $payload
        )->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid_out_of_stock', $order->status);

        // Admin menambahkan stok baru lalu menekan "Alokasikan Stok"
        $newVoucher = HotspotVoucher::create([
            'router_id'  => $this->router->id,
            'package_id' => $this->hotspotPackage->id,
            'code'       => 'PUBRESTOCK',
            'username'   => 'PUBRESTOCK',
            'password'   => 'PUBRESTOCK',
            'is_used'    => false,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('public-orders.retry-fulfill', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $newVoucher->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertEquals($newVoucher->id, $order->voucher_id);
        $this->assertTrue($newVoucher->is_used);

        $this->actingAs($this->adminUser)
            ->get(route('public-orders.index'))
            ->assertStatus(200)
            ->assertSee($order->reference_code);
    }
}

