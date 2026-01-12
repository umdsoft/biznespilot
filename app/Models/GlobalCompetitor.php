<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GlobalCompetitor extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'website',
        'phone',
        'email',
        'description',
        'industry',
        'industry_id',
        'region',
        'district',
        'address',
        'instagram_handle',
        'telegram_handle',
        'facebook_page',
        'tiktok_handle',
        'youtube_channel',
        'swot_data',
        'swot_updated_at',
        'swot_contributors_count',
        'is_verified',
        'verified_at',
        'report_count',
    ];

    protected $casts = [
        'swot_data' => 'array',
        'swot_updated_at' => 'datetime',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate UUID
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            // Generate slug
            if (empty($model->slug)) {
                $baseSlug = Str::slug($model->name);
                $slug = $baseSlug;
                $counter = 1;

                // Add region/district to make unique
                if ($model->region) {
                    $slug = $baseSlug . '-' . Str::slug($model->region);
                }
                if ($model->district) {
                    $slug = $slug . '-' . Str::slug($model->district);
                }

                // Check for uniqueness
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $model->slug = $slug;
            }
        });
    }

    /**
     * Get all business competitors linked to this global competitor
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class, 'global_competitor_id');
    }

    /**
     * Find or create a global competitor based on identifying information
     */
    public static function findOrCreateFromCompetitor(array $data): self
    {
        // Try to find by Instagram handle first (most unique)
        if (!empty($data['instagram_handle'])) {
            $handle = ltrim($data['instagram_handle'], '@');
            $existing = static::where('instagram_handle', $handle)
                ->orWhere('instagram_handle', '@' . $handle)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        // Try to find by Telegram handle
        if (!empty($data['telegram_handle'])) {
            $handle = ltrim($data['telegram_handle'], '@');
            $existing = static::where('telegram_handle', $handle)
                ->orWhere('telegram_handle', '@' . $handle)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        // Try to find by name + region + district
        if (!empty($data['name']) && !empty($data['region'])) {
            $query = static::where('name', 'LIKE', $data['name']);

            if (!empty($data['region'])) {
                $query->where('region', $data['region']);
            }
            if (!empty($data['district'])) {
                $query->where('district', $data['district']);
            }

            $existing = $query->first();
            if ($existing) {
                return $existing;
            }
        }

        // Create new global competitor
        return static::create([
            'name' => $data['name'] ?? 'Unknown',
            'website' => $data['website'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'description' => $data['description'] ?? null,
            'industry' => $data['industry'] ?? null,
            'region' => $data['region'] ?? null,
            'district' => $data['district'] ?? null,
            'address' => $data['address'] ?? null,
            'instagram_handle' => isset($data['instagram_handle']) ? ltrim($data['instagram_handle'], '@') : null,
            'telegram_handle' => isset($data['telegram_handle']) ? ltrim($data['telegram_handle'], '@') : null,
            'facebook_page' => $data['facebook_page'] ?? null,
            'tiktok_handle' => $data['tiktok_handle'] ?? null,
            'youtube_channel' => $data['youtube_channel'] ?? null,
        ]);
    }

    /**
     * Merge SWOT data from a business competitor contribution
     * Supports both string format and object format with business_id
     */
    public function mergeSwotData(array $newSwotData, mixed $businessId = null): void
    {
        $currentSwot = $this->swot_data ?? [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

        // Merge each category, avoiding duplicates
        foreach (['strengths', 'weaknesses', 'opportunities', 'threats'] as $category) {
            $existing = $currentSwot[$category] ?? [];
            $new = $newSwotData[$category] ?? [];

            // Add new items that don't exist (case-insensitive comparison)
            foreach ($new as $item) {
                // Handle both string and object format
                $itemText = is_array($item) ? ($item['text'] ?? '') : $item;
                $itemBusinessId = is_array($item) ? ($item['business_id'] ?? $businessId) : $businessId;

                if (empty(trim($itemText))) {
                    continue;
                }

                $itemLower = mb_strtolower(trim($itemText));
                $exists = false;

                foreach ($existing as $existingItem) {
                    $existingText = is_array($existingItem) ? ($existingItem['text'] ?? '') : $existingItem;
                    if (mb_strtolower(trim($existingText)) === $itemLower) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    // Store in object format with business_id
                    $existing[] = [
                        'text' => trim($itemText),
                        'business_id' => $itemBusinessId,
                    ];
                }
            }

            $currentSwot[$category] = $existing;
        }

        $this->swot_data = $currentSwot;
        $this->swot_updated_at = now();
        $this->swot_contributors_count = $this->swot_contributors_count + 1;
        $this->save();
    }

    /**
     * Get SWOT item count
     */
    public function getSwotCountAttribute(): int
    {
        if (!$this->swot_data) return 0;

        return count($this->swot_data['strengths'] ?? []) +
               count($this->swot_data['weaknesses'] ?? []) +
               count($this->swot_data['opportunities'] ?? []) +
               count($this->swot_data['threats'] ?? []);
    }
}
