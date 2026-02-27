<?php

namespace App\Models\Bot\Queue;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueDailyStat extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id', 'branch_id', 'date',
        'total_bookings', 'completed', 'cancelled', 'no_shows',
        'avg_wait_time', 'avg_service_time',
        'peak_hour', 'busiest_service_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_bookings' => 'integer',
        'completed' => 'integer',
        'cancelled' => 'integer',
        'no_shows' => 'integer',
        'avg_wait_time' => 'decimal:2',
        'avg_service_time' => 'decimal:2',
        'peak_hour' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(QueueBranch::class, 'branch_id');
    }

    public function busiestService(): BelongsTo
    {
        return $this->belongsTo(QueueService::class, 'busiest_service_id');
    }
}
