<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receivable extends Model
{
    use HasUuids, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'deal_id',
        'client_id',
        'responsible_user_id',
        'invoice_number',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'invoice_date',
        'due_date',
        'paid_date',
        'overdue_days',
        'status',
        'notes',
        'payment_history',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'payment_history' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function ($receivable) {
            $receivable->remaining_amount = $receivable->total_amount - $receivable->paid_amount;
            $receivable->overdue_days = $receivable->calculateOverdueDays();
            $receivable->updateStatus();
        });
    }

    // Relationships
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // Calculate overdue days
    public function calculateOverdueDays(): int
    {
        if ($this->status === 'paid') {
            return 0;
        }

        $dueDate = $this->due_date;
        $today = now()->startOfDay();

        if ($today->gt($dueDate)) {
            return $today->diffInDays($dueDate);
        }

        return 0;
    }

    // Update status based on payment
    public function updateStatus(): void
    {
        if ($this->remaining_amount <= 0) {
            $this->status = 'paid';
            $this->paid_date = $this->paid_date ?? now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } elseif ($this->overdue_days > 0) {
            $this->status = 'overdue';
        } else {
            $this->status = 'pending';
        }
    }

    // Add payment
    public function addPayment(float $amount, ?string $note = null): void
    {
        $this->paid_amount += $amount;

        $history = $this->payment_history ?? [];
        $history[] = [
            'amount' => $amount,
            'date' => now()->toDateString(),
            'note' => $note,
        ];
        $this->payment_history = $history;

        $this->save();
    }

    // Getters
    public function getPaidPercentAttribute(): float
    {
        if ($this->total_amount == 0) return 0;
        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'overdue' || $this->overdue_days > 0;
    }

    public function getOverdueSeverityAttribute(): string
    {
        if ($this->overdue_days <= 0) return 'none';
        if ($this->overdue_days <= 7) return 'low';
        if ($this->overdue_days <= 30) return 'medium';
        if ($this->overdue_days <= 90) return 'high';
        return 'critical';
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('responsible_user_id', $userId);
    }

    public function scopeDueWithinDays($query, int $days)
    {
        return $query->where('due_date', '<=', now()->addDays($days))
                     ->whereNotIn('status', ['paid', 'written_off']);
    }
}
