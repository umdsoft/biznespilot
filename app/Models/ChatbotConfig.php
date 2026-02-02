<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotConfig extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'platform',
        'welcome_message',
        'default_response',
        'is_active',
        'ai_enabled',
        'human_handoff_enabled',
        'config',
        'business_hours',
        'auto_responses',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
        'human_handoff_enabled' => 'boolean',
        'config' => 'array',
        'business_hours' => 'array',
        'auto_responses' => 'array',
    ];

    /**
     * Get the conversations for the chatbot config.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class, 'config_id');
    }
}
