<?php

namespace App\Models\Bot\Queue;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueSpecialist extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id', 'branch_id', 'name', 'phone',
        'avatar_url', 'specialization', 'bio',
        'rating_avg', 'rating_count', 'is_active',
    ];

    protected $casts = [
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(QueueBranch::class, 'branch_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(QueueService::class, 'queue_specialist_services', 'specialist_id', 'service_id')
            ->withTimestamps();
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(QueueTimeSlot::class, 'specialist_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(QueueBooking::class, 'specialist_id');
    }
}
