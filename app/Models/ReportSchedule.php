<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'name',
        'description',
        'frequency',
        'day_of_week',
        'day_of_month',
        'send_time',
        'timezone',
        'report_type',
        'metrics',
        'sections',
        'period',
        'include_trends',
        'include_insights',
        'include_recommendations',
        'include_comparison',
        'delivery_channels',
        'telegram_chat_id',
        'email',
        'format',
        'language',
        'is_active',
        'last_sent_at',
        'next_scheduled_at',
        'send_count',
        'failure_count',
        'last_error',
    ];

    protected $casts = [
        'metrics' => 'array',
        'sections' => 'array',
        'delivery_channels' => 'array',
        'include_trends' => 'boolean',
        'include_insights' => 'boolean',
        'include_recommendations' => 'boolean',
        'include_comparison' => 'boolean',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'next_scheduled_at' => 'datetime',
        'send_time' => 'datetime:H:i:s',
    ];

    // Frequency constants
    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    // Report type constants
    public const TYPE_SUMMARY = 'summary';

    public const TYPE_DETAILED = 'detailed';

    public const TYPE_EXECUTIVE = 'executive';

    public const TYPE_CUSTOM = 'custom';

    // Period constants
    public const PERIOD_DAILY = 'daily';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_QUARTERLY = 'quarterly';

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_scheduled_at', '<=', now());
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    // Helpers
    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            self::FREQUENCY_DAILY => 'Kunlik',
            self::FREQUENCY_WEEKLY => 'Haftalik',
            self::FREQUENCY_MONTHLY => 'Oylik',
            default => $this->frequency,
        };
    }

    public function getReportTypeLabelAttribute(): string
    {
        return match ($this->report_type) {
            self::TYPE_SUMMARY => 'Qisqacha',
            self::TYPE_DETAILED => 'Batafsil',
            self::TYPE_EXECUTIVE => 'Rahbariyat uchun',
            self::TYPE_CUSTOM => 'Maxsus',
            default => $this->report_type,
        };
    }

    /**
     * Calculate next scheduled time based on frequency
     */
    public function calculateNextScheduledAt(): void
    {
        $now = now()->setTimezone($this->timezone);
        $sendTime = \Carbon\Carbon::parse($this->send_time);

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                $next = $now->copy()->setTime($sendTime->hour, $sendTime->minute);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;

            case self::FREQUENCY_WEEKLY:
                $dayOfWeek = $this->day_of_week ?? 'monday';
                $next = $now->copy()->next($dayOfWeek)->setTime($sendTime->hour, $sendTime->minute);
                break;

            case self::FREQUENCY_MONTHLY:
                $dayOfMonth = $this->day_of_month ?? 1;
                $next = $now->copy()->startOfMonth()->addMonths(1)->setDay($dayOfMonth)->setTime($sendTime->hour, $sendTime->minute);
                if ($dayOfMonth > $next->daysInMonth) {
                    $next->setDay($next->daysInMonth);
                }
                break;

            default:
                $next = $now->copy()->addDay();
        }

        $this->next_scheduled_at = $next->setTimezone('UTC');
        $this->save();
    }

    /**
     * Mark as sent successfully
     */
    public function markAsSent(): void
    {
        $this->last_sent_at = now();
        $this->send_count++;
        $this->last_error = null;
        $this->save();

        $this->calculateNextScheduledAt();
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->failure_count++;
        $this->last_error = $error;
        $this->save();

        // If too many failures, deactivate
        if ($this->failure_count >= 5) {
            $this->is_active = false;
            $this->save();
        } else {
            $this->calculateNextScheduledAt();
        }
    }

    /**
     * Get delivery channels as array
     */
    public function getDeliveryChannelsList(): array
    {
        return $this->delivery_channels ?? ['telegram'];
    }

    /**
     * Check if should send via specific channel
     */
    public function shouldSendVia(string $channel): bool
    {
        return in_array($channel, $this->getDeliveryChannelsList());
    }
}
