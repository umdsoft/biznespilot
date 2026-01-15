<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAdAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'integration_id',
        'business_id',
        'account_id',
        'meta_account_id',
        'name',
        'currency',
        'timezone',
        'account_status',
        'amount_spent',
        'is_primary',
        'metadata',
        'last_sync_at',
    ];

    protected $casts = [
        'amount_spent' => 'decimal:2',
        'is_primary' => 'boolean',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MetaCampaign::class, 'ad_account_id');
    }

    public function insights(): HasMany
    {
        return $this->hasMany(MetaInsight::class, 'ad_account_id');
    }

    /**
     * Get clean account ID without act_ prefix
     */
    public function getCleanAccountIdAttribute(): string
    {
        return str_replace('act_', '', $this->meta_account_id ?? $this->account_id ?? '');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('account_status', 1);
    }
}
