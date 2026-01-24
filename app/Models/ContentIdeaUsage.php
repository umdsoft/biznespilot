<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentIdeaUsage extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'content_idea_id',
        'business_id',
        'user_id',
        'content_generation_id',
        'outcome',
        'engagement_rate',
        'likes_count',
        'comments_count',
        'shares_count',
        'user_rating',
        'user_notes',
    ];

    protected $casts = [
        'engagement_rate' => 'float',
    ];

    /**
     * Outcome types
     */
    public const OUTCOMES = [
        'draft' => 'Qoralama',
        'published' => 'Nashr qilindi',
        'rejected' => 'Rad etildi',
    ];

    /**
     * Rating types
     */
    public const RATINGS = [
        'helpful' => 'Foydali',
        'neutral' => 'O\'rtacha',
        'not_helpful' => 'Foydali emas',
    ];

    // ==================== RELATIONSHIPS ====================

    public function idea(): BelongsTo
    {
        return $this->belongsTo(ContentIdea::class, 'content_idea_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(ContentGeneration::class, 'content_generation_id');
    }

    // ==================== METHODS ====================

    /**
     * Mark as published with metrics
     */
    public function markPublished(array $metrics = []): void
    {
        $this->update([
            'outcome' => 'published',
            'engagement_rate' => $metrics['engagement_rate'] ?? null,
            'likes_count' => $metrics['likes'] ?? $metrics['likes_count'] ?? null,
            'comments_count' => $metrics['comments'] ?? $metrics['comments_count'] ?? null,
            'shares_count' => $metrics['shares'] ?? $metrics['shares_count'] ?? null,
        ]);

        // G'oya statistikasini yangilash
        $this->idea->recordPublish($this, $metrics);
    }

    /**
     * Rate the idea
     */
    public function rate(string $rating, ?string $notes = null): void
    {
        $this->update([
            'user_rating' => $rating,
            'user_notes' => $notes,
        ]);

        // G'oya quality score ni yangilash
        $this->idea->updateQualityScore();
    }
}
