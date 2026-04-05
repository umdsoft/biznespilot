<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mijoz umr yo'li modeli.
 */
class CustomerLifecycle extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'customer_lifecycle';

    protected $fillable = [
        'business_id', 'customer_id', 'current_stage', 'previous_stage',
        'stage_entered_at', 'next_action_at', 'next_action_type',
        'total_purchases', 'total_spent', 'last_purchase_at',
        'birthday', 'preferred_channel', 'lifecycle_score',
    ];

    protected $casts = [
        'stage_entered_at' => 'datetime',
        'next_action_at' => 'datetime',
        'last_purchase_at' => 'datetime',
        'birthday' => 'date',
        'total_purchases' => 'integer',
        'total_spent' => 'decimal:2',
        'lifecycle_score' => 'integer',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(LifecycleAction::class, 'lifecycle_id');
    }
}
