<?php

namespace App\Models\Bot\Service;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceMaster extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'phone', 'avatar_url',
        'specializations', 'experience_years', 'bio',
        'warranty_months', 'rating_avg', 'rating_count',
        'completed_jobs', 'hourly_rate', 'is_available',
        'available_from', 'location_lat', 'location_lng',
        'is_active',
    ];

    protected $casts = [
        'specializations' => 'array',
        'experience_years' => 'integer',
        'warranty_months' => 'integer',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'completed_jobs' => 'integer',
        'hourly_rate' => 'decimal:2',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceCategory::class,
            'service_master_categories',
            'master_id',
            'category_id'
        );
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'master_id');
    }

    public function updateLocation(float $lat, float $lng): bool
    {
        return $this->update([
            'location_lat' => $lat,
            'location_lng' => $lng,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('is_active', true);
    }
}
