<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class MarketingHypothesis extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'marketing_hypotheses';

    protected $fillable = [
        'business_id',
        'hypothesis_type',
        'if_statement',
        'then_statement',
        'because_statement',
        'test_method',
        'success_metric',
        'target_value',
        'baseline_value',
        'test_duration_days',
        'sample_size_needed',
        'status',
        'confidence_level',
        'actual_result',
        'result_date',
        'learnings',
        'next_steps',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'baseline_value' => 'decimal:2',
        'actual_result' => 'decimal:2',
        'result_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Constants
    public const HYPOTHESIS_TYPES = [
        'channel' => 'Kanal gipotezasi',
        'content' => 'Kontent gipotezasi',
        'offer' => 'Taklif gipotezasi',
        'audience' => 'Auditoriya gipotezasi',
        'funnel' => 'Funnel gipotezasi',
    ];

    public const TEST_METHODS = [
        'a_b_test' => 'A/B test',
        'pilot' => 'Pilot loyiha',
        'survey' => 'So\'rovnoma',
        'mvp' => 'MVP',
    ];

    public const STATUSES = [
        'draft' => 'Qoralama',
        'testing' => 'Test qilinmoqda',
        'validated' => 'Tasdiqlandi',
        'invalidated' => 'Rad etildi',
        'paused' => 'To\'xtatilgan',
    ];

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeTesting($query)
    {
        return $query->where('status', 'testing');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('hypothesis_type', $type);
    }

    // Helpers
    public function getTypeLabel(): string
    {
        return self::HYPOTHESIS_TYPES[$this->hypothesis_type] ?? $this->hypothesis_type;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTestMethodLabel(): ?string
    {
        if (! $this->test_method) {
            return null;
        }

        return self::TEST_METHODS[$this->test_method] ?? $this->test_method;
    }

    public function getFullHypothesis(): string
    {
        return sprintf(
            'AGAR %s, U HOLDA %s, CHUNKI %s',
            $this->if_statement,
            $this->then_statement,
            $this->because_statement
        );
    }

    public function startTesting(): void
    {
        $this->update([
            'status' => 'testing',
            'started_at' => now(),
        ]);
    }

    public function validate(float $actualResult, ?string $learnings = null): void
    {
        $this->update([
            'status' => 'validated',
            'actual_result' => $actualResult,
            'result_date' => now(),
            'completed_at' => now(),
            'learnings' => $learnings,
        ]);
    }

    public function invalidate(float $actualResult, ?string $learnings = null): void
    {
        $this->update([
            'status' => 'invalidated',
            'actual_result' => $actualResult,
            'result_date' => now(),
            'completed_at' => now(),
            'learnings' => $learnings,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isTesting(): bool
    {
        return $this->status === 'testing';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['validated', 'invalidated']);
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }
}
