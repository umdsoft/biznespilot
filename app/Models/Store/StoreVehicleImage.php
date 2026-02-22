<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreVehicleImage extends Model
{
    use HasUuids;

    protected $table = 'store_vehicle_images';

    protected $fillable = [
        'vehicle_id',
        'image_url',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(StoreVehicle::class, 'vehicle_id');
    }
}
