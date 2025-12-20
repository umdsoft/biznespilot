<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferComponent extends Model
{
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
        'order',
        'is_highlighted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'is_highlighted' => 'boolean',
    ];

    /**
     * Get the offer that owns the component.
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
