<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\TopupTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'sent'], 200)]);

        foreach (['view customers', 'create customers', 'edit customers', 'delete customers'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view customers', 'create customers', 'edit customers', 'delete customers']);
        Role::firstOrCreate(['name' => 'customer']);

        $this->admin = User::create([
            'name'      => 'Admin Test',
            'email'     => 'admintest' . uniqid() . '@mantik.test',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->admin->assignRole($adminRole);
    }

    public function test_admin_can_create_customer_with_portal_account_and_initial_balance(): void
    {
        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'name'                  => 'Pelanggan Satu',
            'phone'                 => '081234500011',
            'email'                 => '',
            'customer_type'         => 'reseller',
            'status'                => 'active',
            'create_portal_account' => '1',
            'portal_password'       => 'rahasia123',
            'initial_balance'       => 50000,
        ]);

        $customer = Customer::where('phone', '6281234500011')->first();
        $this->assertNotNull($customer);
        $response->assertRedirect(route('customers.show', $customer));

        $this->assertEquals('reseller', $customer->customer_type);
        $this->assertEquals(50000, (float) $customer->balance);
        $this->assertNotNull($customer->user_id);

        $portalUser = User::find($customer->user_id);
        $this->assertNotNull($portalUser);
        $this->assertEquals('6281234500011', $portalUser->phone);
        $this->assertTrue($portalUser->hasRole('customer'));

        // Cek buku mutasi dan topup
        $this->assertDatabaseHas('topup_transactions', [
            'customer_id' => $customer->id,
            'gateway'     => 'manual',
            'status'      => 'paid',
            'amount'      => 50000,
        ]);

        $this->assertDatabaseHas('customer_balance_transactions', [
            'customer_id'    => $customer->id,
            'type'           => 'credit',
            'category'       => 'topup',
            'balance_before' => 0,
            'balance_after'  => 50000,
        ]);
    }

    public function test_duplicate_whatsapp_number_in_different_format_is_rejected(): void
    {
        Customer::create([
            'name'   => 'Pelanggan Pertama',
            'phone'  => '081299001122',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'name'          => 'Pelanggan Kedua',
            'phone'         => '6281299001122', // Sama setelah normalisasi
            'customer_type' => 'regular',
            'status'        => 'active',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertEquals(1, Customer::where('phone', '6281299001122')->count());
    }

    public function test_admin_can_topup_and_adjust_customer_balance(): void
    {
        $customer = Customer::create([
            'name'    => 'Pelanggan Topup',
            'phone'   => '081277778888',
            'status'  => 'active',
            'balance' => 10000,
        ]);

        // Topup 25.000
        $resTopup = $this->actingAs($this->admin)->post(route('customers.topup', $customer), [
            'amount'         => 25000,
            'payment_method' => 'bank_transfer',
            'notes'          => 'Transfer BCA',
            'send_wa'        => '1',
        ]);

        $resTopup->assertRedirect(route('customers.show', $customer));
        $customer->refresh();
        $this->assertEquals(35000, (float) $customer->balance);

        // Koreksi Debit 5.000
        $resAdj = $this->actingAs($this->admin)->post(route('customers.adjust-balance', $customer), [
            'adjustment_type' => 'debit',
            'amount'          => 5000,
            'notes'           => 'Koreksi kesalahan input',
        ]);

        $resAdj->assertRedirect(route('customers.show', $customer));
        $customer->refresh();
        $this->assertEquals(30000, (float) $customer->balance);

        $this->assertEquals(2, CustomerBalanceTransaction::where('customer_id', $customer->id)->count());
    }

    public function test_cannot_delete_customer_with_positive_balance(): void
    {
        $customer = Customer::create([
            'name'    => 'Pelanggan Bersaldo',
            'phone'   => '081255556666',
            'status'  => 'active',
            'balance' => 15000,
        ]);

        $res = $this->actingAs($this->admin)->delete(route('customers.destroy', $customer));
        $res->assertSessionHas('error');
        $this->assertNull($customer->fresh()->deleted_at);
    }

    public function test_admin_can_render_customer_pages(): void
    {
        $customer = Customer::create([
            'name'          => 'Pelanggan View Test',
            'phone'         => '081244443333',
            'customer_type' => 'regular',
            'status'        => 'active',
            'balance'       => 0,
        ]);

        $this->actingAs($this->admin)->get(route('customers.index'))->assertStatus(200)->assertSee('Pelanggan View Test');
        $this->actingAs($this->admin)->get(route('customers.create'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('customers.show', $customer))->assertStatus(200)->assertSee('Pelanggan View Test');
        $this->actingAs($this->admin)->get(route('customers.edit', $customer))->assertStatus(200);
    }
}
