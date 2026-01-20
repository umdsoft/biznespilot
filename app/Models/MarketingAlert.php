<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAlert extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'type',
        'severity',
        'title',
        'message',
        'channel_id',
        'campaign_id',
        'user_id',
        'data',
        'comparison',
        'threshold_value',
        'actual_value',
        'deviation_percent',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'data' => 'array',
        'comparison' => 'array',
        'threshold_value' => 'decimal:4',
        'actual_value' => 'decimal:4',
        'deviation_percent' => 'decimal:2',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Alert types
    public const TYPE_CPL_HIGH = 'cpl_high';
    public const TYPE_ROAS_LOW = 'roas_low';
    public const TYPE_BUDGET_EXCEEDED = 'budget_exceeded';
    public const TYPE_BUDGET_WARNING = 'budget_warning';
    public const TYPE_LEAD_DROP = 'lead_drop';
    public const TYPE_CONVERSION_DROP = 'conversion_drop';
    public const TYPE_ROI_NEGATIVE = 'roi_negative';
    public const TYPE_TARGET_AT_RISK = 'target_at_risk';
    public const TYPE_CAMPAIGN_UNDERPERFORM = 'campaign_underperform';
    public const TYPE_CHANNEL_ANOMALY = 'channel_anomaly';
    public const TYPE_SPEND_ANOMALY = 'spend_anomaly';

    public const TYPES = [
        self::TYPE_CPL_HIGH => 'CPL yuqori',
        self::TYPE_ROAS_LOW => 'ROAS past',
        self::TYPE_BUDGET_EXCEEDED => 'Byudjet tugadi',
        self::TYPE_BUDGET_WARNING => 'Byudjet ogohlantirishi',
        self::TYPE_LEAD_DROP => 'Lead tushishi',
        self::TYPE_CONVERSION_DROP => 'Conversion tushishi',
        self::TYPE_ROI_NEGATIVE => 'ROI manfiy',
        self::TYPE_TARGET_AT_RISK => 'Target xavf ostida',
        self::TYPE_CAMPAIGN_UNDERPERFORM => 'Kampaniya samarasiz',
        self::TYPE_CHANNEL_ANOMALY => 'Kanal anomaliyasi',
        self::TYPE_SPEND_ANOMALY => 'Xarajat anomaliyasi',
    ];

    // Severity levels
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    // RELATIONSHIPS

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // SCOPES

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', 'critical');
    }

    public function scopeWarning(Builder $query): Builder
    {
        return $query->where('severity', 'warning');
    }

    public function scopeForChannel(Builder $query, $channelId): Builder
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeForCampaign(Builder $query, $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // HELPERS

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function acknowledge(?string $userId = null): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId ?? auth()->id(),
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(?string $userId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $userId ?? auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function dismiss(): void
    {
        $this->update(['status' => 'dismissed']);
    }

    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'gray',
        };
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
