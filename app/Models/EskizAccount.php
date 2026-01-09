<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EskizAccount extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    protected $fillable = [
        'business_id',
        'email',
        'password',
        'sender_name',
        'access_token',
        'token_expires_at',
        'is_active',
        'balance',
        'last_sync_at',
        'last_error',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'access_token',
    ];

    /**
     * Get SMS messages sent through this account
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'eskiz_account_id');
    }

    /**
     * Get the business that owns this account
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Check if the access token is still valid
     */
    public function isTokenValid(): bool
    {
        return $this->access_token &&
               $this->token_expires_at &&
               $this->token_expires_at->isFuture();
    }

    /**
     * Scope: Only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get decrypted password for API calls
     */
    public function getDecryptedPassword(): string
    {
        return $this->password;
    }
}
