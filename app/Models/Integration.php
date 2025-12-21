<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'type',
        'name',
        'description',
        'is_active',
        'status',
        'connected_at',
        'expires_at',
        'credentials',
        'config',
        'last_sync_at',
        'last_error_at',
        'last_error_message',
        'sync_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'encrypted',
        'config' => 'array',
        'connected_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    /**
     * Check if integration is connected
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected' && $this->is_active;
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get decrypted access token
     */
    public function getAccessToken(): ?string
    {
        if (!$this->credentials) {
            return null;
        }

        $credentials = json_decode($this->credentials, true);
        return $credentials['access_token'] ?? null;
    }

    /**
     * Scope for Meta Ads integrations
     */
    public function scopeMetaAds($query)
    {
        return $query->where('type', 'meta_ads');
    }

    /**
     * Scope for connected integrations
     */
    public function scopeConnected($query)
    {
        return $query->where('status', 'connected')->where('is_active', true);
    }

    /**
     * Ad Accounts relationship
     */
    public function adAccounts()
    {
        return $this->hasMany(MetaAdAccount::class);
    }
}
