<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'hr_performance_reviews';

    protected $fillable = [
        'business_id',
        'user_id',
        'reviewer_id',
        'review_period',
        'review_date',
        'status',
        'overall_rating',
        'ratings',
        'strengths',
        'areas_for_improvement',
        'achievements',
        'goals_for_next_period',
        'manager_comments',
        'employee_comments',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'review_date' => 'date',
        'overall_rating' => 'integer',
        'ratings' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Qoralama',
            'submitted' => 'Yuborildi',
            'completed' => 'Tugallandi',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getRatingLabelAttribute(): ?string
    {
        if (! $this->overall_rating) {
            return null;
        }

        $labels = [
            1 => 'Yomon',
            2 => 'Qoniqarli',
            3 => 'Yaxshi',
            4 => 'Juda yaxshi',
            5 => 'A\'lo',
        ];

        return $labels[$this->overall_rating] ?? null;
    }

    // ==================== Scopes ====================

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
