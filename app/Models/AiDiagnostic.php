<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiDiagnostic extends Model
{
    use HasUuid;

    protected $table = 'ai_diagnostics';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'business_id',
        'type',
        'status',
        'input_data',
        'results',
        'overall_score',
        'recommendations',
        'completed_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'results' => 'array',
        'recommendations' => 'array',
        'overall_score' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public const TYPES = [
        'full' => 'To\'liq diagnostika',
        'marketing' => 'Marketing diagnostikasi',
        'sales' => 'Savdo diagnostikasi',
        'content' => 'Kontent diagnostikasi',
        'quick' => 'Tezkor diagnostika',
    ];

    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'processing' => 'Jarayonda',
        'completed' => 'Tugallangan',
        'failed' => 'Xatolik',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DiagnosticReport::class, 'diagnostic_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(array $results, ?float $score = null, ?array $recommendations = null): void
    {
        $this->update([
            'status' => 'completed',
            'results' => $results,
            'overall_score' => $score,
            'recommendations' => $recommendations,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
