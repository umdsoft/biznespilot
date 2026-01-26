<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

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
            'email' => fake()->optional(0.8)->safeEmail(),
            'phone' => '+998' . fake()->numerify('#########'),
            'company' => fake()->optional(0.5)->company(),
            'address' => fake()->optional(0.5)->address(),
            'city' => fake()->optional(0.5)->city(),
            'region' => fake()->optional(0.5)->randomElement(['tashkent', 'samarkand', 'bukhara', 'fergana']),
            'status' => fake()->randomElement(['active', 'inactive', 'churned']),
            'type' => fake()->randomElement(['individual', 'business']),
            'total_spent' => fake()->randomFloat(2, 0, 50000000),
            'lifetime_value' => fake()->randomFloat(2, 0, 100000000),
            'orders_count' => fake()->numberBetween(0, 50),
            'average_order_value' => fake()->randomFloat(2, 100000, 5000000),
            'churn_risk_level' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
        ];
    }

    /**
     * Active customer.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'churned_at' => null,
        ]);
    }

    /**
     * Churned customer.
     */
    public function churned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'churned',
            'churned_at' => now()->subDays(fake()->numberBetween(1, 90)),
            'churn_reason' => fake()->randomElement(['price', 'service', 'competitor', 'other']),
        ]);
    }

    /**
     * High value customer.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_spent' => fake()->randomFloat(2, 10000000, 100000000),
            'lifetime_value' => fake()->randomFloat(2, 20000000, 200000000),
            'orders_count' => fake()->numberBetween(10, 100),
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
