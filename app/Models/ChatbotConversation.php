<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotConversation extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'config_id',
        'customer_id',
        'lead_id',
        'platform',
        'platform_user_id',
        'platform_conversation_id',
        'customer_name',
        'customer_phone',
        'status',
        'last_message_at',
        'messages_count',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_message_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the chatbot config for the conversation.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(ChatbotConfig::class, 'config_id');
    }

    /**
     * Get the user assigned to the conversation.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }
}
