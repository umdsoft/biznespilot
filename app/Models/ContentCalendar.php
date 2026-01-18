<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContentCalendar extends Model
{
    use BelongsToBusiness;

    protected $table = 'content_calendar';

    protected $fillable = [
        'uuid',
        'business_id',
        'weekly_plan_id',
        'monthly_plan_id',
        'title',
        'description',
        'content_text',
        'media_urls',
        'hashtags',
        'content_type',
        'format',
        'channel',
        'channel_account',
        'scheduled_date',
        'scheduled_time',
        'scheduled_at',
        'timezone',
        'status',
        'published_at',
        'external_post_id',
        'post_url',
        'views',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'reach',
        'impressions',
        'engagement_rate',
        'campaign_name',
        'campaign_id',
        'is_ai_generated',
        'ai_suggestions',
        'ai_caption_suggestion',
        'tags',
        'theme',
        'goal',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
        'priority',
        'sort_order',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'ai_suggestions' => 'array',
        'tags' => 'array',
        'is_ai_generated' => 'boolean',
        'scheduled_date' => 'date',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'engagement_rate' => 'decimal:2',
    ];

    public const CONTENT_TYPES = [
        'post' => 'Post',
        'story' => 'Story',
        'reel' => 'Reel',
        'video' => 'Video',
        'article' => 'Maqola',
        'carousel' => 'Carousel',
        'live' => 'Live',
        'poll' => 'So\'rovnoma',
        'ad' => 'Reklama',
        'email' => 'Email',
        'sms' => 'SMS',
        'other' => 'Boshqa',
    ];

    public const CHANNELS = [
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'twitter' => 'Twitter/X',
        'website' => 'Websayt',
        'email' => 'Email',
        'sms' => 'SMS',
    ];

    public const STATUSES = [
        'idea' => 'G\'oya',
        'draft' => 'Qoralama',
        'pending_review' => 'Tekshiruvda',
        'approved' => 'Tasdiqlangan',
        'scheduled' => 'Rejalashtirilgan',
        'published' => 'Joylashtirilgan',
        'failed' => 'Xato',
        'archived' => 'Arxivlangan',
    ];

    public const GOALS = [
        'awareness' => 'Tanilish',
        'engagement' => 'Faollik',
        'conversion' => 'Konversiya',
        'retention' => 'Saqlash',
        'education' => 'Ta\'lim',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }

    public function monthlyPlan(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('scheduled_date', [$start, $end]);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['approved', 'scheduled'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', now()->toDateString());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }

    // Helpers
    public function getContentTypeLabel(): string
    {
        return self::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }

    public function getChannelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getGoalLabel(): string
    {
        return self::GOALS[$this->goal] ?? $this->goal;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'idea' => 'gray',
            'draft' => 'yellow',
            'pending_review' => 'orange',
            'approved' => 'blue',
            'scheduled' => 'indigo',
            'published' => 'green',
            'failed' => 'red',
            'archived' => 'gray',
            default => 'gray',
        };
    }

    public function getChannelColor(): string
    {
        return match ($this->channel) {
            'instagram' => 'pink',
            'telegram' => 'sky',
            'facebook' => 'blue',
            'tiktok' => 'gray',
            'youtube' => 'red',
            'linkedin' => 'blue',
            'twitter' => 'gray',
            default => 'gray',
        };
    }

    public function approve(?int $userId = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function schedule(): void
    {
        if ($this->scheduled_date && $this->scheduled_time) {
            $scheduledAt = \Carbon\Carbon::parse(
                $this->scheduled_date->format('Y-m-d').' '.$this->scheduled_time,
                $this->timezone
            );
            $this->update([
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
            ]);
        }
    }

    public function markAsPublished(?string $externalId = null, ?string $url = null): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'external_post_id' => $externalId,
            'post_url' => $url,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function updateMetrics(array $metrics): void
    {
        $this->update([
            'views' => $metrics['views'] ?? $this->views,
            'likes' => $metrics['likes'] ?? $this->likes,
            'comments' => $metrics['comments'] ?? $this->comments,
            'shares' => $metrics['shares'] ?? $this->shares,
            'saves' => $metrics['saves'] ?? $this->saves,
            'clicks' => $metrics['clicks'] ?? $this->clicks,
            'reach' => $metrics['reach'] ?? $this->reach,
            'impressions' => $metrics['impressions'] ?? $this->impressions,
        ]);

        $this->calculateEngagementRate();
    }

    public function calculateEngagementRate(): void
    {
        $reach = $this->reach ?: $this->impressions;
        if ($reach === 0) {
            $this->update(['engagement_rate' => 0]);

            return;
        }

        $engagements = $this->likes + $this->comments + $this->shares + $this->saves;
        $rate = ($engagements / $reach) * 100;
        $this->update(['engagement_rate' => round($rate, 2)]);
    }

    public function getTotalEngagements(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    public function duplicate(?string $newDate = null): self
    {
        $new = $this->replicate(['uuid', 'published_at', 'external_post_id', 'post_url', 'approved_at', 'approved_by']);
        $new->uuid = (string) Str::uuid();
        $new->status = 'draft';
        $new->scheduled_date = $newDate ? \Carbon\Carbon::parse($newDate) : $this->scheduled_date->addWeek();
        $new->views = 0;
        $new->likes = 0;
        $new->comments = 0;
        $new->shares = 0;
        $new->saves = 0;
        $new->clicks = 0;
        $new->reach = 0;
        $new->impressions = 0;
        $new->engagement_rate = 0;
        $new->save();

        return $new;
    }
}
