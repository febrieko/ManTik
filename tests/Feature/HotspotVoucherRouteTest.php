<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HotspotVoucherRouteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hotspot_vouchers_page_is_accessible_without_show_error(): void
    {
        // Pastikan role dan permission ada
        $permission = Permission::firstOrCreate(['name' => 'view hotspot']);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/hotspot/vouchers');

        $response->assertStatus(200);
        $response->assertViewIs('hotspot.vouchers');
    }

    public function test_hotspot_generate_vouchers_page_is_accessible(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view hotspot']);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/hotspot/vouchers/generate');

        $response->assertStatus(200);
        $response->assertViewIs('hotspot.generate');
    }
}
