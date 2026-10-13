<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Dashboard
            'view dashboard',
            // Routers
            'view routers', 'create routers', 'edit routers', 'delete routers',
            // PPPoE
            'view pppoe', 'create pppoe', 'edit pppoe', 'delete pppoe',
            // Hotspot
            'view hotspot', 'create hotspot', 'edit hotspot', 'delete hotspot',
            // Queue
            'view queue', 'create queue', 'edit queue', 'delete queue',
            // IP & DHCP
            'view ip', 'manage ip',
            // Traffic
            'view traffic',
            // Billing
            'view billing', 'create billing', 'edit billing', 'delete billing',
            // Reports
            'view reports', 'export reports',
            // Customers
            'view customers', 'create customers', 'edit customers', 'delete customers',
            // Notifications
            'view notifications', 'send notifications',
            // Users
            'view users', 'create users', 'edit users', 'delete users',
            'manage user router assignments',
            // Settings
            'view settings', 'edit settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $teknisi = Role::firstOrCreate(['name' => 'teknisi']);
        $customer = Role::firstOrCreate(['name' => 'customer']);

        // Assign permissions to roles
        $superAdmin->syncPermissions(Permission::all());

        $adminPerms = Permission::whereNotIn('name', ['view settings', 'edit settings', 'delete users'])->get();
        $admin->syncPermissions($adminPerms);

        $teknisiPerms = Permission::whereIn('name', [
            'view dashboard', 'view routers',
            'view pppoe', 'edit pppoe',
            'view hotspot', 'edit hotspot',
            'view queue',
            'view ip', 'view traffic',
            'view customers',
            'view notifications',
        ])->get();
        $teknisi->syncPermissions($teknisiPerms);

        $customerPerms = Permission::whereIn('name', [
            'view billing',
        ])->get();
        $customer->syncPermissions($customerPerms);

        // Create Super Admin User
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@mantik.test'],
            [
                'name'     => 'Super Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $superAdminUser->assignRole($superAdmin);

        // Create Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@mantik.test'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole($admin);

        // Create Teknisi User
        $teknisiUser = User::firstOrCreate(
            ['email' => 'teknisi@mantik.test'],
            [
                'name'     => 'Teknisi Jaringan',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $teknisiUser->assignRole($teknisi);

        // Create Sample Packages
        $packages = [
            [
                'name'           => 'Paket 5 Mbps',
                'slug'           => 'paket-5mbps',
                'bandwidth_up'   => '5M',
                'bandwidth_down' => '5M',
                'price'          => 100000,
                'duration_days'  => 30,
                'type'           => 'pppoe',
                'description'    => 'Paket internet rumahan 5 Mbps',
            ],
            [
                'name'           => 'Paket 10 Mbps',
                'slug'           => 'paket-10mbps',
                'bandwidth_up'   => '10M',
                'bandwidth_down' => '10M',
                'price'          => 175000,
                'duration_days'  => 30,
                'type'           => 'pppoe',
                'description'    => 'Paket internet standar 10 Mbps',
            ],
            [
                'name'           => 'Paket 20 Mbps',
                'slug'           => 'paket-20mbps',
                'bandwidth_up'   => '20M',
                'bandwidth_down' => '20M',
                'price'          => 250000,
                'duration_days'  => 30,
                'type'           => 'pppoe',
                'description'    => 'Paket internet cepat 20 Mbps',
            ],
            [
                'name'           => 'Hotspot 1 Hari',
                'slug'           => 'hotspot-1hari',
                'bandwidth_up'   => '5M',
                'bandwidth_down' => '10M',
                'price'          => 5000,
                'duration_days'  => 1,
                'type'           => 'hotspot',
                'description'    => 'Voucher hotspot harian',
            ],
            [
                'name'           => 'Hotspot 1 Minggu',
                'slug'           => 'hotspot-1minggu',
                'bandwidth_up'   => '5M',
                'bandwidth_down' => '10M',
                'price'          => 25000,
                'duration_days'  => 7,
                'type'           => 'hotspot',
                'description'    => 'Voucher hotspot mingguan',
            ],
        ];

        foreach ($packages as $package) {
            Package::firstOrCreate(['slug' => $package['slug']], $package);
        }

        $this->command->info('✅ MANTIK Seeder berhasil dijalankan!');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['superadmin@mantik.test', 'password', 'Super Admin'],
                ['admin@mantik.test', 'password', 'Admin'],
                ['teknisi@mantik.test', 'password', 'Teknisi'],
            ]
        );
    }
}
