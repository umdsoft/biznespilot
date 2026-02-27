<?php

namespace App\Models\Bot\Service;

use App\Models\Business;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'business_id', 'auto_assign_master', 'allow_master_choice',
        'require_cost_approval', 'show_master_location',
        'max_images', 'working_hours', 'service_area',
    ];

    protected $casts = [
        'auto_assign_master' => 'boolean',
        'allow_master_choice' => 'boolean',
        'require_cost_approval' => 'boolean',
        'show_master_location' => 'boolean',
        'max_images' => 'integer',
        'working_hours' => 'array',
        'service_area' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function getForBusiness(string $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId],
            [
                'auto_assign_master' => false,
                'allow_master_choice' => true,
                'require_cost_approval' => true,
                'show_master_location' => false,
                'max_images' => 5,
            ]
        );
    }
}
