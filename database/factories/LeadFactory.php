<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'email' => fake()->optional(0.7)->safeEmail(),
            'phone' => '+998' . fake()->numerify('#########'),
            'company' => fake()->optional(0.5)->company(),
            'status' => fake()->randomElement(['new', 'contacted', 'qualified', 'proposal', 'negotiation']),
            'qualification_status' => fake()->randomElement(['new', 'mql', 'sql', 'disqualified']),
            'score' => fake()->optional(0.6)->numberBetween(0, 100),
            'score_category' => fake()->randomElement(['frozen', 'cold', 'cool', 'warm', 'hot']),
            'estimated_value' => fake()->optional(0.5)->randomFloat(2, 100000, 50000000),
            'region' => fake()->randomElement(array_keys(Lead::REGIONS)),
            'notes' => fake()->optional(0.3)->paragraph(),
        ];
    }

    /**
     * Create a new lead.
     */
    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'new',
            'qualification_status' => 'new',
        ]);
    }

    /**
     * Create a qualified lead (MQL).
     */
    public function mql(): static
    {
        return $this->state(fn (array $attributes) => [
            'qualification_status' => 'mql',
            'qualified_at' => now(),
        ]);
    }

    /**
     * Create a sales qualified lead (SQL).
     */
    public function sql(): static
    {
        return $this->state(fn (array $attributes) => [
            'qualification_status' => 'sql',
            'qualified_at' => now(),
        ]);
    }

    /**
     * Create a hot lead.
     */
    public function hot(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(80, 100),
            'score_category' => 'hot',
        ]);
    }

    /**
     * Create a cold lead.
     */
    public function cold(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 20),
            'score_category' => 'cold',
        ]);
    }

    /**
     * Create a won lead.
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'converted_at' => now(),
        ]);
    }

    /**
     * Create a lost lead.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'lost_reason' => fake()->randomElement(array_keys(Lead::LOST_REASONS)),
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

    /**
     * With UTM tracking.
     */
    public function withUtm(): static
    {
        return $this->state(fn (array $attributes) => [
            'utm_source' => fake()->randomElement(['google', 'facebook', 'telegram', 'instagram']),
            'utm_medium' => fake()->randomElement(['cpc', 'organic', 'social', 'email']),
            'utm_campaign' => fake()->slug(2),
        ]);
    }
}
