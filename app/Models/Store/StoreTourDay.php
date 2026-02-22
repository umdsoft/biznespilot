<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreTourDay extends Model
{
    use HasUuids;

    protected $table = 'store_tour_days';

    protected $fillable = [
        'tour_id',
        'day_number',
        'title',
        'description',
        'location',
        'activities',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'activities' => 'array',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(StoreTour::class, 'tour_id');
    }
}
