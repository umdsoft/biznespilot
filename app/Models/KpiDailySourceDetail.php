<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiDailySourceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_entry_id',
        'lead_source_id',
        'leads_count',
        'spend_amount',
        'conversions',
        'revenue',
    ];

    protected $casts = [
        'spend_amount' => 'decimal:2',
        'revenue' => 'decimal:2',
    ];

    /**
     * Get the daily entry
     */
    public function dailyEntry(): BelongsTo
    {
        return $this->belongsTo(KpiDailyEntry::class, 'daily_entry_id');
    }

    /**
     * Get the lead source
     */
    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    /**
     * Get conversion rate for this source
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->leads_count > 0) {
            return round(($this->conversions / $this->leads_count) * 100, 2);
        }
        return 0;
    }

    /**
     * Get cost per lead for this source
     */
    public function getCplAttribute(): float
    {
        if ($this->leads_count > 0 && $this->spend_amount > 0) {
            return round($this->spend_amount / $this->leads_count, 0);
        }
        return 0;
    }

    /**
     * Get ROI for this source
     */
    public function getRoiAttribute(): float
    {
        if ($this->spend_amount > 0) {
            return round((($this->revenue - $this->spend_amount) / $this->spend_amount) * 100, 1);
        }
        return $this->revenue > 0 ? 100 : 0; // If no spend but has revenue, ROI is infinite (show 100%)
    }
}
