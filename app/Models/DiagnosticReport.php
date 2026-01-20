<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DiagnosticReport extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'diagnostic_id',
        'report_type',
        'report_format',
        'title',
        'content',
        'html_content',
        'pdf_path',
        'sent_to_email',
        'sent_at',
        'opened_at',
        'download_count',
    ];

    protected $casts = [
        'content' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Constants
    public const REPORT_TYPES = [
        'summary' => 'Qisqacha Hisobot',
        'detailed' => 'To\'liq Hisobot',
        'executive' => 'Rahbar Hisoboti',
    ];

    public const FORMATS = [
        'html' => 'HTML',
        'pdf' => 'PDF',
        'json' => 'JSON',
    ];

    // Relationships
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(AiDiagnostic::class, 'diagnostic_id');
    }

    // Helpers
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function markAsSent(string $email): void
    {
        $this->update([
            'sent_to_email' => $email,
            'sent_at' => now(),
        ]);
    }

    public function markAsOpened(): void
    {
        if (! $this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }

    public function getTypeLabel(): string
    {
        return self::REPORT_TYPES[$this->report_type] ?? $this->report_type;
    }

    public function getFormatLabel(): string
    {
        return self::FORMATS[$this->report_format] ?? $this->report_format;
    }

    public function hasPdf(): bool
    {
        return ! empty($this->pdf_path);
    }

    public function getPdfUrl(): ?string
    {
        if (! $this->hasPdf()) {
            return null;
        }

        return asset('storage/'.$this->pdf_path);
    }
}
