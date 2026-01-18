<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDailyEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'date',
        // Leads
        'leads_digital',
        'leads_offline',
        'leads_referral',
        'leads_organic',
        'leads_total',
        // Spend
        'spend_digital',
        'spend_offline',
        'spend_other',
        'spend_total',
        // Sales
        'sales_new',
        'sales_repeat',
        'sales_total',
        // Revenue
        'revenue_new',
        'revenue_repeat',
        'revenue_total',
        // Payments
        'payment_cash',
        'payment_card',
        'payment_transfer',
        'payment_credit',
        'payment_other',
        // Calculated
        'avg_check',
        'conversion_rate',
        'cpl',
        'cac',
        // Meta
        'notes',
        'source',
        'created_by',
        'is_complete',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'date' => 'date',
        'spend_digital' => 'decimal:2',
        'spend_offline' => 'decimal:2',
        'spend_other' => 'decimal:2',
        'spend_total' => 'decimal:2',
        'revenue_new' => 'decimal:2',
        'revenue_repeat' => 'decimal:2',
        'revenue_total' => 'decimal:2',
        'payment_cash' => 'decimal:2',
        'payment_card' => 'decimal:2',
        'payment_transfer' => 'decimal:2',
        'payment_credit' => 'decimal:2',
        'payment_other' => 'decimal:2',
        'avg_check' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cpl' => 'decimal:2',
        'cac' => 'decimal:2',
        'is_complete' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate totals before saving
        static::saving(function ($model) {
            $model->calculateTotals();
            $model->calculateMetrics();
        });
    }

    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $this->leads_total = $this->leads_digital + $this->leads_offline + $this->leads_referral + $this->leads_organic;
        $this->spend_total = $this->spend_digital + $this->spend_offline + $this->spend_other;
        $this->sales_total = $this->sales_new + $this->sales_repeat;
        $this->revenue_total = $this->revenue_new + $this->revenue_repeat;
    }

    /**
     * Calculate metrics
     */
    public function calculateMetrics(): void
    {
        // Average check
        if ($this->sales_total > 0) {
            $this->avg_check = $this->revenue_total / $this->sales_total;
        }

        // Conversion rate
        if ($this->leads_total > 0) {
            $this->conversion_rate = ($this->sales_total / $this->leads_total) * 100;
        }

        // Cost Per Lead
        if ($this->leads_total > 0 && $this->spend_total > 0) {
            $this->cpl = $this->spend_total / $this->leads_total;
        }

        // Customer Acquisition Cost
        if ($this->sales_new > 0 && $this->spend_total > 0) {
            $this->cac = $this->spend_total / $this->sales_new;
        }

        // Check if complete
        $this->is_complete = $this->leads_total > 0 || $this->sales_total > 0 || $this->revenue_total > 0;
    }

    /**
     * Get the business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user who created the entry
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get source details
     */
    public function sourceDetails(): HasMany
    {
        return $this->hasMany(KpiDailySourceDetail::class, 'daily_entry_id');
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereYear('date', now()->year)
            ->whereMonth('date', now()->month);
    }

    /**
     * Scope for specific week
     */
    public function scopeWeek($query, int $year, int $week)
    {
        $startOfWeek = now()->setISODate($year, $week)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        return $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope for complete entries
     */
    public function scopeComplete($query)
    {
        return $query->where('is_complete', true);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('d.m.Y');
    }

    /**
     * Get day name in Uzbek
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            'Monday' => 'Dushanba',
            'Tuesday' => 'Seshanba',
            'Wednesday' => 'Chorshanba',
            'Thursday' => 'Payshanba',
            'Friday' => 'Juma',
            'Saturday' => 'Shanba',
            'Sunday' => 'Yakshanba',
        ];

        return $days[$this->date->format('l')] ?? '';
    }

    /**
     * Get short day name
     */
    public function getShortDayNameAttribute(): string
    {
        $days = [
            'Monday' => 'Du',
            'Tuesday' => 'Se',
            'Wednesday' => 'Ch',
            'Thursday' => 'Pa',
            'Friday' => 'Ju',
            'Saturday' => 'Sh',
            'Sunday' => 'Ya',
        ];

        return $days[$this->date->format('l')] ?? '';
    }
}
