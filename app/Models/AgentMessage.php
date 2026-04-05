<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agent xabar modeli.
 * Suhbatdagi har bir xabar (foydalanuvchi yoki agent).
 */
class AgentMessage extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'agent_messages';

    protected $fillable = [
        'conversation_id',
        'business_id',
        'role',
        'content',
        'agent_type',
        'model_used',
        'tokens_input',
        'tokens_output',
        'cost_usd',
        'routed_to',
        'processing_time_ms',
    ];

    protected $casts = [
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'cost_usd' => 'decimal:6',
        'routed_to' => 'array',
        'processing_time_ms' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }
}
