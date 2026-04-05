<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use BelongsToBusiness, HasUuids;

    const TYPE_PHONE = 'phone';
    const TYPE_VIDEO = 'video';
    const TYPE_IN_PERSON = 'in_person';
    const TYPE_TECHNICAL = 'technical';

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'business_id', 'job_application_id', 'interview_type', 'scheduled_at',
        'duration_minutes', 'location', 'meeting_link', 'interviewer_id',
        'status', 'feedback', 'rating', 'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'rating' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)->where('scheduled_at', '>', now());
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->interview_type) {
            self::TYPE_PHONE => 'Telefon',
            self::TYPE_VIDEO => 'Video',
            self::TYPE_IN_PERSON => 'Yuzma-yuz',
            self::TYPE_TECHNICAL => 'Texnik',
            default => $this->interview_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'Rejalashtirilgan',
            self::STATUS_COMPLETED => 'Tugallangan',
            self::STATUS_CANCELLED => 'Bekor qilingan',
            self::STATUS_NO_SHOW => 'Kelmadi',
            default => $this->status,
        };
    }
}
