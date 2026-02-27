<?php

namespace App\Models\Bot\Queue;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueService extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'slug', 'description', 'icon',
        'duration_min', 'duration_max', 'price',
        'is_active', 'sort_order', 'requires_branch',
    ];

    protected $casts = [
        'duration_min' => 'integer',
        'duration_max' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'requires_branch' => 'boolean',
    ];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(QueueBranch::class, 'queue_branch_services', 'service_id', 'branch_id')
            ->withTimestamps();
    }

    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(QueueSpecialist::class, 'queue_specialist_services', 'service_id', 'specialist_id')
            ->withTimestamps();
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
