<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AIDiagnostic extends Model
{
    use BelongsToBusiness;

    protected $table = 'ai_diagnostics';

    protected $fillable = [
        'uuid',
        'business_id',
        'version',
        'previous_diagnostic_id',
        'diagnostic_type',
        'triggered_by',
        'status',
        'processing_step',
        'data_period_start',
        'data_period_end',
        'data_sources_used',
        'data_points_analyzed',
        'overall_score',
        'overall_health_score',
        'marketing_score',
        'sales_score',
        'content_score',
        'funnel_score',
        'strengths',
        'weaknesses',
        'opportunities',
        'threats',
        'swot_analysis',
        'recommendations',
        'ai_insights',
        'critical_actions',
        'high_priority_actions',
        'medium_priority_actions',
        'low_priority_actions',
        'executive_summary',
        'detailed_analysis',
        'ai_model_used',
        'ai_input_tokens',
        'ai_output_tokens',
        'ai_tokens_used',
        'ai_cost',
        'input_data_snapshot',
        'kpi_snapshot',
        'benchmark_comparison',
        'benchmark_summary',
        'trend_data',
        'started_at',
        'completed_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'data_period_start' => 'date',
        'data_period_end' => 'date',
        'data_sources_used' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'opportunities' => 'array',
        'threats' => 'array',
        'swot_analysis' => 'array',
        'recommendations' => 'array',
        'critical_actions' => 'array',
        'high_priority_actions' => 'array',
        'medium_priority_actions' => 'array',
        'low_priority_actions' => 'array',
        'detailed_analysis' => 'array',
        'input_data_snapshot' => 'array',
        'kpi_snapshot' => 'array',
        'benchmark_comparison' => 'array',
        'benchmark_summary' => 'array',
        'trend_data' => 'array',
        'ai_cost' => 'decimal:4',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    // Constants
    public const DIAGNOSTIC_TYPES = [
        'onboarding' => 'Onboarding Diagnostika',
        'weekly' => 'Haftalik Diagnostika',
        'monthly' => 'Oylik Diagnostika',
        'quarterly' => 'Choraklik Diagnostika',
        'ad_hoc' => 'Maxsus Diagnostika',
    ];

    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'processing' => 'Jarayonda',
        'completed' => 'Tugallandi',
        'failed' => 'Xato',
    ];

    // Relationships
    public function questions(): HasMany
    {
        return $this->hasMany(DiagnosticQuestion::class, 'diagnostic_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DiagnosticReport::class, 'diagnostic_id');
    }

    public function kpiCalculation(): HasOne
    {
        return $this->hasOne(KPICalculation::class, 'diagnostic_id');
    }

    public function previousDiagnostic(): BelongsTo
    {
        return $this->belongsTo(AIDiagnostic::class, 'previous_diagnostic_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOnboarding($query)
    {
        return $query->where('diagnostic_type', 'onboarding');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $data = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            ...$data,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    public function canRetry(): bool
    {
        return $this->retry_count < 3;
    }

    public function getTypeLabel(): string
    {
        return self::DIAGNOSTIC_TYPES[$this->diagnostic_type] ?? $this->diagnostic_type;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getHealthStatusColor(): string
    {
        $score = $this->overall_health_score ?? 0;

        if ($score >= 80) return 'green';
        if ($score >= 60) return 'yellow';
        if ($score >= 40) return 'orange';
        return 'red';
    }

    public function getHealthStatusLabel(): string
    {
        $score = $this->overall_health_score ?? 0;

        if ($score >= 80) return 'Ajoyib';
        if ($score >= 60) return 'Yaxshi';
        if ($score >= 40) return 'O\'rtacha';
        if ($score >= 20) return 'Zaif';
        return 'Kritik';
    }

    public function getAllRecommendations(): array
    {
        return array_merge(
            $this->critical_actions ?? [],
            $this->high_priority_actions ?? [],
            $this->medium_priority_actions ?? [],
            $this->low_priority_actions ?? []
        );
    }

    public function getProcessingDuration(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    public function getProcessingDurationFormatted(): ?string
    {
        $seconds = $this->getProcessingDuration();

        if ($seconds === null) {
            return null;
        }

        if ($seconds < 60) {
            return "{$seconds} soniya";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return "{$minutes} daqiqa {$remainingSeconds} soniya";
    }
}
