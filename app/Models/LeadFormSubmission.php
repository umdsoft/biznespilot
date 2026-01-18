<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFormSubmission extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'lead_form_id',
        'lead_id',
        'business_id',
        'form_data',
        'ip_address',
        'user_agent',
        'device_type',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'lead_magnet_delivered',
        'lead_magnet_downloaded_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'lead_magnet_delivered' => 'boolean',
        'lead_magnet_downloaded_at' => 'datetime',
    ];

    // Relationships

    public function leadForm(): BelongsTo
    {
        return $this->belongsTo(LeadForm::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Helper methods

    /**
     * Get value for a specific field from form_data
     */
    public function getFieldValue(string $fieldId): mixed
    {
        return $this->form_data[$fieldId] ?? null;
    }

    /**
     * Check if has UTM tracking
     */
    public function hasUtmTracking(): bool
    {
        return ! empty($this->utm_source) ||
               ! empty($this->utm_medium) ||
               ! empty($this->utm_campaign);
    }

    /**
     * Get UTM parameters as array
     */
    public function getUtmParams(): array
    {
        return array_filter([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
        ]);
    }

    /**
     * Mark lead magnet as delivered
     */
    public function markLeadMagnetDelivered(): void
    {
        $this->update([
            'lead_magnet_delivered' => true,
            'lead_magnet_downloaded_at' => now(),
        ]);
    }

    // Scopes

    public function scopeWithLead($query)
    {
        return $query->whereNotNull('lead_id');
    }

    public function scopeFromSource($query, string $source)
    {
        return $query->where('utm_source', $source);
    }

    public function scopeFromCampaign($query, string $campaign)
    {
        return $query->where('utm_campaign', $campaign);
    }
}
