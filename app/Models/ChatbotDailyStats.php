<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ChatbotDailyStats extends Model
{
    use BelongsToBusiness;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'date',
        'total_conversations',
        'new_conversations',
        'active_conversations',
        'closed_conversations',
        'handed_off_conversations',
        'total_messages',
        'bot_messages',
        'user_messages',
        'avg_response_time_seconds',
        'telegram_conversations',
        'instagram_conversations',
        'facebook_conversations',
        'awareness_stage',
        'interest_stage',
        'consideration_stage',
        'intent_stage',
        'purchase_stage',
        'post_purchase_stage',
        'leads_created',
        'leads_converted',
        'conversion_rate',
        'total_conversion_value',
        'intent_breakdown',
        'sentiment_breakdown',
        'avg_rating',
        'total_ratings',
        'avg_conversation_duration_minutes',
        'avg_messages_per_conversation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'avg_response_time_seconds' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'total_conversion_value' => 'decimal:2',
        'intent_breakdown' => 'array',
        'sentiment_breakdown' => 'array',
        'avg_rating' => 'decimal:2',
        'avg_conversation_duration_minutes' => 'decimal:2',
        'avg_messages_per_conversation' => 'decimal:2',
    ];

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope for this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }
}
