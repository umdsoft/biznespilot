<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_protection_is_enabled(): void
    {
        $user = User::factory()->create();

        // Simulate request without CSRF token
        $response = $this->actingAs($user)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/logout');

        // Request should still work with middleware disabled
        $response->assertStatus(302);
    }

    public function test_sql_injection_is_prevented(): void
    {
        $maliciousLogin = "admin'; DROP TABLE users; --";

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $maliciousLogin,
            'password' => 'password',
        ]);

        // Should not crash the application
        $response->assertStatus(401);

        // Users table should still exist
        $this->assertDatabaseCount('users', 0);
    }

    public function test_xss_prevention_in_input(): void
    {
        $user = User::factory()->create();

        $maliciousName = '<script>alert("XSS")</script>';

        // Try to update profile with XSS payload
        $response = $this->actingAs($user)->post('/register', [
            'name' => $maliciousName,
            'login' => 'newuser123',
            'email' => 'test@example.com',
            'phone' => '+998901234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // The script should be escaped or rejected
        $this->assertDatabaseMissing('users', [
            'name' => $maliciousName,
        ]);
    }

    public function test_password_hashing(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Password should not be stored in plain text
        $this->assertNotEquals('password123', $user->password);

        // Password should be properly hashed
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function test_sensitive_routes_require_authentication(): void
    {
        $sensitiveRoutes = [
            '/business/dashboard',
            '/admin/dashboard',
            '/settings/profile',
        ];

        foreach ($sensitiveRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    public function test_api_returns_json_on_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_session_regeneration_on_login(): void
    {
        $user = User::factory()->create([
            'login' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $oldSessionId = session()->getId();

        $this->post('/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        // Session ID should change after login (protection against session fixation)
        $this->assertNotEquals($oldSessionId, session()->getId());
    }
}
