<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\Sale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LeadWon - Lead "won" statusga o'tganda va Sale yaratilganda
 * Marketing Attribution tracking uchun
 */
class LeadWon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Lead $lead,
        public Sale $sale,
        public ?string $campaignId = null,
        public ?string $channelId = null,
        public float $revenue = 0
    ) {}

    /**
     * Check if this conversion has attribution.
     */
    public function hasAttribution(): bool
    {
        return $this->campaignId !== null || $this->channelId !== null;
    }

    /**
     * Get attribution summary.
     */
    public function getAttributionSummary(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'sale_id' => $this->sale->id,
            'campaign_id' => $this->campaignId,
            'channel_id' => $this->channelId,
            'revenue' => $this->revenue,
            'utm_source' => $this->lead->utm_source,
            'utm_medium' => $this->lead->utm_medium,
            'utm_campaign' => $this->lead->utm_campaign,
        ];
    }

    /**
     * Get revenue value.
     */
    public function getRevenue(): float
    {
        return $this->revenue;
    }
}
