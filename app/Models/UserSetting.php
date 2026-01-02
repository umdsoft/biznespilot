<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasUuid;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'openai_api_key',
        'claude_api_key',
        'email_notifications',
        'browser_notifications',
        'marketing_emails',
        'preferred_ai_model',
        'ai_creativity_level',
        'theme',
        'language',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_notifications' => 'boolean',
        'browser_notifications' => 'boolean',
        'marketing_emails' => 'boolean',
        'ai_creativity_level' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'openai_api_key',
        'claude_api_key',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get decrypted OpenAI API key.
     */
    public function getDecryptedOpenAIKey(): ?string
    {
        return $this->openai_api_key ? decrypt($this->openai_api_key) : null;
    }

    /**
     * Get decrypted Claude API key.
     */
    public function getDecryptedClaudeKey(): ?string
    {
        return $this->claude_api_key ? decrypt($this->claude_api_key) : null;
    }

    /**
     * Set encrypted OpenAI API key.
     */
    public function setOpenAIKey(?string $key): void
    {
        $this->openai_api_key = $key ? encrypt($key) : null;
    }

    /**
     * Set encrypted Claude API key.
     */
    public function setClaudeKey(?string $key): void
    {
        $this->claude_api_key = $key ? encrypt($key) : null;
    }
}
