<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticActionProgress extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'diagnostic_action_progress';

    protected $fillable = [
        'diagnostic_id',
        'business_id',
        'step_order',
        'step_title',
        'module_route',
        'status',
        'started_at',
        'completed_at',
        'result_score_before',
        'result_score_after',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Constants
    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'in_progress' => 'Jarayonda',
        'completed' => 'Bajarildi',
        'skipped' => "O'tkazib yuborildi",
    ];

    // Relationships
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(AIDiagnostic::class, 'diagnostic_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('step_order', 'asc');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(?int $scoreBefore = null, ?int $scoreAfter = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result_score_before' => $scoreBefore,
            'result_score_after' => $scoreAfter,
        ]);
    }

    public function skip(): void
    {
        $this->update([
            'status' => 'skipped',
            'completed_at' => now(),
        ]);
    }

    public function getScoreImprovement(): ?int
    {
        if ($this->result_score_before === null || $this->result_score_after === null) {
            return null;
        }
        return $this->result_score_after - $this->result_score_before;
    }
}
