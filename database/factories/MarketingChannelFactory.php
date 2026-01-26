<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\MarketingChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketingChannel>
 */
class MarketingChannelFactory extends Factory
{
    protected $model = MarketingChannel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['instagram', 'telegram', 'facebook', 'google_ads', 'paid_social', 'organic_social', 'referral'];

        return [
            'business_id' => Business::factory(),
            'name' => fake()->randomElement(['Instagram Ads', 'Facebook Ads', 'Google Ads', 'Telegram Channel', 'Referral Program']),
            'type' => fake()->randomElement($types),
            'platform' => fake()->optional(0.7)->randomElement(['instagram', 'facebook', 'telegram', 'google']),
            'description' => fake()->optional(0.5)->sentence(),
            'monthly_budget' => fake()->randomFloat(2, 0, 1000000),
            'settings' => [],
            'is_active' => true,
        ];
    }

    /**
     * Instagram channel.
     */
    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Instagram',
            'type' => 'instagram',
            'platform' => 'instagram',
        ]);
    }

    /**
     * Telegram channel.
     */
    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Telegram',
            'type' => 'telegram',
            'platform' => 'telegram',
        ]);
    }

    /**
     * Facebook channel.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Facebook',
            'type' => 'facebook',
            'platform' => 'facebook',
        ]);
    }

    /**
     * Google Ads channel.
     */
    public function googleAds(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Google Ads',
            'type' => 'google_ads',
            'platform' => 'google',
        ]);
    }

    /**
     * Inactive channel.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
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
