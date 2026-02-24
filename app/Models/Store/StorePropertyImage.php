<?php

namespace App\Models\Store;

use App\Traits\NormalizesImageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePropertyImage extends Model
{
    use HasUuids, NormalizesImageUrl;

    protected $table = 'store_property_images';

    protected $fillable = [
        'property_id',
        'image_url',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(StoreProperty::class, 'property_id');
    }
}
