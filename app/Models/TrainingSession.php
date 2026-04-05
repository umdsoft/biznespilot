<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'trainee_user_id', 'trainer_user_id', 'session_type',
        'status', 'messages', 'overall_score', 'stage_scores', 'strengths',
        'improvements', 'recommended_next_session', 'duration_seconds',
        'ai_tokens_used', 'completed_at',
    ];

    protected $casts = [
        'messages' => 'array',
        'stage_scores' => 'array',
        'strengths' => 'array',
        'improvements' => 'array',
        'overall_score' => 'integer',
        'duration_seconds' => 'integer',
        'ai_tokens_used' => 'integer',
        'completed_at' => 'datetime',
    ];
}
