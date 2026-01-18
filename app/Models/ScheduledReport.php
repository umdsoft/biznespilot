<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'report_type',
        'report_name',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time_of_day',
        'timezone',
        'sections',
        'include_charts',
        'include_comparison',
        'comparison_period',
        'delivery_method',
        'recipients',
        'format',
        'is_active',
        'last_sent_at',
        'next_scheduled_at',
    ];

    protected $casts = [
        'sections' => 'array',
        'recipients' => 'array',
        'include_charts' => 'boolean',
        'include_comparison' => 'boolean',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'next_scheduled_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function generatedReports()
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_scheduled_at', '<=', now());
    }

    public function calculateNextScheduledAt()
    {
        $now = now()->setTimezone($this->timezone);
        $time = explode(':', $this->time_of_day);
        $hour = (int) $time[0];
        $minute = (int) ($time[1] ?? 0);

        switch ($this->frequency) {
            case 'daily':
                $next = $now->copy()->setTime($hour, $minute);
                if ($next <= $now) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                $next = $now->copy()->next($this->day_of_week)->setTime($hour, $minute);
                if ($next <= $now) {
                    $next->addWeek();
                }
                break;

            case 'monthly':
                $next = $now->copy()->day($this->day_of_month)->setTime($hour, $minute);
                if ($next <= $now) {
                    $next->addMonth();
                }
                break;

            case 'quarterly':
                $next = $now->copy()->firstOfQuarter()->addMonths(3)->day($this->day_of_month)->setTime($hour, $minute);
                if ($next <= $now) {
                    $next->addQuarter();
                }
                break;

            default:
                $next = $now->copy()->addDay()->setTime($hour, $minute);
        }

        return $next->setTimezone('UTC');
    }

    public function updateNextScheduledAt()
    {
        $this->update([
            'next_scheduled_at' => $this->calculateNextScheduledAt(),
            'last_sent_at' => now(),
        ]);
    }

    public function getDefaultSections()
    {
        return match ($this->report_type) {
            'daily_brief' => ['kpis', 'alerts', 'insights', 'quick_wins'],
            'weekly_summary' => ['executive_summary', 'kpis', 'channels', 'content', 'insights', 'recommendations'],
            'monthly_report' => ['executive_summary', 'kpis', 'funnel', 'channels', 'content', 'budget', 'insights', 'next_month'],
            'quarterly_review' => ['executive_summary', 'goals_review', 'kpis', 'funnel', 'channels', 'budget', 'insights', 'next_quarter'],
            default => ['kpis', 'insights'],
        };
    }

    public function getReportTypeName()
    {
        return match ($this->report_type) {
            'daily_brief' => 'Kunlik Xulosa',
            'weekly_summary' => 'Haftalik Hisobot',
            'monthly_report' => 'Oylik Hisobot',
            'quarterly_review' => 'Choraklik Tahlil',
            'custom' => 'Maxsus Hisobot',
            default => $this->report_type,
        };
    }

    public function getFrequencyName()
    {
        return match ($this->frequency) {
            'daily' => 'Har kuni',
            'weekly' => 'Har hafta',
            'monthly' => 'Har oy',
            'quarterly' => 'Har chorak',
            default => $this->frequency,
        };
    }
}
