<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health/ping');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
            ]);
    }

    public function test_status_endpoint_returns_health_checks(): void
    {
        $response = $this->getJson('/health/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'environment',
                'checks' => [
                    'app',
                    'database',
                    'cache',
                    'storage',
                ],
            ]);
    }

    public function test_ready_endpoint_returns_ready(): void
    {
        $response = $this->getJson('/health/ready');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ready',
            ]);
    }

    public function test_live_endpoint_returns_alive(): void
    {
        $response = $this->getJson('/health/live');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'alive',
            ]);
    }
}
