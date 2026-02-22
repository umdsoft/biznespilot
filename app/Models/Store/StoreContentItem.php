<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreContentItem extends Model
{
    use HasUuids;

    protected $table = 'store_content_items';

    protected $fillable = [
        'plan_id',
        'title',
        'description',
        'content_type',
        'content_url',
        'content_body',
        'is_locked',
        'sort_order',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StoreContentPlan::class, 'plan_id');
    }
}
