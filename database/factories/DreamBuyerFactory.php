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
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'is_primary' => false,
            // Demographics
            'age_range' => fake()->randomElement(['18-25', '25-35', '35-45', '45-55', '55+']),
            'gender' => fake()->randomElement(['male', 'female', 'any']),
            'location' => fake()->city(),
            'occupation' => fake()->jobTitle(),
            'income_level' => fake()->randomElement(['low', 'medium', 'high']),
            // Pain points and goals
            'pain_points' => fake()->paragraph(),
            'goals' => fake()->paragraph(),
            'interests' => fake()->sentence(),
            'objections' => fake()->optional()->sentence(),
            'buying_triggers' => fake()->optional()->sentence(),
            'preferred_channels' => fake()->randomElement(['telegram', 'phone', 'email', 'instagram']),
            // Nine Questions - "Sell Like Crazy" framework
            'q3_where_do_they_hang_out' => fake()->optional()->sentence(),
            'q5_what_are_they_afraid_of' => fake()->optional()->sentence(),
            'q6_what_are_they_frustrated_with' => fake()->optional()->paragraph(),
            'q8_what_do_they_secretly_want' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Create a primary dream buyer.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'priority' => 'high',
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
