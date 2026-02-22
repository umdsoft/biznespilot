<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreStaff extends Model
{
    use HasUuids;

    protected $table = 'store_staff';

    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'email',
        'photo_url',
        'position',
        'bio',
        'specializations',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'specializations' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StoreStaffSchedule::class, 'staff_id');
    }

    public function timeOffs(): HasMany
    {
        return $this->hasMany(StoreStaffTimeOff::class, 'staff_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(StoreBooking::class, 'staff_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
