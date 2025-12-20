<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    protected static function bootLogsActivity(): void
    {
        // Log when model is created
        static::created(function ($model) {
            if ($model->shouldLogActivity('created')) {
                ActivityLogger::created($model, $model->getActivityMetadata());
            }
        });

        // Log when model is updated
        static::updated(function ($model) {
            if ($model->shouldLogActivity('updated')) {
                $changes = [
                    'old' => $model->getOriginal(),
                    'new' => $model->getAttributes(),
                ];

                ActivityLogger::updated($model, $changes, $model->getActivityMetadata());
            }
        });

        // Log when model is deleted
        static::deleted(function ($model) {
            if ($model->shouldLogActivity('deleted')) {
                ActivityLogger::deleted($model, $model->getActivityMetadata());
            }
        });

        // Log when model is restored (if using soft deletes)
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                if ($model->shouldLogActivity('restored')) {
                    ActivityLogger::restored($model, $model->getActivityMetadata());
                }
            });
        }
    }

    /**
     * Determine if the activity should be logged for this event
     */
    protected function shouldLogActivity(string $event): bool
    {
        // Check if model has defined which events to log
        if (property_exists($this, 'logEvents')) {
            return in_array($event, $this->logEvents);
        }

        // Check if model has defined which events NOT to log
        if (property_exists($this, 'dontLogEvents')) {
            return !in_array($event, $this->dontLogEvents);
        }

        // By default, log all events
        return true;
    }

    /**
     * Get metadata to include with the activity log
     */
    protected function getActivityMetadata(): ?array
    {
        // Override this method in your model to add custom metadata
        return null;
    }

    /**
     * Get activity logs for this model
     */
    public function activityLogs()
    {
        return ActivityLogger::getModelActivity($this);
    }
}
