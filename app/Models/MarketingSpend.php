<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingSpend extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'channel_id',
        'amount',
        'currency',
        'date',
        'category',
        'description',
        'invoice_number',
        'receipt_path',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the marketing channel for the spend.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }
}
