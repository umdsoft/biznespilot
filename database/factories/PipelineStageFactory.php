<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PipelineStage>
 */
class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Yangi',
            'Aloqa qilindi',
            'Kvalifikatsiya',
            'Taklif',
            'Muzokara',
            'Yutilgan',
            'Yo\'qotilgan',
        ]);

        return [
            'business_id' => Business::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'order' => fake()->numberBetween(1, 10),
            'color' => fake()->hexColor(),
            'is_won' => false,
            'is_lost' => false,
        ];
    }

    /**
     * Create a won stage.
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Yutilgan',
            'slug' => 'won',
            'is_won' => true,
            'is_lost' => false,
        ]);
    }

    /**
     * Create a lost stage.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Yo\'qotilgan',
            'slug' => 'lost',
            'is_won' => false,
            'is_lost' => true,
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
