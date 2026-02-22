<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreStaffTimeOff extends Model
{
    use HasUuids;

    protected $table = 'store_staff_time_off';

    protected $fillable = [
        'staff_id',
        'date_from',
        'date_to',
        'reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StoreStaff::class, 'staff_id');
    }
}
