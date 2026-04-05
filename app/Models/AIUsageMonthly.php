<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Oylik AI xarajat umumlashtirish modeli.
 */
class AIUsageMonthly extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'ai_usage_monthly';

    protected $fillable = [
        'business_id',
        'month',
        'total_requests',
        'cache_hit_count',
        'total_tokens_input',
        'total_tokens_output',
        'total_cost_usd',
        'model_breakdown',
    ];

    protected $casts = [
        'month' => 'date',
        'total_requests' => 'integer',
        'cache_hit_count' => 'integer',
        'total_tokens_input' => 'integer',
        'total_tokens_output' => 'integer',
        'total_cost_usd' => 'decimal:4',
        'model_breakdown' => 'array',
    ];
}
