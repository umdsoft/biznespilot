<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversation extends Model
{
    use BelongsToBusiness, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'user_id',
        'title',
        'messages',
        'context',
        'status',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'messages' => 'array',
        'context' => 'array',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add a message to the conversation.
     */
    public function addMessage(string $role, string $content): void
    {
        $messages = $this->messages ?? [];

        $messages[] = [
            'role' => $role, // 'user' or 'assistant'
            'content' => $content,
            'timestamp' => now()->toISOString(),
        ];

        $this->update([
            'messages' => $messages,
            'last_message_at' => now(),
        ]);
    }

    /**
     * Add user message.
     */
    public function addUserMessage(string $content): void
    {
        $this->addMessage('user', $content);
    }

    /**
     * Add assistant message.
     */
    public function addAssistantMessage(string $content): void
    {
        $this->addMessage('assistant', $content);
    }

    /**
     * Get the last message.
     */
    public function getLastMessage(): ?array
    {
        $messages = $this->messages ?? [];
        return !empty($messages) ? end($messages) : null;
    }

    /**
     * Get message count.
     */
    public function getMessageCount(): int
    {
        return count($this->messages ?? []);
    }

    /**
     * Archive the conversation.
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Activate the conversation.
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Generate a title based on first message.
     */
    public function generateTitle(): string
    {
        $messages = $this->messages ?? [];

        if (empty($messages)) {
            return 'New Conversation';
        }

        $firstMessage = $messages[0]['content'] ?? '';

        // Take first 50 characters
        $title = substr($firstMessage, 0, 50);

        if (strlen($firstMessage) > 50) {
            $title .= '...';
        }

        return $title ?: 'New Conversation';
    }

    /**
     * Update title if not set.
     */
    public function updateTitleIfNeeded(): void
    {
        if (!$this->title) {
            $this->update(['title' => $this->generateTitle()]);
        }
    }

    /**
     * Scope for active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for archived conversations.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope for recent conversations.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_message_at', '>=', now()->subDays($days));
    }
}
