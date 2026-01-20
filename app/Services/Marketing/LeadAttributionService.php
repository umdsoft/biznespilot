<?php

namespace App\Services\Marketing;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Campaign;
use App\Models\MarketingChannel;
use App\Models\UtmMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LeadAttributionService - Lead ga marketing attribution qo'shish
 * UTM parametrlar, Campaign, Channel tracking
 */
class LeadAttributionService
{
    /**
     * Request dan UTM parametrlarni olish.
     */
    public function captureUtmFromRequest(Request $request): array
    {
        return [
            'utm_source' => $request->input('utm_source') ?? $request->query('utm_source'),
            'utm_medium' => $request->input('utm_medium') ?? $request->query('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign') ?? $request->query('utm_campaign'),
            'utm_content' => $request->input('utm_content') ?? $request->query('utm_content'),
            'utm_term' => $request->input('utm_term') ?? $request->query('utm_term'),
        ];
    }

    /**
     * UTM parametrlardan attribution qilish.
     */
    public function attributeFromUtm(Lead $lead, array $utmParams): void
    {
        // UTM parametrlarni saqlash
        $lead->utm_source = $utmParams['utm_source'] ?? null;
        $lead->utm_medium = $utmParams['utm_medium'] ?? null;
        $lead->utm_campaign = $utmParams['utm_campaign'] ?? null;
        $lead->utm_content = $utmParams['utm_content'] ?? null;
        $lead->utm_term = $utmParams['utm_term'] ?? null;

        // First touch tracking
        if (!$lead->first_touch_at) {
            $lead->first_touch_at = now();
            $lead->first_touch_source = $utmParams['utm_source'] ?? 'direct';
        }

        // UTM mapping dan Campaign va Channel topish
        if ($utmParams['utm_source'] || $utmParams['utm_medium'] || $utmParams['utm_campaign']) {
            $mapping = UtmMapping::findMatch(
                $lead->business_id,
                $utmParams['utm_source'],
                $utmParams['utm_medium'],
                $utmParams['utm_campaign']
            );

            if ($mapping) {
                $lead->marketing_channel_id = $mapping->marketing_channel_id;
                $lead->campaign_id = $mapping->campaign_id;
            } else {
                // Mapping yo'q - avtomatik resolve qilish
                $lead->marketing_channel_id = $this->resolveChannelFromUtm(
                    $lead->business_id,
                    $utmParams['utm_source'],
                    $utmParams['utm_medium']
                );

                $lead->campaign_id = $this->resolveCampaignFromUtm(
                    $lead->business_id,
                    $utmParams['utm_campaign']
                );
            }
        }

        $lead->saveQuietly(); // Observer triggerlanmasin

        Log::info('LeadAttributionService: Lead attributed from UTM', [
            'lead_id' => $lead->id,
            'utm' => $utmParams,
            'campaign_id' => $lead->campaign_id,
            'channel_id' => $lead->marketing_channel_id,
        ]);
    }

    /**
     * LeadSource dan attribution qilish.
     */
    public function attributeFromSource(Lead $lead, ?LeadSource $source): void
    {
        if (!$source) {
            return;
        }

        // First touch
        if (!$lead->first_touch_at) {
            $lead->first_touch_at = now();
            $lead->first_touch_source = $source->name ?? $source->code ?? 'source';
        }

        // Source category bo'yicha channel aniqlash
        if (!$lead->marketing_channel_id && $source->category) {
            $lead->marketing_channel_id = $this->resolveChannelFromSourceCategory(
                $lead->business_id,
                $source->category
            );
        }

        $lead->saveQuietly();

        Log::info('LeadAttributionService: Lead attributed from source', [
            'lead_id' => $lead->id,
            'source_id' => $source->id,
            'source_name' => $source->name,
        ]);
    }

    /**
     * Direct attribution (campaign va channel to'g'ridan-to'g'ri berish).
     */
    public function attributeDirect(
        Lead $lead,
        ?string $campaignId = null,
        ?string $channelId = null
    ): void {
        if ($campaignId) {
            $lead->campaign_id = $campaignId;
        }

        if ($channelId) {
            $lead->marketing_channel_id = $channelId;
        }

        // First touch
        if (!$lead->first_touch_at) {
            $lead->first_touch_at = now();
            $lead->first_touch_source = 'direct_attribution';
        }

        $lead->saveQuietly();

        Log::info('LeadAttributionService: Lead attributed directly', [
            'lead_id' => $lead->id,
            'campaign_id' => $campaignId,
            'channel_id' => $channelId,
        ]);
    }

    /**
     * UTM source/medium dan MarketingChannel topish.
     */
    public function resolveChannelFromUtm(string $businessId, ?string $source, ?string $medium): ?string
    {
        if (!$source) {
            return null;
        }

        // Bilangan source lar
        $channelMap = [
            'instagram' => ['instagram', 'ig', 'insta'],
            'facebook' => ['facebook', 'fb', 'meta'],
            'telegram' => ['telegram', 'tg'],
            'google' => ['google', 'gads', 'adwords', 'googleads'],
            'youtube' => ['youtube', 'yt'],
            'tiktok' => ['tiktok', 'tt'],
            'email' => ['email', 'newsletter', 'mailchimp', 'sendgrid'],
            'sms' => ['sms', 'eskiz'],
            'organic' => ['organic', 'seo', 'search'],
            'referral' => ['referral', 'ref', 'affiliate'],
        ];

        $sourceLower = strtolower($source);
        $mediumLower = strtolower($medium ?? '');

        foreach ($channelMap as $channelType => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($sourceLower, $keyword) || str_contains($mediumLower, $keyword)) {
                    // Database dan topish
                    $channel = MarketingChannel::where('business_id', $businessId)
                        ->where(function ($q) use ($channelType, $keyword) {
                            $q->where('type', $channelType)
                              ->orWhere('platform', 'like', "%{$keyword}%")
                              ->orWhere('name', 'like', "%{$keyword}%");
                        })
                        ->first();

                    if ($channel) {
                        return $channel->id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * UTM campaign dan Campaign model topish.
     */
    public function resolveCampaignFromUtm(string $businessId, ?string $utmCampaign): ?string
    {
        if (!$utmCampaign) {
            return null;
        }

        // Aniq nom bo'yicha
        $campaign = Campaign::where('business_id', $businessId)
            ->where(function ($q) use ($utmCampaign) {
                $q->where('name', $utmCampaign)
                  ->orWhere('name', 'like', "%{$utmCampaign}%");
            })
            ->first();

        return $campaign?->id;
    }

    /**
     * LeadSource category dan channel topish.
     */
    private function resolveChannelFromSourceCategory(string $businessId, string $category): ?string
    {
        $categoryToType = [
            'digital' => ['social_media', 'advertising'],
            'social' => ['social_media'],
            'paid' => ['advertising'],
            'organic' => ['organic'],
            'referral' => ['referral'],
            'offline' => ['offline'],
        ];

        $types = $categoryToType[$category] ?? null;

        if (!$types) {
            return null;
        }

        $channel = MarketingChannel::where('business_id', $businessId)
            ->whereIn('type', $types)
            ->first();

        return $channel?->id;
    }

    /**
     * Lead uchun to'liq attribution ma'lumotlarini yig'ish.
     */
    public function buildFullAttribution(Lead $lead): array
    {
        return [
            'lead_id' => $lead->id,
            'campaign' => [
                'id' => $lead->campaign_id,
                'name' => $lead->campaign?->name,
                'type' => $lead->campaign?->type,
            ],
            'channel' => [
                'id' => $lead->marketing_channel_id,
                'name' => $lead->marketingChannel?->name,
                'type' => $lead->marketingChannel?->type,
                'platform' => $lead->marketingChannel?->platform,
            ],
            'source' => [
                'id' => $lead->source_id,
                'name' => $lead->source?->name,
                'category' => $lead->source?->category,
            ],
            'utm' => $lead->getUtmArray(),
            'first_touch' => [
                'at' => $lead->first_touch_at?->toIso8601String(),
                'source' => $lead->first_touch_source,
            ],
            'timestamps' => [
                'lead_created' => $lead->created_at->toIso8601String(),
                'attribution_captured' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Check if lead has any attribution.
     */
    public function hasAttribution(Lead $lead): bool
    {
        return $lead->campaign_id !== null
            || $lead->marketing_channel_id !== null
            || $lead->utm_source !== null
            || $lead->source_id !== null;
    }
}
