<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides common status-based scoping functionality
 * Reduces code duplication across models with status fields
 */
trait HasStatusScope
{
    /**
     * Get the status column name.
     * Override this method in your model if you need a different column.
     */
    protected function getStatusColumn(): string
    {
        return $this->statusColumn ?? 'status';
    }

    /**
     * Scope a query to a specific status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where($this->getStatusColumn(), $status);
    }

    /**
     * Scope a query to multiple statuses.
     */
    public function scopeWithStatuses(Builder $query, array $statuses): Builder
    {
        return $query->whereIn($this->getStatusColumn(), $statuses);
    }

    /**
     * Scope a query to exclude specific statuses.
     */
    public function scopeWithoutStatus(Builder $query, string|array $statuses): Builder
    {
        $statuses = is_array($statuses) ? $statuses : [$statuses];
        return $query->whereNotIn($this->getStatusColumn(), $statuses);
    }

    /**
     * Scope for pending status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where($this->getStatusColumn(), 'pending');
    }

    /**
     * Scope for completed status.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where($this->getStatusColumn(), 'completed');
    }

    /**
     * Scope for cancelled status.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where($this->getStatusColumn(), 'cancelled');
    }

    /**
     * Scope for draft status.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where($this->getStatusColumn(), 'draft');
    }

    /**
     * Scope for published status.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->getStatusColumn(), 'published');
    }

    /**
     * Check if the model has a specific status.
     */
    public function hasStatus(string $status): bool
    {
        return $this->{$this->getStatusColumn()} === $status;
    }

    /**
     * Check if the model is pending.
     */
    public function isPending(): bool
    {
        return $this->hasStatus('pending');
    }

    /**
     * Check if the model is completed.
     */
    public function isCompleted(): bool
    {
        return $this->hasStatus('completed');
    }

    /**
     * Update the model's status.
     */
    public function updateStatus(string $status): bool
    {
        return $this->update([$this->getStatusColumn() => $status]);
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(): bool
    {
        return $this->updateStatus('completed');
    }

    /**
     * Mark as pending.
     */
    public function markAsPending(): bool
    {
        return $this->updateStatus('pending');
    }

    /**
     * Mark as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->updateStatus('cancelled');
    }
}
