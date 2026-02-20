<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoContentRequest extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'content_generation_id',
        'video_url',
        'platform',
        'video_title',
        'video_duration',
        'thumbnail_url',
        'status',
        'error_message',
        'transcript',
        'key_points',
        'content_type',
        'purpose',
        'target_channel',
        'stt_cost',
        'analysis_cost',
        'total_cost',
        'processing_time_ms',
        'stt_model',
        'analysis_model',
        'input_tokens',
        'output_tokens',
    ];

    protected $casts = [
        'key_points' => 'array',
        'stt_cost' => 'float',
        'analysis_cost' => 'float',
        'total_cost' => 'float',
    ];

    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'extracting' => 'Audio ajratilmoqda',
        'transcribing' => 'Matnga o\'tkazilmoqda',
        'analyzing' => 'Tahlil qilinmoqda',
        'generating' => 'Kontent yaratilmoqda',
        'completed' => 'Tayyor',
        'failed' => 'Xatolik',
    ];

    public const PLATFORMS = [
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contentGeneration(): BelongsTo
    {
        return $this->belongsTo(ContentGeneration::class);
    }

    public function markStatus(string $status): self
    {
        $this->update(['status' => $status]);

        return $this;
    }

    public function markFailed(string $error): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);

        return $this;
    }

    public function markCompleted(string $contentGenerationId): self
    {
        $this->update([
            'status' => 'completed',
            'content_generation_id' => $contentGenerationId,
        ]);

        return $this;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Detect platform from URL
     */
    public static function detectPlatform(string $url): ?string
    {
        if (preg_match('/youtube\.com|youtu\.be/i', $url)) {
            return 'youtube';
        }
        if (preg_match('/instagram\.com/i', $url)) {
            return 'instagram';
        }
        if (preg_match('/tiktok\.com/i', $url)) {
            return 'tiktok';
        }

        return null;
    }
}
