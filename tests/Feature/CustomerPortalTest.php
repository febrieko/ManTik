<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\HotspotVoucher;
use App\Models\Package;
use App\Models\Router;
use App\Models\TopupTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use DatabaseTransactions;

    protected Customer $customer;
    protected User $customerUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'sent'], 200)]);

        foreach (['view customers', 'edit customers', 'view dashboard'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $adminRole    = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view customers', 'edit customers', 'view dashboard']);

        $this->customerUser = User::create([
            'name'      => 'Pelanggan Portal',
            'phone'     => '081234000999',
            'password'  => Hash::make('portalpass123'),
            'is_active' => true,
        ]);
        $this->customerUser->assignRole($customerRole);

        $this->customer = Customer::create([
            'user_id'       => $this->customerUser->id,
            'name'          => 'Pelanggan Portal',
            'phone'         => '081234000999',
            'customer_type' => 'regular',
            'status'        => 'active',
            'balance'       => 25000,
        ]);

        $this->adminUser = User::create([
            'name'      => 'Admin Portal Test',
            'email'     => 'adminportal' . uniqid() . '@mantik.test',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_customer_can_login_with_whatsapp_number_in_08_format_and_password(): void
    {
        $this->get(route('portal.login'))->assertStatus(200);

        $response = $this->post(route('portal.login.submit'), [
            'phone'    => '081234000999', // Format 08...
            'password' => 'portalpass123',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($this->customerUser);

        $this->get(route('portal.dashboard'))
            ->assertStatus(200)
            ->assertSee('Pelanggan Portal')
            ->assertSee('25.000');
    }

    public function test_customer_login_fails_with_wrong_password(): void
    {
        $response = $this->post(route('portal.login.submit'), [
            'phone'    => '6281234000999',
            'password' => 'salah123',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_customer_is_redirected_from_admin_routes_to_portal(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($this->customerUser)
            ->get(route('customers.index'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_admin_is_redirected_from_portal_to_admin_dashboard(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_customer_can_buy_hotspot_voucher_from_portal(): void
    {
        $router = Router::create([
            'name'      => 'Router Portal',
            'ssid'      => 'PORTAL_WIFI',
            'host'      => '192.168.88.50',
            'username'  => 'admin',
            'password'  => 'admin',
            'port'      => 8728,
            'is_active' => true,
        ]);

        $package = Package::create([
            'name'          => 'Paket Portal 10K',
            'slug'          => 'portal-10k',
            'type'          => 'hotspot',
            'price'         => 10000,
            'duration_days' => 1,
            'is_active'     => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id'  => $router->id,
            'package_id' => $package->id,
            'code'       => 'PRT-VCR-777',
            'is_used'    => false,
        ]);

        $response = $this->actingAs($this->customerUser)->post(route('portal.vouchers.buy'), [
            'package_id' => $package->id,
            'router_id'  => $router->id,
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('purchased_voucher');

        $this->customer->refresh();
        $this->assertEquals(15000, (float) $this->customer->balance);

        $voucher->refresh();
        $this->assertTrue((bool) $voucher->is_used);

        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id'    => $this->customer->id,
            'type'           => 'debit',
            'category'       => 'voucher_portal',
            'amount'         => 10000,
            'balance_before' => 25000,
            'balance_after'  => 15000,
        ]);
    }

    public function test_customer_can_request_topup_and_admin_can_approve(): void
    {
        // 1. Pelanggan ajukan topup 50.000
        $resTopup = $this->actingAs($this->customerUser)->post(route('portal.topup.request'), [
            'amount'         => 50000,
            'payment_method' => 'bank_transfer',
            'notes'          => 'Transfer Mandiri',
        ]);
        $resTopup->assertRedirect(route('portal.dashboard'));

        $topup = TopupTransaction::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($topup);
        $this->assertEquals('pending', $topup->status);
        $this->assertEquals(25000, (float) $this->customer->fresh()->balance);

        // 2. Admin menyetujui pengajuan top-up
        $resApprove = $this->actingAs($this->adminUser)->post(route('customers.topups.approve', $topup));
        $resApprove->assertSessionHas('success');

        $topup->refresh();
        $this->assertEquals('paid', $topup->status);

        $this->customer->refresh();
        $this->assertEquals(75000, (float) $this->customer->balance);
    }

    public function test_customer_can_update_portal_password(): void
    {
        $response = $this->actingAs($this->customerUser)->post(route('portal.password.update'), [
            'current_password'      => 'portalpass123',
            'password'              => 'newpass456',
            'password_confirmation' => 'newpass456',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpass456', $this->customerUser->fresh()->password));
    }
}

