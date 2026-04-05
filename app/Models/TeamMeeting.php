<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TeamMeeting extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'meeting_type', 'meeting_date', 'agent_reports',
        'director_summary', 'urgent_items', 'action_items',
        'ai_tokens_used', 'ai_model_used', 'sent_to_owner', 'sent_at',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'agent_reports' => 'array',
        'urgent_items' => 'array',
        'action_items' => 'array',
        'ai_tokens_used' => 'integer',
        'sent_to_owner' => 'boolean',
        'sent_at' => 'datetime',
    ];
}
