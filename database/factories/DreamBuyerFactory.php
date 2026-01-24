<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\DreamBuyer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DreamBuyer>
 */
class DreamBuyerFactory extends Factory
{
    protected $model = DreamBuyer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->randomElement(['Premium Mijoz', 'Startup Founder', 'Kichik Biznes Egasi', 'Korporativ Mijoz']),
            'description' => fake()->paragraph(),
            'priority' => fake()->numberBetween(1, 5),
            'is_primary' => false,
            // 9 ta savol - "Sell Like Crazy" framework
            'where_spend_time' => fake()->sentence(),
            'info_sources' => fake()->sentence(),
            'frustrations' => fake()->paragraph(),
            'dreams' => fake()->paragraph(),
            'fears' => fake()->sentence(),
            'communication_preferences' => fake()->randomElement(['telegram', 'phone', 'email', 'in_person']),
            'language_style' => fake()->sentence(),
            'daily_routine' => fake()->paragraph(),
            'happiness_triggers' => fake()->sentence(),
        ];
    }

    /**
     * Create a primary dream buyer.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'priority' => 1,
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
