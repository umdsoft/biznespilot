<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentGeneration extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'topic',
        'prompt',
        'content_type',
        'purpose',
        'target_channel',
        'generated_content',
        'generated_hashtags',
        'generated_variations',
        'ai_model',
        'temperature',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'cost_usd',
        'reference_template_ids',
        'status',
        'error_message',
        'user_rating',
        'user_feedback',
        'was_edited',
        'edited_content',
        'was_published',
        'published_post_id',
        'published_at',
        'post_engagement_rate',
        'post_likes',
        'post_comments',
    ];

    protected $casts = [
        'generated_hashtags' => 'array',
        'generated_variations' => 'array',
        'reference_template_ids' => 'array',
        'temperature' => 'float',
        'cost_usd' => 'float',
        'was_edited' => 'boolean',
        'was_published' => 'boolean',
        'published_at' => 'datetime',
        'post_engagement_rate' => 'float',
    ];

    /**
     * Status options
     */
    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'generating' => 'Generatsiya qilinmoqda',
        'completed' => 'Tayyor',
        'failed' => 'Xatolik',
        'published' => 'Nashr qilindi',
    ];

    /**
     * Rating options
     */
    public const RATINGS = [
        'good' => 'Yaxshi',
        'neutral' => 'O\'rtacha',
        'bad' => 'Yomon',
    ];

    /**
     * AI Models with pricing (per 1M tokens)
     */
    public const AI_MODELS = [
        'claude-3-haiku' => [
            'name' => 'Claude 3 Haiku',
            'input_price' => 0.25,
            'output_price' => 1.25,
        ],
        'claude-3-sonnet' => [
            'name' => 'Claude 3.5 Sonnet',
            'input_price' => 3.00,
            'output_price' => 15.00,
        ],
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: By status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Published
     */
    public function scopePublished($query)
    {
        return $query->where('was_published', true);
    }

    /**
     * Scope: By channel
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('target_channel', $channel);
    }

    /**
     * Scope: Recent
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Calculate cost based on tokens used
     */
    public function calculateCost(): float
    {
        $model = self::AI_MODELS[$this->ai_model] ?? self::AI_MODELS['claude-3-haiku'];

        $inputCost = ($this->input_tokens / 1000000) * $model['input_price'];
        $outputCost = ($this->output_tokens / 1000000) * $model['output_price'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Update cost
     */
    public function updateCost(): self
    {
        $this->total_tokens = $this->input_tokens + $this->output_tokens;
        $this->cost_usd = $this->calculateCost();
        $this->save();

        return $this;
    }

    /**
     * Mark as completed
     */
    public function markCompleted(string $content, array $hashtags = [], array $variations = []): self
    {
        $this->update([
            'status' => 'completed',
            'generated_content' => $content,
            'generated_hashtags' => $hashtags,
            'generated_variations' => $variations,
        ]);

        return $this;
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $error): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);

        return $this;
    }

    /**
     * Mark as published
     */
    public function markPublished(string $postId): self
    {
        $this->update([
            'status' => 'published',
            'was_published' => true,
            'published_post_id' => $postId,
            'published_at' => now(),
        ]);

        return $this;
    }

    /**
     * Rate generation
     */
    public function rate(string $rating, ?string $feedback = null): self
    {
        $this->update([
            'user_rating' => $rating,
            'user_feedback' => $feedback,
        ]);

        return $this;
    }

    /**
     * Get final content (edited or generated)
     */
    public function getFinalContent(): ?string
    {
        return $this->was_edited ? $this->edited_content : $this->generated_content;
    }

    /**
     * Get variation by index
     */
    public function getVariation(int $index): ?array
    {
        return $this->generated_variations[$index] ?? null;
    }

    /**
     * Get best variation based on hook type preference
     */
    public function getBestVariation(string $preferredHookType = null): ?array
    {
        if (empty($this->generated_variations)) {
            return null;
        }

        if ($preferredHookType) {
            foreach ($this->generated_variations as $variation) {
                if (($variation['hook_type'] ?? null) === $preferredHookType) {
                    return $variation;
                }
            }
        }

        return $this->generated_variations[0];
    }
}
