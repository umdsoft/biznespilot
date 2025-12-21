<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiInsight extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    protected $table = 'ai_insights';

    protected $fillable = [
        'business_id',
        'type',
        'insight_type',
        'category',
        'title',
        'content',
        'priority',
        'sentiment',
        'title_uz',
        'title_en',
        'description_uz',
        'description_en',
        'action_uz',
        'action_en',
        'data',
        'data_points',
        'metric_affected',
        'expected_impact',
        'confidence_score',
        'ai_model',
        'ai_prompt_context',
        'is_read',
        'is_actionable',
        'is_active',
        'action_taken',
        'generated_at',
        'read_at',
        'status',
        'viewed_at',
        'acted_at',
        'action_result',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'data_points' => 'array',
        'confidence_score' => 'decimal:2',
        'is_read' => 'boolean',
        'is_actionable' => 'boolean',
        'is_active' => 'boolean',
        'generated_at' => 'datetime',
        'read_at' => 'datetime',
        'viewed_at' => 'datetime',
        'acted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['new', 'viewed']);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getTitle($locale = 'uz')
    {
        return $locale === 'en' && $this->title_en
            ? $this->title_en
            : $this->title_uz;
    }

    public function getDescription($locale = 'uz')
    {
        return $locale === 'en' && $this->description_en
            ? $this->description_en
            : $this->description_uz;
    }

    public function getAction($locale = 'uz')
    {
        return $locale === 'en' && $this->action_en
            ? $this->action_en
            : $this->action_uz;
    }

    public function markViewed()
    {
        if ($this->status === 'new') {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    public function markActed($result = null)
    {
        $this->update([
            'status' => 'acted',
            'acted_at' => now(),
            'action_result' => $result,
        ]);
    }

    public function dismiss()
    {
        $this->update([
            'status' => 'dismissed',
        ]);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function getTypeIcon()
    {
        return match ($this->insight_type) {
            'opportunity' => 'light-bulb',
            'warning' => 'exclamation-triangle',
            'recommendation' => 'sparkles',
            'trend' => 'arrow-trending-up',
            'anomaly' => 'exclamation-circle',
            'celebration' => 'trophy',
            default => 'information-circle',
        };
    }

    public function getTypeColor()
    {
        return match ($this->insight_type) {
            'opportunity' => 'green',
            'warning' => 'orange',
            'recommendation' => 'blue',
            'trend' => 'purple',
            'anomaly' => 'red',
            'celebration' => 'yellow',
            default => 'gray',
        };
    }

    public function getPriorityColor()
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }
}
