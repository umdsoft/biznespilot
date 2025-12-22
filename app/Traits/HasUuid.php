<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Initialize the trait - called on model instantiation
     */
    public function initializeHasUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';

        // Generate UUID on instantiation if not set (works even with WithoutModelEvents)
        if (empty($this->{$this->getKeyName()})) {
            $this->{$this->getKeyName()} = Str::uuid()->toString();
        }
    }

    /**
     * Boot the trait
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
