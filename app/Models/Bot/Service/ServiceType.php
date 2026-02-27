<?php

namespace App\Models\Bot\Service;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceType extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id', 'category_id', 'name', 'description',
        'price_from', 'price_to', 'estimated_duration',
        'warranty_days', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'price_to' => 'decimal:2',
        'estimated_duration' => 'integer',
        'warranty_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'service_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
