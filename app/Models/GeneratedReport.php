<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class GeneratedReport extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    // Type constants
    public const TYPE_SCHEDULED = 'scheduled';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_REALTIME = 'realtime';

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DELIVERED = 'delivered';

    // Period type constants
    public const PERIOD_DAILY = 'daily';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_QUARTERLY = 'quarterly';

    public const PERIOD_YEARLY = 'yearly';

    public const PERIOD_CUSTOM = 'custom';

    protected $fillable = [
        'scheduled_report_id',
        'business_id',
        'user_id',
        'report_schedule_id',
        'report_template_id',
        'report_type',
        'title',
        'type',
        'category',
        'period_start',
        'period_end',
        'period_type',
        'content',
        'summary',
        'highlights',
        'metrics_data',
        'trends_data',
        'insights',
        'recommendations',
        'comparisons',
        'anomalies',
        'health_score',
        'health_breakdown',
        'content_text',
        'content_html',
        'html_path',
        'pdf_path',
        'excel_path',
        'sent_to',
        'delivery_status',
        'delivered_at',
        'delivery_errors',
        'download_count',
        'generation_time_seconds',
        'generation_time_ms',
        'ai_tokens_used',
        'language',
        'metadata',
        'status',
        'error_message',
        'view_count',
        'last_viewed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'content' => 'array',
        'highlights' => 'array',
        'sent_to' => 'array',
        'metrics_data' => 'array',
        'trends_data' => 'array',
        'insights' => 'array',
        'recommendations' => 'array',
        'comparisons' => 'array',
        'anomalies' => 'array',
        'health_breakdown' => 'array',
        'delivery_status' => 'array',
        'delivery_errors' => 'array',
        'metadata' => 'array',
        'delivered_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'health_score' => 'decimal:2',
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

        return $this->period_start->format('d.m.Y').' - '.$this->period_end->format('d.m.Y');
    }

    public function getHtmlUrl()
    {
        if (! $this->html_path) {
            return null;
        }

        return Storage::url($this->html_path);
    }

    public function getPdfUrl()
    {
        if (! $this->pdf_path) {
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
        return ! empty($this->pdf_path) && Storage::exists($this->pdf_path);
    }

    public function hasHtml()
    {
        return ! empty($this->html_path) && Storage::exists($this->html_path);
    }

    // New relationships for algorithmic reports
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'report_schedule_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    // New scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('type', self::TYPE_SCHEDULED);
    }

    public function scopeManual($query)
    {
        return $query->where('type', self::TYPE_MANUAL);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // New helper attributes
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_GENERATING => 'Yaratilmoqda',
            self::STATUS_COMPLETED => 'Tayyor',
            self::STATUS_FAILED => 'Xatolik',
            self::STATUS_DELIVERED => 'Yuborildi',
            default => $this->status ?? 'Noma\'lum',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_GENERATING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_DELIVERED => 'green',
            default => 'gray',
        };
    }

    public function getHealthScoreColorAttribute(): string
    {
        if (! $this->health_score) {
            return 'gray';
        }

        return match (true) {
            $this->health_score >= 80 => 'green',
            $this->health_score >= 60 => 'blue',
            $this->health_score >= 40 => 'yellow',
            default => 'red',
        };
    }

    public function getHealthScoreLabelAttribute(): string
    {
        if (! $this->health_score) {
            return 'Noma\'lum';
        }

        return match (true) {
            $this->health_score >= 80 => 'Ajoyib',
            $this->health_score >= 60 => 'Yaxshi',
            $this->health_score >= 40 => 'O\'rtacha',
            default => 'Zaif',
        };
    }

    // Status management methods
    public function markAsGenerating(): void
    {
        $this->status = self::STATUS_GENERATING;
        $this->save();
    }

    public function markAsCompleted(?int $generationTimeMs = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        if ($generationTimeMs) {
            $this->generation_time_ms = $generationTimeMs;
        }
        $this->save();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->save();
    }

    public function markAsDelivered(array $deliveryStatus = []): void
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_at = now();
        $this->delivery_status = $deliveryStatus;
        $this->save();
    }

    public function recordView(): void
    {
        $this->increment('view_count');
        $this->last_viewed_at = now();
        $this->save();
    }

    public function getExcelUrl()
    {
        if (! $this->excel_path) {
            return null;
        }

        return Storage::url($this->excel_path);
    }

    public function deleteFiles(): void
    {
        if ($this->pdf_path) {
            Storage::delete($this->pdf_path);
        }
        if ($this->excel_path) {
            Storage::delete($this->excel_path);
        }
        if ($this->html_path) {
            Storage::delete($this->html_path);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($report) {
            $report->deleteFiles();
        });
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'period' => $this->getPeriodLabel(),
            'health_score' => $this->health_score,
            'health_score_label' => $this->health_score_label,
            'health_score_color' => $this->health_score_color,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'insights_count' => count($this->insights ?? []),
            'recommendations_count' => count($this->recommendations ?? []),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'has_pdf' => $this->hasPdf(),
            'has_excel' => (bool) $this->excel_path,
        ];
    }
}
