<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayMobileAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'playmobile_accounts';

    protected $fillable = [
        'business_id',
        'login',
        'password',
        'originator',
        'api_url',
        'is_active',
        'balance',
        'last_sync_at',
        'last_error',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get SMS messages sent through this account
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'playmobile_account_id');
    }

    /**
     * Get the business that owns this account
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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

    /**
     * Get Basic Auth header value
     */
    public function getBasicAuthHeader(): string
    {
        return 'Basic ' . base64_encode($this->login . ':' . $this->password);
    }
}
