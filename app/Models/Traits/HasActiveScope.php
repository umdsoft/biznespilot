<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides common active/inactive scoping functionality
 * Reduces code duplication across models that have is_active or status fields
 */
trait HasActiveScope
{
    /**
     * Scope a query to only include active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        // Check which column exists for this model
        if ($this->hasColumn('is_active')) {
            return $query->where('is_active', true);
        }

        if ($this->hasColumn('status')) {
            return $query->where('status', 'active');
        }

        return $query;
    }

    /**
     * Scope a query to only include inactive records.
     */
    public function scopeInactive(Builder $query): Builder
    {
        if ($this->hasColumn('is_active')) {
            return $query->where('is_active', false);
        }

        if ($this->hasColumn('status')) {
            return $query->where('status', 'inactive');
        }

        return $query;
    }

    /**
     * Check if the model is active.
     */
    public function isActive(): bool
    {
        if ($this->hasColumn('is_active')) {
            return (bool) $this->is_active;
        }

        if ($this->hasColumn('status')) {
            return $this->status === 'active';
        }

        return true;
    }

    /**
     * Mark the model as active.
     */
    public function markAsActive(): bool
    {
        if ($this->hasColumn('is_active')) {
            return $this->update(['is_active' => true]);
        }

        if ($this->hasColumn('status')) {
            return $this->update(['status' => 'active']);
        }

        return false;
    }

    /**
     * Mark the model as inactive.
     */
    public function markAsInactive(): bool
    {
        if ($this->hasColumn('is_active')) {
            return $this->update(['is_active' => false]);
        }

        if ($this->hasColumn('status')) {
            return $this->update(['status' => 'inactive']);
        }

        return false;
    }

    /**
     * Toggle the active status.
     */
    public function toggleActive(): bool
    {
        if ($this->isActive()) {
            return $this->markAsInactive();
        }

        return $this->markAsActive();
    }

    /**
     * Check if a column exists in the model's table.
     */
    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->getFillable()) ||
               array_key_exists($column, $this->getAttributes()) ||
               property_exists($this, $column);
    }
}
