<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_user_cannot_access_super_admin(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => 'secret12345',
            'is_platform_admin' => false,
        ]);
        $tenant = Tenant::create([
            'name' => 'Regular Enterprise',
            'slug' => 'regular-enterprise',
            'status' => 'active',
            'owner_id' => $user->id,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner', 'is_current' => true]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_all_admin_pages(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'secret12345',
            'is_platform_admin' => true,
        ]);
        $tenant = Tenant::create([
            'name' => 'Admin Enterprise',
            'slug' => 'admin-enterprise',
            'status' => 'active',
            'owner_id' => $admin->id,
        ]);
        $tenant->users()->attach($admin->id, ['role' => 'owner', 'is_current' => true]);

        $this->actingAs($admin)->get('/admin')->assertStatus(200)->assertSee('Tableau de bord Super Admin');
        $this->actingAs($admin)->get('/admin/tenants')->assertStatus(200)->assertSee('Gestion des entreprises clientes');
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee('Tous les utilisateurs');
        $this->actingAs($admin)->get('/admin/campaigns')->assertStatus(200)->assertSee('Supervision de toutes les campagnes');
    }

    public function test_super_admin_can_toggle_tenant_status(): void
    {
        $admin = User::create([
            'name' => 'Super Admin 2',
            'email' => 'admin2@example.com',
            'password' => 'secret12345',
            'is_platform_admin' => true,
        ]);
        $tenant = Tenant::create([
            'name' => 'Client Enterprise',
            'slug' => 'client-enterprise',
            'status' => 'active',
            'owner_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post("/admin/tenants/{$tenant->id}/toggle-status");
        $response->assertSessionHas('success');
        $this->assertEquals('suspended', $tenant->fresh()->status);
    }
}
