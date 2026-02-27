<?php

namespace App\Models\Bot\Queue;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTimeSlot extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id', 'specialist_id', 'date',
        'start_time', 'end_time', 'status', 'booking_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(QueueBranch::class, 'branch_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(QueueSpecialist::class, 'specialist_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(QueueBooking::class, 'booking_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForSpecialist($query, string $specialistId)
    {
        return $query->where('specialist_id', $specialistId);
    }
}
