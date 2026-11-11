<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Package;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Setting;
use App\Models\TopupTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingAndPaymentGatewayTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $customerUser;
    protected Customer $customer;
    protected Package $pppoePackage;
    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'tripay.co.id/*' => Http::response([
                'success' => true,
                'data'    => [
                    'reference'    => 'T1234567890',
                    'checkout_url' => 'https://tripay.co.id/checkout/T1234567890',
                    'qr_url'       => 'https://tripay.co.id/qr/T1234567890.png',
                    'total_fee'    => 1500,
                    'amount'       => 101500,
                ],
            ], 200),
            '*' => Http::response(['status' => 'sent'], 200),
        ]);

        foreach (['view billing', 'view settings', 'view customers', 'view dashboard'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $adminRole    = Role::firstOrCreate(['name' => 'admin']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $adminRole->givePermissionTo(['view billing', 'view settings', 'view customers', 'view dashboard']);

        $this->adminUser = User::create([
            'name'      => 'Admin Billing',
            'email'     => 'admin.billing.test@example.com',
            'phone'     => '081111222333',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->customerUser = User::create([
            'name'      => 'Pelanggan Billing',
            'phone'     => '081234555666',
            'password'  => Hash::make('portal123'),
            'is_active' => true,
        ]);
        $this->customerUser->assignRole($customerRole);

        $this->customer = Customer::create([
            'user_id'       => $this->customerUser->id,
            'name'          => 'Pelanggan Billing',
            'phone'         => '081234555666',
            'email'         => 'pelanggan.billing@example.com',
            'customer_type' => 'regular',
            'balance'       => 300000,
            'status'        => 'active',
        ]);

        $this->router = Router::create([
            'name'      => 'Router Billing Test',
            'host'      => '10.10.10.2',
            'port'      => 8728,
            'username'  => 'admin',
            'password'  => 'secret',
            'is_active' => true,
        ]);

        $this->pppoePackage = Package::create([
            'name'           => 'Home 20 Mbps Test',
            'bandwidth_up'   => 20,
            'bandwidth_down' => 20,
            'price'          => 150000,
            'duration_days'  => 30,
            'type'           => 'pppoe',
            'is_active'      => true,
        ]);
    }

    public function test_admin_can_generate_monthly_billing_idempotently(): void
    {
        PppoeUser::create([
            'router_id'   => $this->router->id,
            'customer_id' => $this->customer->id,
            'package_id'  => $this->pppoePackage->id,
            'username'    => 'pppoe_cust_01',
            'password'    => '123456',
            'profile'     => 'Home-20M',
            'is_disabled' => false,
        ]);

        // Generate bulan target tertentu
        $res1 = $this->actingAs($this->adminUser)
            ->post(route('billing.generate-monthly'), ['month' => '2026-11']);

        $res1->assertRedirect(route('billing.index'));
        $this->assertDatabaseHas('billings', [
            'customer_id' => $this->customer->id,
            'package_id'  => $this->pppoePackage->id,
            'amount'      => 150000.00,
            'status'      => 'unpaid',
        ]);

        $countAfterFirst = Billing::where('customer_id', $this->customer->id)->count();
        $this->assertEquals(1, $countAfterFirst);

        // Generate ulang bulan yang sama tidak boleh membuat duplikat
        $this->actingAs($this->adminUser)
            ->post(route('billing.generate-monthly'), ['month' => '2026-11'])
            ->assertRedirect(route('billing.index'));

        $this->assertEquals(1, Billing::where('customer_id', $this->customer->id)->count());
    }

    public function test_admin_can_pay_billing_by_deducting_customer_deposit_balance(): void
    {
        $billing = Billing::create([
            'customer_id'  => $this->customer->id,
            'package_id'   => $this->pppoePackage->id,
            'amount'       => 150000,
            'paid_amount'  => 0,
            'period_start' => '2026-11-01',
            'period_end'   => '2026-11-30',
            'due_date'     => '2026-11-10',
            'status'       => 'unpaid',
        ]);

        $res = $this->actingAs($this->adminUser)
            ->post(route('billing.pay', $billing), [
                'amount'         => 150000,
                'payment_method' => 'balance',
                'notes'          => 'Potong deposit bulanan',
            ]);

        $res->assertSessionHas('success');

        $billing->refresh();
        $this->customer->refresh();

        $this->assertEquals('paid', $billing->status);
        $this->assertEquals('balance', $billing->payment_method);
        $this->assertEquals(150000.0, (float) $billing->paid_amount);
        $this->assertEquals(150000.0, (float) $this->customer->balance);

        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id'    => $this->customer->id,
            'type'           => 'debit',
            'category'       => 'billing_payment',
            'reference_type' => Billing::class,
            'reference_id'   => $billing->id,
            'amount'         => 150000.00,
        ]);
    }

    public function test_customer_can_pay_own_billing_with_balance_from_portal_and_cannot_pay_others(): void
    {
        $ownBilling = Billing::create([
            'customer_id'  => $this->customer->id,
            'package_id'   => $this->pppoePackage->id,
            'amount'       => 150000,
            'paid_amount'  => 0,
            'period_start' => '2026-11-01',
            'period_end'   => '2026-11-30',
            'due_date'     => '2026-11-10',
            'status'       => 'unpaid',
        ]);

        $otherCustomer = Customer::create([
            'name'   => 'Pelanggan Lain',
            'phone'  => '089999888777',
            'status' => 'active',
        ]);

        $otherBilling = Billing::create([
            'customer_id'  => $otherCustomer->id,
            'amount'       => 100000,
            'paid_amount'  => 0,
            'period_start' => '2026-11-01',
            'period_end'   => '2026-11-30',
            'due_date'     => '2026-11-10',
            'status'       => 'unpaid',
        ]);

        // Forbidden for another customer's billing
        $this->actingAs($this->customerUser)
            ->post(route('portal.billing.pay-balance', $otherBilling))
            ->assertStatus(403);

        // Pay own billing
        $res = $this->actingAs($this->customerUser)
            ->post(route('portal.billing.pay-balance', $ownBilling));

        $res->assertRedirect(route('portal.dashboard'));
        $res->assertSessionHas('success');

        $ownBilling->refresh();
        $this->customer->refresh();

        $this->assertEquals('paid', $ownBilling->status);
        $this->assertEquals(150000.0, (float) $this->customer->balance);
    }

    public function test_admin_can_configure_payment_gateway_and_portal_creates_tripay_invoice(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('payment-gateway.settings.update'), [
                'enabled'         => '1',
                'provider'        => 'tripay',
                'mode'            => 'sandbox',
                'merchant_code'   => 'T0001',
                'api_key'         => 'DEV-APIKEY123',
                'private_key'     => 'PRIVKEY-XYZ',
                'default_channel' => 'QRIS',
            ])
            ->assertRedirect(route('payment-gateway.settings'));

        $this->assertTrue((bool) Setting::get('payment_gateway_enabled'));
        $this->assertEquals('tripay', Setting::get('payment_gateway_provider'));

        $res = $this->actingAs($this->customerUser)
            ->post(route('portal.topup.request'), [
                'amount'         => 100000,
                'payment_method' => 'payment_gateway',
            ]);

        $res->assertRedirect(route('portal.dashboard'));
        $res->assertSessionHas('payment_url', 'https://tripay.co.id/checkout/T1234567890');

        $this->assertDatabaseHas('topup_transactions', [
            'customer_id'       => $this->customer->id,
            'gateway'           => 'tripay',
            'gateway_reference' => 'T1234567890',
            'payment_url'       => 'https://tripay.co.id/checkout/T1234567890',
            'status'            => 'pending',
        ]);
    }

    public function test_tripay_webhook_verifies_signature_and_credits_balance_idempotently(): void
    {
        Setting::set('payment_gateway_enabled', true, 'payment_gateway');
        Setting::set('payment_gateway_provider', 'tripay', 'payment_gateway');
        Setting::set('payment_gateway_private_key', 'PRIV-SECRET-KEY', 'payment_gateway');
        Setting::set('payment_gateway_api_key', 'API-KEY', 'payment_gateway');

        $topup = TopupTransaction::create([
            'customer_id'    => $this->customer->id,
            'amount'         => 100000,
            'fee'            => 0,
            'total_amount'   => 100000,
            'gateway'        => 'tripay',
            'payment_method' => 'qris',
            'status'         => 'pending',
        ]);

        $payload = json_encode([
            'merchant_ref' => $topup->reference_code,
            'status'       => 'PAID',
        ]);

        // 1. Invalid signature -> 401
        $this->call(
            'POST',
            '/api/payment-gateway/callback/tripay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => 'wrong-signature'],
            $payload
        )->assertStatus(401);

        // 2. Valid HMAC-SHA256 signature -> credits balance
        $validSig = hash_hmac('sha256', $payload, 'PRIV-SECRET-KEY');

        $this->call(
            'POST',
            '/api/payment-gateway/callback/tripay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => $validSig],
            $payload
        )->assertStatus(200)->assertJson(['success' => true]);

        $topup->refresh();
        $this->customer->refresh();

        $this->assertEquals('paid', $topup->status);
        $this->assertEquals(400000.0, (float) $this->customer->balance);

        // 3. Duplicate callback is idempotent (balance remains 400000)
        $this->call(
            'POST',
            '/api/payment-gateway/callback/tripay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => $validSig],
            $payload
        )->assertStatus(200)->assertJson(['message' => 'Already paid']);

        $this->customer->refresh();
        $this->assertEquals(400000.0, (float) $this->customer->balance);
        $this->assertEquals(1, CustomerBalanceTransaction::where('reference_id', $topup->id)->where('reference_type', TopupTransaction::class)->count());
    }

    public function test_midtrans_webhook_verifies_signature_and_credits_balance(): void
    {
        Setting::set('payment_gateway_enabled', true, 'payment_gateway');
        Setting::set('payment_gateway_provider', 'midtrans', 'payment_gateway');
        Setting::set('payment_gateway_api_key', 'SB-Mid-server-TESTKEY', 'payment_gateway');

        $topup = TopupTransaction::create([
            'customer_id'    => $this->customer->id,
            'amount'         => 50000,
            'fee'            => 0,
            'total_amount'   => 50000,
            'gateway'        => 'midtrans',
            'payment_method' => 'snap',
            'status'         => 'pending',
        ]);

        $orderId     = $topup->reference_code;
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $sig         = hash('sha512', $orderId . $statusCode . $grossAmount . 'SB-Mid-server-TESTKEY');

        $this->postJson('/api/payment-gateway/callback/midtrans', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
            'signature_key'      => $sig,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $this->customer->refresh();
        $this->assertEquals(350000.0, (float) $this->customer->balance);
    }

    public function test_admin_can_render_billing_and_payment_gateway_pages(): void
    {
        $billing = Billing::create([
            'customer_id'  => $this->customer->id,
            'package_id'   => $this->pppoePackage->id,
            'amount'       => 150000,
            'paid_amount'  => 0,
            'period_start' => '2026-11-01',
            'period_end'   => '2026-11-30',
            'due_date'     => '2026-11-10',
            'status'       => 'unpaid',
        ]);

        $this->actingAs($this->adminUser)->get(route('billing.index'))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('billing.create'))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('billing.invoice', $billing))->assertStatus(200);
        $this->actingAs($this->adminUser)->get(route('payment-gateway.settings'))->assertStatus(200);
        $this->actingAs($this->adminUser)->post(route('billing.remind', $billing))->assertSessionHas('success');
        $this->actingAs($this->customerUser)->get(route('portal.dashboard'))->assertStatus(200)->assertSee($billing->invoice_number);
    }
}
