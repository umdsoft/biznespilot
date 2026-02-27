<?php

namespace App\Models\Bot\Queue;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueBranch extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $table = 'queue_branches';

    protected $fillable = [
        'business_id', 'name', 'address', 'phone',
        'lat', 'lng', 'working_hours', 'lunch_break',
        'slot_duration', 'max_concurrent', 'is_active',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'working_hours' => 'array',
        'lunch_break' => 'array',
        'slot_duration' => 'integer',
        'max_concurrent' => 'integer',
        'is_active' => 'boolean',
    ];

    public function specialists(): HasMany
    {
        return $this->hasMany(QueueSpecialist::class, 'branch_id');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(QueueTimeSlot::class, 'branch_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(QueueBooking::class, 'branch_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(QueueService::class, 'queue_branch_services', 'branch_id', 'service_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
