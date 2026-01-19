<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferComponent extends Model
{
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'offer_id',
        'type',
        'name',
        'description',
        'value',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the offer that owns this component.
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Alias for sort_order to maintain compatibility
     */
    public function getOrderAttribute(): int
    {
        return $this->sort_order ?? 0;
    }

    /**
     * Check if component is highlighted (based on type)
     */
    public function getIsHighlightedAttribute(): bool
    {
        return in_array($this->type, ['bonus', 'guarantee', 'main']);
    }
}
