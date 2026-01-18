<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_create_business(): void
    {
        $response = $this->actingAs($this->user)->post('/welcome/create-business', [
            'name' => 'Test Business',
            'category' => 'retail',
            'region' => 'Toshkent',
            'main_goals' => ['grow_revenue', 'increase_customers'],
        ]);

        $this->assertDatabaseHas('businesses', [
            'name' => 'Test Business',
            'category' => 'retail',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_switch_between_businesses(): void
    {
        $business1 = Business::factory()->create(['user_id' => $this->user->id]);
        $business2 = Business::factory()->create(['user_id' => $this->user->id]);

        // Set first business in session
        session(['current_business_id' => $business1->id]);

        // Switch to second business
        $response = $this->actingAs($this->user)->post("/switch-business/{$business2->id}");

        $this->assertEquals($business2->id, session('current_business_id'));
    }

    public function test_user_cannot_access_other_users_business(): void
    {
        $otherUser = User::factory()->create();
        $otherBusiness = Business::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->post("/switch-business/{$otherBusiness->id}");

        $response->assertForbidden();
    }

    public function test_business_dashboard_requires_authentication(): void
    {
        $response = $this->get('/business');

        $response->assertRedirect('/login');
    }

    public function test_user_with_business_can_access_dashboard(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        session(['current_business_id' => $business->id]);

        $response = $this->actingAs($this->user)->get('/business');

        $response->assertStatus(200);
    }
}
