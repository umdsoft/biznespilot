<?php

namespace App\Models\Bot\Queue;

use App\Models\Business;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'business_id', 'allow_same_day', 'advance_booking_days',
        'reminder_minutes_before', 'auto_cancel_minutes',
        'require_phone', 'allow_specialist_choice', 'show_queue_position',
    ];

    protected $casts = [
        'allow_same_day' => 'boolean',
        'advance_booking_days' => 'integer',
        'reminder_minutes_before' => 'integer',
        'auto_cancel_minutes' => 'integer',
        'require_phone' => 'boolean',
        'allow_specialist_choice' => 'boolean',
        'show_queue_position' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function getForBusiness(string $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId],
            [
                'allow_same_day' => true,
                'advance_booking_days' => 14,
                'reminder_minutes_before' => 30,
                'auto_cancel_minutes' => 15,
                'require_phone' => true,
                'allow_specialist_choice' => true,
                'show_queue_position' => true,
            ]
        );
    }
}
