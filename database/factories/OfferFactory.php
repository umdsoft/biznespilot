<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'value_proposition' => fake()->paragraph(),
            'target_audience' => fake()->sentence(),
            'pricing' => fake()->randomFloat(2, 100000, 10000000),
            'pricing_model' => fake()->randomElement(['fixed', 'subscription', 'tiered']),
            'status' => fake()->randomElement(['draft', 'active', 'paused']),
            'guarantees' => fake()->boolean(50) ? [fake()->sentence()] : [],
            'bonuses' => fake()->boolean(50) ? [fake()->sentence()] : [],
            'scarcity' => fake()->optional(0.3)->sentence(),
            'urgency' => fake()->optional(0.3)->sentence(),
            // Value Equation scores
            'dream_outcome_score' => fake()->numberBetween(1, 10),
            'perceived_likelihood_score' => fake()->numberBetween(1, 10),
            'time_delay_days' => fake()->numberBetween(1, 365),
            'effort_score' => fake()->numberBetween(1, 10),
            'guarantee_type' => fake()->randomElement(['money_back', 'result', 'conditional', null]),
            'guarantee_period_days' => fake()->optional(0.5)->numberBetween(7, 90),
        ];
    }

    /**
     * Create an active offer.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create a draft offer.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Create a high-value offer (good value equation).
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'dream_outcome_score' => 9,
            'perceived_likelihood_score' => 8,
            'time_delay_days' => 7,
            'effort_score' => 2,
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
     * With money back guarantee.
     */
    public function withGuarantee(): static
    {
        return $this->state(fn (array $attributes) => [
            'guarantee_type' => 'money_back',
            'guarantee_terms' => '100% pulni qaytarish kafolati',
            'guarantee_period_days' => 30,
        ]);
    }
}
