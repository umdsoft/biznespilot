<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentStyleGuide extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'tone',
        'language_style',
        'emoji_frequency',
        'common_emojis',
        'avg_post_length',
        'min_post_length',
        'max_post_length',
        'common_hashtags',
        'avg_hashtag_count',
        'use_branded_hashtags',
        'cta_patterns',
        'cta_style',
        'best_posting_times',
        'content_pillars',
        'channel_specific_settings',
        'analyzed_posts_count',
        'avg_engagement_rate',
        'top_performing_topics',
        'ai_model',
        'creativity_level',
        'last_analyzed_at',
    ];

    protected $casts = [
        'common_emojis' => 'array',
        'common_hashtags' => 'array',
        'cta_patterns' => 'array',
        'best_posting_times' => 'array',
        'content_pillars' => 'array',
        'channel_specific_settings' => 'array',
        'top_performing_topics' => 'array',
        'use_branded_hashtags' => 'boolean',
        'creativity_level' => 'float',
        'avg_engagement_rate' => 'float',
        'last_analyzed_at' => 'datetime',
    ];

    /**
     * Tone options
     */
    public const TONES = [
        'formal' => 'Rasmiy',
        'casual' => 'Oddiy',
        'professional' => 'Professional',
        'friendly' => 'Do\'stona',
        'playful' => 'Qiziqarli',
    ];

    /**
     * Language style options
     */
    public const LANGUAGE_STYLES = [
        'simple' => 'Oddiy',
        'technical' => 'Texnik',
        'creative' => 'Ijodiy',
        'persuasive' => 'Ishontiruvchi',
    ];

    /**
     * CTA style options
     */
    public const CTA_STYLES = [
        'soft' => 'Yumshoq',
        'direct' => 'To\'g\'ridan-to\'g\'ri',
        'urgent' => 'Shoshilinch',
        'none' => 'Yo\'q',
    ];

    /**
     * Get or create style guide for business
     */
    public static function getOrCreate(string $businessId): self
    {
        return self::firstOrCreate(
            ['business_id' => $businessId],
            [
                'tone' => 'professional',
                'language_style' => 'simple',
                'emoji_frequency' => 'medium',
                'avg_post_length' => 200,
                'min_post_length' => 100,
                'max_post_length' => 500,
                'avg_hashtag_count' => 5,
                'cta_style' => 'direct',
                'ai_model' => 'claude-3-haiku',
                'creativity_level' => 0.7,
            ]
        );
    }

    /**
     * Templates relationship
     */
    public function templates(): HasMany
    {
        return $this->hasMany(ContentTemplate::class, 'business_id', 'business_id');
    }

    /**
     * Get channel-specific setting
     */
    public function getChannelSetting(string $channel, string $key, $default = null)
    {
        return data_get($this->channel_specific_settings, "{$channel}.{$key}", $default);
    }

    /**
     * Set channel-specific setting
     */
    public function setChannelSetting(string $channel, string $key, $value): self
    {
        $settings = $this->channel_specific_settings ?? [];
        data_set($settings, "{$channel}.{$key}", $value);
        $this->channel_specific_settings = $settings;

        return $this;
    }

    /**
     * Get best posting time for day
     */
    public function getBestTimeForDay(string $day): ?array
    {
        return $this->best_posting_times[$day] ?? null;
    }

    /**
     * Build AI prompt context
     */
    public function buildPromptContext(): string
    {
        $context = "Brand Voice & Style Guide:\n";
        $context .= "- Ton: " . self::TONES[$this->tone] . "\n";
        $context .= "- Til uslubi: " . self::LANGUAGE_STYLES[$this->language_style] . "\n";
        $context .= "- Emoji darajasi: {$this->emoji_frequency}\n";
        $context .= "- Post uzunligi: {$this->min_post_length}-{$this->max_post_length} belgi\n";
        $context .= "- CTA uslubi: " . self::CTA_STYLES[$this->cta_style] . "\n";

        if ($this->common_emojis) {
            $context .= "- Ko'p ishlatiladigan emoji: " . implode(' ', $this->common_emojis) . "\n";
        }

        if ($this->content_pillars) {
            $context .= "- Kontent yo'nalishlari: " . implode(', ', $this->content_pillars) . "\n";
        }

        if ($this->cta_patterns) {
            $context .= "- CTA namunalari: " . implode('; ', array_slice($this->cta_patterns, 0, 3)) . "\n";
        }

        return $context;
    }
}
