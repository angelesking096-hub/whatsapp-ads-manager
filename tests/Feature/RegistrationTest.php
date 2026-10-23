<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_an_empresa_space_is_created(): void
    {
        $response = $this->post('/register', [
            'name' => 'Demo Client', 'company' => 'Demo Company', 'email' => 'demo@example.com',
            'password' => 'StrongPass123!', 'password_confirmation' => 'StrongPass123!', 'terms' => '1',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'demo@example.com']);
        $this->assertDatabaseHas('tenants', ['slug' => 'demo-company']);
        $this->assertDatabaseHas('tenant_user', ['role' => 'owner', 'is_current' => 1]);
    }
}
