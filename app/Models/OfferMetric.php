<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferMetric extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    protected $fillable = [
        'offer_id',
        'business_id',
        'date',
        'sends_count',
        'deliveries_count',
        'views_count',
        'unique_views_count',
        'clicks_count',
        'unique_clicks_count',
        'conversions_count',
        'rejections_count',
        'total_revenue',
        'total_discounts',
        'delivery_rate',
        'view_rate',
        'click_rate',
        'conversion_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'sends_count' => 'integer',
        'deliveries_count' => 'integer',
        'views_count' => 'integer',
        'unique_views_count' => 'integer',
        'clicks_count' => 'integer',
        'unique_clicks_count' => 'integer',
        'conversions_count' => 'integer',
        'rejections_count' => 'integer',
        'total_revenue' => 'decimal:2',
        'total_discounts' => 'decimal:2',
        'delivery_rate' => 'decimal:2',
        'view_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    // Relationships

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    // Helper methods

    /**
     * Calculate and update rates
     */
    public function calculateRates(): self
    {
        if ($this->sends_count > 0) {
            $this->delivery_rate = ($this->deliveries_count / $this->sends_count) * 100;
            $this->view_rate = ($this->unique_views_count / $this->sends_count) * 100;
            $this->click_rate = ($this->unique_clicks_count / $this->sends_count) * 100;
            $this->conversion_rate = ($this->conversions_count / $this->sends_count) * 100;
        }

        return $this;
    }

    /**
     * Increment a metric
     */
    public function incrementMetric(string $metric, int $amount = 1): self
    {
        $this->increment($metric, $amount);
        $this->calculateRates();
        $this->save();

        return $this;
    }

    /**
     * Add revenue
     */
    public function addRevenue(float $amount): self
    {
        $this->increment('total_revenue', $amount);
        $this->increment('conversions_count');
        $this->calculateRates();
        $this->save();

        return $this;
    }

    // Scopes

    public function scopeForOffer($query, $offerId)
    {
        return $query->where('offer_id', $offerId);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // Static helpers

    /**
     * Get or create metric for offer and date
     */
    public static function getOrCreate(string $offerId, string $businessId, $date = null): self
    {
        $date = $date ?? today();

        return self::firstOrCreate(
            [
                'offer_id' => $offerId,
                'date' => $date,
            ],
            [
                'business_id' => $businessId,
            ]
        );
    }

    /**
     * Get aggregated metrics for an offer
     */
    public static function getAggregatedMetrics(string $offerId, $startDate = null, $endDate = null): array
    {
        $query = self::where('offer_id', $offerId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $metrics = $query->selectRaw('
            SUM(sends_count) as total_sends,
            SUM(deliveries_count) as total_deliveries,
            SUM(views_count) as total_views,
            SUM(unique_views_count) as total_unique_views,
            SUM(clicks_count) as total_clicks,
            SUM(unique_clicks_count) as total_unique_clicks,
            SUM(conversions_count) as total_conversions,
            SUM(rejections_count) as total_rejections,
            SUM(total_revenue) as total_revenue,
            SUM(total_discounts) as total_discounts
        ')->first();

        $totalSends = $metrics->total_sends ?? 0;

        return [
            'total_sends' => (int) $totalSends,
            'total_deliveries' => (int) ($metrics->total_deliveries ?? 0),
            'total_views' => (int) ($metrics->total_views ?? 0),
            'total_unique_views' => (int) ($metrics->total_unique_views ?? 0),
            'total_clicks' => (int) ($metrics->total_clicks ?? 0),
            'total_unique_clicks' => (int) ($metrics->total_unique_clicks ?? 0),
            'total_conversions' => (int) ($metrics->total_conversions ?? 0),
            'total_rejections' => (int) ($metrics->total_rejections ?? 0),
            'total_revenue' => (float) ($metrics->total_revenue ?? 0),
            'total_discounts' => (float) ($metrics->total_discounts ?? 0),
            'delivery_rate' => $totalSends > 0 ? round(($metrics->total_deliveries / $totalSends) * 100, 2) : 0,
            'view_rate' => $totalSends > 0 ? round(($metrics->total_unique_views / $totalSends) * 100, 2) : 0,
            'click_rate' => $totalSends > 0 ? round(($metrics->total_unique_clicks / $totalSends) * 100, 2) : 0,
            'conversion_rate' => $totalSends > 0 ? round(($metrics->total_conversions / $totalSends) * 100, 2) : 0,
        ];
    }
}
