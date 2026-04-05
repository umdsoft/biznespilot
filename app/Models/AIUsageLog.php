<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI token va xarajat qayd modeli.
 * Har bir AI chaqiriq shu jadvalda saqlanadi.
 * BelongsToBusiness trait ishlatilmagan — business_id nullable
 */
class AIUsageLog extends Model
{
    use HasUuid;

    protected $table = 'ai_usage_log';

    protected $fillable = [
        'business_id',
        'agent_type',
        'model',
        'tokens_input',
        'tokens_output',
        'cost_usd',
        'cache_hit',
        'prompt_hash',
    ];

    protected $casts = [
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'cost_usd' => 'decimal:6',
        'cache_hit' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
