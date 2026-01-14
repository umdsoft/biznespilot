<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(5),
            'owner_id' => User::factory(),
            'category' => fake()->randomElement(['retail', 'services', 'manufacturing', 'technology', 'food']),
            'description' => fake()->paragraph(),
            'logo' => null,
            'phone' => '+998' . fake()->numerify('#########'),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'website' => fake()->optional()->url(),
            'industry_id' => null,
        ];
    }

    /**
     * Set a specific owner for the business.
     */
    public function forOwner(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user->id,
        ]);
    }

    /**
     * Create a retail business.
     */
    public function retail(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'retail',
        ]);
    }

    /**
     * Create a services business.
     */
    public function services(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'services',
        ]);
    }
}
