<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competitor extends Model
{
    use BelongsToBusiness, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'website',
        'description',
        'strengths',
        'weaknesses',
        'products',
        'pricing',
        'marketing_strategies',
        'threat_level',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'products' => 'array',
        'pricing' => 'array',
        'marketing_strategies' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the activities for the competitor.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CompetitorActivity::class);
    }

    /**
     * Get the metrics for the competitor.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(CompetitorMetric::class);
    }
}
