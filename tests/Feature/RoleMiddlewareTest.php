<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_dashboard_and_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    }

    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_staff_cannot_access_user_management_and_receives_403(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($staff)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_manager_cannot_access_user_management_and_receives_403(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $response = $this->actingAs($manager)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_manager_can_access_categories_and_products(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $this->actingAs($manager)->get('/categories')->assertStatus(200);
        $this->actingAs($manager)->get('/products')->assertStatus(200);
    }

    public function test_staff_cannot_access_categories_and_receives_403(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($staff)->get('/categories')->assertStatus(403);
    }
}
