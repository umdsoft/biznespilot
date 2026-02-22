<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreStaffSchedule extends Model
{
    use HasUuids;

    protected $table = 'store_staff_schedules';

    protected $fillable = [
        'staff_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'is_working',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_working' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StoreStaff::class, 'staff_id');
    }
}
