<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingMetrics extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $table = 'marketing_metrics';

    protected $fillable = [
        'business_id',
        'monthly_budget',
        'ad_spend',
        'website_purpose',
        'monthly_visits',
        'website_conversion',
        'active_channels',
        'best_channel',
        'top_lead_channel',
        'instagram_followers',
        'telegram_subscribers',
        'facebook_followers',
        'roi_tracking_level',
        'marketing_roi',
        'content_activities',
        'marketing_challenges',
        'additional_data',
    ];

    protected $casts = [
        'active_channels' => 'array',
        'content_activities' => 'array',
        'additional_data' => 'array',
        'website_conversion' => 'decimal:2',
        'marketing_roi' => 'decimal:2',
    ];

    // Web-sayt maqsadlari
    public const WEBSITE_PURPOSES = [
        'lead_generation' => 'Lid yig\'ish',
        'ecommerce' => 'Onlayn sotuv',
        'info_brand' => 'Ma\'lumot/Brend',
        'no_website' => 'Sayt yo\'q',
    ];

    // ROI kuzatuv darajalari
    public const ROI_TRACKING_LEVELS = [
        'yes' => 'Ha, bilaman',
        'partially' => 'Qisman',
        'no' => 'Yo\'q',
    ];

    // Marketing kanallari
    public const MARKETING_CHANNELS = [
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'facebook' => 'Facebook',
        'google_ads' => 'Google Ads',
        'seo' => 'SEO',
        'email' => 'Email marketing',
        'sms' => 'SMS marketing',
        'content' => 'Kontent marketing',
        'influencer' => 'Influencer marketing',
        'offline' => 'Oflayn reklama',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
    ];

    // Kontent faoliyatlari
    public const CONTENT_ACTIVITIES = [
        'blog' => 'Blog maqolalar',
        'videos' => 'Video kontentlar',
        'reels' => 'Reels/Stories',
        'podcast' => 'Podcast',
        'webinars' => 'Vebinarlar',
        'ebooks' => 'E-kitoblar',
    ];

    /**
     * Tarix yozuvlari
     */
    public function history(): HasMany
    {
        return $this->hasMany(MarketingMetricsHistory::class, 'marketing_metrics_id')
            ->orderBy('recorded_at', 'desc');
    }

    /**
     * Tarixga saqlash
     */
    public function saveToHistory(string $changeType = 'update', ?string $note = null): MarketingMetricsHistory
    {
        return MarketingMetricsHistory::create([
            'business_id' => $this->business_id,
            'marketing_metrics_id' => $this->id,
            'monthly_budget' => $this->monthly_budget,
            'ad_spend' => $this->ad_spend,
            'website_purpose' => $this->website_purpose,
            'monthly_visits' => $this->monthly_visits,
            'website_conversion' => $this->website_conversion,
            'active_channels' => $this->active_channels,
            'best_channel' => $this->best_channel,
            'top_lead_channel' => $this->top_lead_channel,
            'instagram_followers' => $this->instagram_followers,
            'telegram_subscribers' => $this->telegram_subscribers,
            'facebook_followers' => $this->facebook_followers,
            'roi_tracking_level' => $this->roi_tracking_level,
            'marketing_roi' => $this->marketing_roi,
            'content_activities' => $this->content_activities,
            'marketing_challenges' => $this->marketing_challenges,
            'additional_data' => $this->additional_data,
            'recorded_at' => now(),
            'change_type' => $changeType,
            'note' => $note,
        ]);
    }

    /**
     * Web-sayt maqsadi labelini olish
     */
    public function getWebsitePurposeLabelAttribute(): ?string
    {
        return self::WEBSITE_PURPOSES[$this->website_purpose] ?? null;
    }

    /**
     * ROI kuzatuv darajasi labelini olish
     */
    public function getRoiTrackingLabelAttribute(): ?string
    {
        return self::ROI_TRACKING_LEVELS[$this->roi_tracking_level] ?? null;
    }

    /**
     * Web-sayt bormi?
     */
    public function hasWebsite(): bool
    {
        return $this->website_purpose && $this->website_purpose !== 'no_website';
    }

    /**
     * Ma'lumotlar to'ldirilganmi
     */
    public function hasData(): bool
    {
        return ! empty($this->monthly_budget) ||
               ! empty($this->active_channels) ||
               ! empty($this->website_purpose);
    }

    /**
     * Jami ijtimoiy tarmoq obunachilar
     */
    public function getTotalSocialFollowersAttribute(): int
    {
        return (int) $this->instagram_followers +
               (int) $this->telegram_subscribers +
               (int) $this->facebook_followers;
    }

    /**
     * To'liqlik foizi
     */
    public function getCompletionPercentAttribute(): int
    {
        $fields = [
            'monthly_budget',
            'website_purpose',
            'active_channels',
            'best_channel',
            'roi_tracking_level',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            $value = $this->$field;
            if (! empty($value) && (! is_array($value) || count($value) > 0)) {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }
}
