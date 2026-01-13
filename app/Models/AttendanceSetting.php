<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'work_start_time',
        'work_end_time',
        'work_hours_per_day',
        'late_threshold_minutes',
        'require_location',
        'allow_remote_checkin',
        'office_locations',
    ];

    protected $casts = [
        'require_location' => 'boolean',
        'allow_remote_checkin' => 'boolean',
        'office_locations' => 'array',
    ];

    /**
     * Get settings for business, create default if not exists
     */
    public static function getOrCreateForBusiness($businessId): self
    {
        return self::firstOrCreate(
            ['business_id' => $businessId],
            [
                'work_start_time' => '09:00:00',
                'work_end_time' => '18:00:00',
                'work_hours_per_day' => 8,
                'late_threshold_minutes' => 15,
                'require_location' => false,
                'allow_remote_checkin' => true,
                'office_locations' => [],
            ]
        );
    }

    /**
     * Check if location is required
     */
    public function isLocationRequired(): bool
    {
        return $this->require_location;
    }

    /**
     * Check if remote check-in is allowed
     */
    public function isRemoteCheckinAllowed(): bool
    {
        return $this->allow_remote_checkin;
    }

    /**
     * Get formatted work hours
     */
    public function getFormattedWorkHoursAttribute(): string
    {
        return $this->work_start_time . ' - ' . $this->work_end_time;
    }
}
