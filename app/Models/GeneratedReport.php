<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GeneratedReport extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'scheduled_report_id',
        'business_id',
        'report_type',
        'title',
        'period_start',
        'period_end',
        'content',
        'summary',
        'highlights',
        'html_path',
        'pdf_path',
        'sent_to',
        'download_count',
        'generation_time_seconds',
        'ai_tokens_used',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'content' => 'array',
        'highlights' => 'array',
        'sent_to' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scheduledReport()
    {
        return $this->belongsTo(ScheduledReport::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
                     ->where('period_end', '<=', $end);
    }

    public function getReportTypeName()
    {
        return match ($this->report_type) {
            'daily_brief' => 'Kunlik Xulosa',
            'weekly_summary' => 'Haftalik Hisobot',
            'monthly_report' => 'Oylik Hisobot',
            'quarterly_review' => 'Choraklik Tahlil',
            'diagnostic' => 'Diagnostika Hisoboti',
            'custom' => 'Maxsus Hisobot',
            default => $this->report_type,
        };
    }

    public function getPeriodLabel()
    {
        if ($this->period_start->eq($this->period_end)) {
            return $this->period_start->format('d.m.Y');
        }

        return $this->period_start->format('d.m.Y') . ' - ' . $this->period_end->format('d.m.Y');
    }

    public function getHtmlUrl()
    {
        if (!$this->html_path) {
            return null;
        }

        return Storage::url($this->html_path);
    }

    public function getPdfUrl()
    {
        if (!$this->pdf_path) {
            return null;
        }

        return Storage::url($this->pdf_path);
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public function recordSentTo($email, $name = null)
    {
        $sentTo = $this->sent_to ?? [];
        $sentTo[] = [
            'email' => $email,
            'name' => $name,
            'sent_at' => now()->toISOString(),
        ];

        $this->update(['sent_to' => $sentTo]);
    }

    public function hasPdf()
    {
        return !empty($this->pdf_path) && Storage::exists($this->pdf_path);
    }

    public function hasHtml()
    {
        return !empty($this->html_path) && Storage::exists($this->html_path);
    }
}
