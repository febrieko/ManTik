<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\User;
use App\Models\UserRouterAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserRuleSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $admin;
    protected User $teknisi;
    protected User $customerUser;
    protected Customer $customer;
    protected Router $assignedRouter;
    protected Router $unassignedRouter;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'sent'], 200)]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view routers', 'create routers', 'edit routers', 'delete routers',
            'view pppoe', 'create pppoe', 'edit pppoe', 'delete pppoe',
            'view hotspot', 'create hotspot', 'edit hotspot', 'delete hotspot',
            'view queue', 'create queue', 'edit queue', 'delete queue',
            'view ip', 'manage ip',
            'view traffic',
            'view billing', 'create billing', 'edit billing', 'delete billing',
            'view reports', 'export reports',
            'view customers', 'create customers', 'edit customers', 'delete customers',
            'view notifications', 'send notifications',
            'view users', 'create users', 'edit users', 'delete users',
            'manage user router assignments',
            'view settings', 'edit settings',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $adminRole      = Role::firstOrCreate(['name' => 'admin']);
        $teknisiRole    = Role::firstOrCreate(['name' => 'teknisi']);
        $customerRole   = Role::firstOrCreate(['name' => 'customer']);

        $superAdminRole->syncPermissions(Permission::all());
        $adminRole->syncPermissions(
            Permission::whereNotIn('name', ['view settings', 'edit settings', 'delete users'])->get()
        );
        $teknisiRole->syncPermissions(
            Permission::whereIn('name', [
                'view dashboard', 'view routers',
                'view pppoe', 'edit pppoe',
                'view hotspot', 'edit hotspot',
                'view queue',
                'view ip', 'view traffic',
                'view customers',
                'view notifications',
            ])->get()
        );
        $customerRole->syncPermissions(Permission::whereIn('name', ['view billing'])->get());

        $this->superAdmin = User::create([
            'name'      => 'Super Admin Test',
            'email'     => 'sa.rule.' . uniqid() . '@mantik.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->admin = User::create([
            'name'      => 'Admin Rule Test',
            'email'     => 'admin.rule.' . uniqid() . '@mantik.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->admin->assignRole($adminRole);

        $this->teknisi = User::create([
            'name'      => 'Teknisi Rule Test',
            'email'     => 'teknisi.rule.' . uniqid() . '@mantik.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->teknisi->assignRole($teknisiRole);

        $this->customerUser = User::create([
            'name'      => 'Customer Rule Hidden',
            'phone'     => '081234009988',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->customerUser->assignRole($customerRole);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name'    => 'Customer Rule Hidden',
            'phone'   => '081234009988',
            'status'  => 'active',
        ]);

        $this->assignedRouter = Router::create([
            'name'      => 'Router Assigned',
            'host'      => '10.10.30.1',
            'port'      => 8728,
            'username'  => 'admin',
            'password'  => 'secret',
            'is_active' => true,
        ]);

        $this->unassignedRouter = Router::create([
            'name'      => 'Router Unassigned',
            'host'      => '10.10.30.2',
            'port'      => 8728,
            'username'  => 'admin',
            'password'  => 'secret',
            'is_active' => true,
        ]);

        UserRouterAssignment::create([
            'user_id'     => $this->teknisi->id,
            'router_id'   => $this->assignedRouter->id,
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_teknisi_is_blocked_from_sensitive_routes_and_customer_mutations(): void
    {
        $this->actingAs($this->teknisi);

        // Teknisi boleh view routers, customers, dan router yang di-assign
        $this->get(route('routers.index'))->assertStatus(200);
        $this->get(route('customers.index'))->assertStatus(200);
        $this->get(route('routers.show', $this->assignedRouter))->assertStatus(200);

        // Teknisi TIDAK boleh membuka/mengubah router yang bukan jatahnya
        $this->get(route('routers.show', $this->unassignedRouter))->assertStatus(403);

        $unassignedPppoe = PppoeUser::create([
            'router_id' => $this->unassignedRouter->id,
            'username'  => 'pppoe_unassigned_' . uniqid(),
            'password'  => '123456',
            'profile'   => 'default',
        ]);
        $this->post(route('pppoe.toggle', $unassignedPppoe))->assertStatus(403);

        // Teknisi TIDAK boleh tambah/edit/hapus router, paket, WA/Telegram, users, billing, topup/adjust customer
        $this->get(route('routers.create'))->assertStatus(403);
        $this->post(route('routers.store'), [])->assertStatus(403);
        $this->get(route('packages.index'))->assertStatus(403);
        $this->get(route('whatsapp.settings'))->assertStatus(403);
        $this->get(route('telegram.settings'))->assertStatus(403);
        $this->get(route('users.index'))->assertStatus(403);
        $this->get(route('billing.index'))->assertStatus(403);
        $this->get(route('settings.index'))->assertStatus(403);
        $this->post(route('customers.topup', $this->customer), ['amount' => 10000])->assertStatus(403);
        $this->post(route('notifications.test'))->assertStatus(403);
    }

    public function test_admin_cannot_escalate_to_super_admin_or_delete_users(): void
    {
        $this->actingAs($this->admin);

        // Admin dapat melihat/membuka form user tapi opsi super-admin ditolak
        $this->post(route('users.store'), [
            'name'                  => 'Evil Escalation',
            'email'                 => 'evil.' . uniqid() . '@mantik.test',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'super-admin',
        ])->assertSessionHasErrors('role');

        // Admin tidak boleh edit/update/ganti password akun super-admin
        $this->get(route('users.edit', $this->superAdmin))->assertStatus(403);
        $this->put(route('users.update', $this->superAdmin), [
            'name'  => 'Hacked SA',
            'email' => $this->superAdmin->email,
            'role'  => 'admin',
        ])->assertStatus(403);
        $this->put(route('users.update-password', $this->superAdmin), [
            'password'              => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertStatus(403);

        // Admin tidak punya permission delete users
        $this->delete(route('users.destroy', $this->teknisi))->assertStatus(403);
        $this->delete(route('users.destroy', $this->superAdmin))->assertStatus(403);
    }

    public function test_super_admin_can_access_settings_and_manage_staff_only_list(): void
    {
        $this->actingAs($this->superAdmin);

        // Daftar users tidak menampilkan akun portal customer
        $this->get(route('users.index'))
            ->assertStatus(200)
            ->assertSee($this->admin->name)
            ->assertDontSee('Customer Rule Hidden');

        $this->get(route('settings.index'))->assertStatus(200);
        $this->get(route('packages.index'))->assertStatus(200);
    }

    public function test_deactivated_staff_user_is_logged_out_on_next_request(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('dashboard'))->assertStatus(200);

        $this->admin->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
