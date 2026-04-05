<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class VoiceInteraction extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id', 'user_id', 'conversation_id',
        'audio_input_url', 'audio_input_duration_sec', 'transcript_text',
        'detected_language', 'response_text', 'audio_output_url',
        'audio_output_duration_sec', 'whisper_cost_usd', 'tts_cost_usd',
        'total_cost_usd', 'processing_time_ms',
    ];

    protected $casts = [
        'audio_input_duration_sec' => 'integer',
        'audio_output_duration_sec' => 'integer',
        'whisper_cost_usd' => 'decimal:6',
        'tts_cost_usd' => 'decimal:6',
        'total_cost_usd' => 'decimal:6',
        'processing_time_ms' => 'integer',
    ];
}
