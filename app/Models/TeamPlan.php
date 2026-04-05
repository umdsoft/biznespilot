<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TeamPlan extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'plan_type', 'period_start', 'period_end',
        'previous_results', 'agent_suggestions', 'final_plan', 'tasks', 'ai_tokens_used',
    ];

    protected $casts = [
        'period_start' => 'date', 'period_end' => 'date',
        'previous_results' => 'array', 'agent_suggestions' => 'array',
        'final_plan' => 'array', 'tasks' => 'array', 'ai_tokens_used' => 'integer',
    ];
}
