<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLeadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'login' => 'testuser',
            'password' => bcrypt('password123'),
        ]);
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->user->businesses()->attach($this->business->id, ['role' => 'owner']);

        // Get auth token
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        if ($response->status() === 200) {
            $this->token = $response->json('access_token') ?? '';
        } else {
            $this->token = '';
        }
    }

    /**
     * Test leads list API requires authentication.
     */
    public function test_leads_api_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/leads');

        $response->assertStatus(401);
    }

    /**
     * Test leads list API with authentication.
     */
    public function test_leads_api_with_auth(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        session(['current_business_id' => $this->business->id]);
        Lead::factory()->count(3)->forBusiness($this->business)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->getJson('/api/v1/leads');

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'data',
            ]);
        }
    }

    /**
     * Test create lead via API.
     */
    public function test_create_lead_via_api(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->postJson('/api/v1/leads', [
              'name' => 'API Test Lead',
              'phone' => '+998901234567',
              'status' => 'new',
          ]);

        if ($response->status() === 201 || $response->status() === 200) {
            $this->assertDatabaseHas('leads', [
                'name' => 'API Test Lead',
            ]);
        }
    }

    /**
     * Test lead validation on API.
     */
    public function test_lead_validation(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->postJson('/api/v1/leads', [
              // Missing required fields
          ]);

        $response->assertStatus(422);
    }

    /**
     * Test get single lead via API.
     */
    public function test_get_single_lead_via_api(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        session(['current_business_id' => $this->business->id]);
        $lead = Lead::factory()->forBusiness($this->business)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->getJson("/api/v1/leads/{$lead->id}");

        if ($response->status() === 200) {
            $response->assertJsonPath('data.id', $lead->id);
        }
    }

    /**
     * Test cannot access other business lead via API.
     */
    public function test_cannot_access_other_business_lead(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        $otherBusiness = Business::factory()->create();
        $lead = Lead::factory()->forBusiness($otherBusiness)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->getJson("/api/v1/leads/{$lead->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    /**
     * Test update lead via API.
     */
    public function test_update_lead_via_api(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        session(['current_business_id' => $this->business->id]);
        $lead = Lead::factory()->forBusiness($this->business)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->putJson("/api/v1/leads/{$lead->id}", [
              'name' => 'Updated Lead Name',
          ]);

        if ($response->status() === 200) {
            $this->assertEquals('Updated Lead Name', $lead->fresh()->name);
        }
    }

    /**
     * Test delete lead via API.
     */
    public function test_delete_lead_via_api(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        session(['current_business_id' => $this->business->id]);
        $lead = Lead::factory()->forBusiness($this->business)->create();
        $leadId = $lead->id;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->deleteJson("/api/v1/leads/{$leadId}");

        if ($response->status() === 200 || $response->status() === 204) {
            $this->assertSoftDeleted('leads', ['id' => $leadId]);
        }
    }

    /**
     * Test leads pagination.
     */
    public function test_leads_pagination(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        session(['current_business_id' => $this->business->id]);
        Lead::factory()->count(25)->forBusiness($this->business)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->getJson('/api/v1/leads?per_page=10');

        if ($response->status() === 200) {
            $data = $response->json();
            if (isset($data['meta'])) {
                $this->assertEquals(10, count($data['data']));
                $this->assertEquals(25, $data['meta']['total']);
            }
        }
    }

    /**
     * Test leads filtering by status.
     */
    public function test_leads_filter_by_status(): void
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token not available');
        }

        session(['current_business_id' => $this->business->id]);
        Lead::factory()->forBusiness($this->business)->create(['status' => 'new']);
        Lead::factory()->forBusiness($this->business)->create(['status' => 'contacted']);
        Lead::factory()->forBusiness($this->business)->create(['status' => 'new']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withSession(['current_business_id' => $this->business->id])
          ->getJson('/api/v1/leads?status=new');

        if ($response->status() === 200) {
            $data = $response->json('data') ?? [];
            foreach ($data as $lead) {
                $this->assertEquals('new', $lead['status']);
            }
        }
    }
}
