<?php
namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_channels_page(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'channels@example.com',
            'password' => 'secret12345',
        ]);
        $tenant = Tenant::create([
            'name' => 'Test Enterprise',
            'slug' => 'test-enterprise',
            'status' => 'active',
            'owner_id' => $user->id,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner', 'is_current' => true]);

        $response = $this->actingAs($user)->get('/channels');
        $response->assertStatus(200);
        $response->assertSee('Connexion WhatsApp');
    }

    public function test_authenticated_user_can_access_settings_page(): void
    {
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'settings@example.com',
            'password' => 'secret12345',
        ]);
        $tenant = Tenant::create([
            'name' => 'Test Enterprise 2',
            'slug' => 'test-enterprise-2',
            'status' => 'active',
            'owner_id' => $user->id,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner', 'is_current' => true]);

        $response = $this->actingAs($user)->get('/settings');
        $response->assertStatus(200);
    }
}
