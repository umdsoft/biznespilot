<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Umr yo'li harakatlari modeli.
 */
class LifecycleAction extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'lifecycle_actions';

    protected $fillable = [
        'business_id', 'customer_id', 'lifecycle_id', 'stage',
        'action_type', 'channel', 'message_template_id', 'message_content',
        'personalized_by_ai', 'status', 'scheduled_at', 'sent_at', 'result_action',
    ];

    protected $casts = [
        'personalized_by_ai' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function lifecycle(): BelongsTo
    {
        return $this->belongsTo(CustomerLifecycle::class, 'lifecycle_id');
    }
}
