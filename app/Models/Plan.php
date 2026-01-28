<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuid;

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'limits',
        'features',
        'is_active',
        'sort_order',
        // Legacy columns (backward compatibility)
        'business_limit',
        'team_member_limit',
        'lead_limit',
        'chatbot_channel_limit',
        'telegram_bot_limit',
        'has_instagram',
        'audio_minutes_limit',
        'ai_requests_limit',
        'storage_limit_mb',
        'instagram_dm_limit',
        'content_posts_limit',
        'has_amocrm',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'limits' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'has_amocrm' => 'boolean',
        'has_instagram' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
    ];

    /**
     * Get the subscriptions for the plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get a specific limit value.
     * Returns -1 for unlimited, null if not set.
     */
    public function getLimit(string $key, $default = null): ?int
    {
        $limits = $this->limits ?? [];
        $value = $limits[$key] ?? $default;

        return $value !== null ? (int) $value : null;
    }

    /**
     * Check if a limit is unlimited (-1 or null).
     */
    public function isLimitUnlimited(string $key): bool
    {
        $limit = $this->getLimit($key);
        return $limit === -1 || $limit === null;
    }

    /**
     * Check if a feature is enabled.
     */
    public function hasFeature(string $key): bool
    {
        $features = $this->features ?? [];
        return (bool) ($features[$key] ?? false);
    }

    /**
     * Get all enabled features.
     */
    public function getEnabledFeatures(): array
    {
        $features = $this->features ?? [];
        return array_keys(array_filter($features));
    }

    /**
     * Get all limits as array.
     */
    public function getAllLimits(): array
    {
        return $this->limits ?? [];
    }

    /**
     * Get active plans ordered by price.
     */
    public static function getActivePlans()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();
    }

    /**
     * Get plan by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
