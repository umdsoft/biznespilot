<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UtmMapping - UTM parametrlarni Campaign/Channel ga avtomatik mapping
 */
class UtmMapping extends Model
{
    use BelongsToBusiness, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'marketing_channel_id',
        'campaign_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the marketing channel.
     */
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    /**
     * Get the campaign.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Active mappings only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * UTM parametrlarga mos mapping topish.
     * Eng aniq matchni qaytaradi (specificity bo'yicha tartiblangan).
     */
    public static function findMatch(
        string $businessId,
        ?string $source,
        ?string $medium,
        ?string $campaign
    ): ?self {
        if (!$source && !$medium && !$campaign) {
            return null;
        }

        return static::where('business_id', $businessId)
            ->active()
            ->where(function ($query) use ($source, $medium, $campaign) {
                // Eng aniq match: source + medium + campaign
                $query->where(function ($q) use ($source, $medium, $campaign) {
                    $q->where('utm_source', $source)
                      ->where('utm_medium', $medium)
                      ->where('utm_campaign', $campaign);
                })
                // Yoki source + medium match (campaign null yoki match)
                ->orWhere(function ($q) use ($source, $medium) {
                    $q->where('utm_source', $source)
                      ->where('utm_medium', $medium)
                      ->whereNull('utm_campaign');
                })
                // Yoki source + campaign match (medium null)
                ->orWhere(function ($q) use ($source, $campaign) {
                    $q->where('utm_source', $source)
                      ->whereNull('utm_medium')
                      ->where('utm_campaign', $campaign);
                })
                // Yoki faqat source match
                ->orWhere(function ($q) use ($source) {
                    $q->where('utm_source', $source)
                      ->whereNull('utm_medium')
                      ->whereNull('utm_campaign');
                });
            })
            // Specificity bo'yicha tartiblash (eng aniq birinchi)
            ->orderByRaw('
                CASE
                    WHEN utm_source IS NOT NULL AND utm_medium IS NOT NULL AND utm_campaign IS NOT NULL THEN 1
                    WHEN utm_source IS NOT NULL AND utm_medium IS NOT NULL THEN 2
                    WHEN utm_source IS NOT NULL AND utm_campaign IS NOT NULL THEN 3
                    WHEN utm_source IS NOT NULL THEN 4
                    ELSE 5
                END
            ')
            ->first();
    }

    /**
     * Create mapping with auto-generated name.
     */
    public static function createMapping(
        string $businessId,
        ?string $source,
        ?string $medium,
        ?string $campaign,
        ?string $channelId = null,
        ?string $campaignModelId = null
    ): self {
        $parts = array_filter([$source, $medium, $campaign]);
        $name = implode(' / ', $parts) ?: 'Unnamed Mapping';

        return static::create([
            'business_id' => $businessId,
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'marketing_channel_id' => $channelId,
            'campaign_id' => $campaignModelId,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get UTM pattern as string.
     */
    public function getUtmPattern(): string
    {
        $parts = [];

        if ($this->utm_source) {
            $parts[] = "source={$this->utm_source}";
        }
        if ($this->utm_medium) {
            $parts[] = "medium={$this->utm_medium}";
        }
        if ($this->utm_campaign) {
            $parts[] = "campaign={$this->utm_campaign}";
        }

        return implode('&', $parts) ?: '*';
    }

    /**
     * Check if this mapping matches given UTM parameters.
     */
    public function matches(?string $source, ?string $medium, ?string $campaign): bool
    {
        // Source must match if specified in mapping
        if ($this->utm_source !== null && $this->utm_source !== $source) {
            return false;
        }

        // Medium must match if specified in mapping
        if ($this->utm_medium !== null && $this->utm_medium !== $medium) {
            return false;
        }

        // Campaign must match if specified in mapping
        if ($this->utm_campaign !== null && $this->utm_campaign !== $campaign) {
            return false;
        }

        return true;
    }

    /**
     * Get target description.
     */
    public function getTargetDescription(): string
    {
        $targets = [];

        if ($this->marketingChannel) {
            $targets[] = "Channel: {$this->marketingChannel->name}";
        }

        if ($this->campaign) {
            $targets[] = "Campaign: {$this->campaign->name}";
        }

        return implode(', ', $targets) ?: 'No target';
    }
}
