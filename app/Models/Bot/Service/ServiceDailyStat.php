<?php

namespace App\Models\Bot\Service;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceDailyStat extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id', 'date', 'total_requests', 'completed',
        'cancelled', 'total_revenue', 'avg_rating',
        'avg_completion_time', 'top_categories', 'top_masters',
    ];

    protected $casts = [
        'date' => 'date',
        'total_requests' => 'integer',
        'completed' => 'integer',
        'cancelled' => 'integer',
        'total_revenue' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'avg_completion_time' => 'integer',
        'top_categories' => 'array',
        'top_masters' => 'array',
    ];
}
