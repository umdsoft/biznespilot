<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->slug(2),
            'type' => fake()->randomElement(['broadcast', 'drip', 'trigger', 'email', 'social']),
            'description' => fake()->optional(0.5)->paragraph(),
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'completed']),
            'budget' => fake()->randomFloat(2, 0, 10000000),
            'starts_at' => fake()->optional(0.5)->dateTimeBetween('now', '+7 days'),
            'ends_at' => fake()->optional(0.5)->dateTimeBetween('+7 days', '+30 days'),
            'target_audience' => [
                'segments' => [fake()->randomElement(['all', 'new', 'active', 'inactive'])],
            ],
            'settings' => [],
            'metrics' => [],
        ];
    }

    /**
     * Draft campaign.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Active campaign.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Completed campaign.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Assign to a specific business.
     */
    public function forBusiness(Business $business): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $business->id,
        ]);
    }
}
