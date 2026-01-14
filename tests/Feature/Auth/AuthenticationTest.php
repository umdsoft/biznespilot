<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_login_screen(): void
    {
        $user = User::factory()->create([
            'login' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'login' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'login' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }

    public function test_registration_page_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'login' => 'newuser',
            'email' => 'test@example.com',
            'phone' => '+998901234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
    }

    public function test_users_cannot_register_with_duplicate_login(): void
    {
        User::factory()->create(['login' => 'existinguser']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'login' => 'existinguser',
            'email' => 'test@example.com',
            'phone' => '+998901234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('login');
    }
}
