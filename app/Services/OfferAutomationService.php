<?php

namespace App\Services;

use App\Jobs\SendOfferToLead;
use App\Models\Business;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\OfferLeadAssignment;
use App\Models\OfferMetric;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfferAutomationService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Assign offer to a single lead
     */
    public function assignOfferToLead(
        Offer $offer,
        Lead $lead,
        string $channel = 'manual',
        ?User $assignedBy = null,
        array $options = []
    ): OfferLeadAssignment {
        // Check if offer is already assigned to this lead
        $existingAssignment = OfferLeadAssignment::where('offer_id', $offer->id)
            ->where('lead_id', $lead->id)
            ->whereNotIn('status', [
                OfferLeadAssignment::STATUS_REJECTED,
                OfferLeadAssignment::STATUS_EXPIRED,
                OfferLeadAssignment::STATUS_CONVERTED,
            ])
            ->first();

        if ($existingAssignment) {
            return $existingAssignment;
        }

        $assignment = OfferLeadAssignment::create([
            'offer_id' => $offer->id,
            'lead_id' => $lead->id,
            'business_id' => $offer->business_id,
            'assigned_by' => $assignedBy?->id,
            'telegram_user_id' => $lead->telegramUser?->id,
            'channel' => $channel,
            'status' => OfferLeadAssignment::STATUS_PENDING,
            'offered_price' => $options['custom_price'] ?? $offer->pricing,
            'discount_amount' => $options['discount'] ?? null,
            'discount_code' => $options['discount_code'] ?? null,
            'scheduled_at' => $options['scheduled_at'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'utm_data' => $options['utm_data'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);

        return $assignment;
    }

    /**
     * Assign offer to multiple leads
     */
    public function assignOfferToLeads(
        Offer $offer,
        Collection $leads,
        string $channel = 'manual',
        ?User $assignedBy = null,
        array $options = []
    ): Collection {
        $assignments = collect();

        foreach ($leads as $lead) {
            try {
                $assignment = $this->assignOfferToLead($offer, $lead, $channel, $assignedBy, $options);
                $assignments->push($assignment);
            } catch (\Exception $e) {
                Log::error('Failed to assign offer to lead', [
                    'offer_id' => $offer->id,
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $assignments;
    }

    /**
     * Send offer to lead via specified channel
     */
    public function sendOffer(OfferLeadAssignment $assignment, bool $queue = true): bool
    {
        if ($queue) {
            SendOfferToLead::dispatch($assignment);
            return true;
        }

        return $this->sendOfferNow($assignment);
    }

    /**
     * Send offer immediately
     */
    public function sendOfferNow(OfferLeadAssignment $assignment): bool
    {
        try {
            $success = match ($assignment->channel) {
                OfferLeadAssignment::CHANNEL_TELEGRAM => $this->sendViaTelegram($assignment),
                OfferLeadAssignment::CHANNEL_SMS => $this->sendViaSms($assignment),
                OfferLeadAssignment::CHANNEL_EMAIL => $this->sendViaEmail($assignment),
                OfferLeadAssignment::CHANNEL_WHATSAPP => $this->sendViaWhatsApp($assignment),
                default => $this->markAsSentManually($assignment),
            };

            if ($success) {
                $assignment->markAsSent();
                $this->updateMetrics($assignment, 'send');
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to send offer', [
                'assignment_id' => $assignment->id,
                'channel' => $assignment->channel,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send offer via Telegram
     */
    protected function sendViaTelegram(OfferLeadAssignment $assignment): bool
    {
        $telegramUser = $assignment->telegramUser ?? $assignment->lead->telegramUser;

        if (!$telegramUser || !$telegramUser->telegram_id) {
            Log::warning('Lead has no Telegram user', ['lead_id' => $assignment->lead_id]);
            return false;
        }

        $message = $this->formatOfferMessage($assignment);
        $keyboard = $this->buildOfferKeyboard($assignment);

        try {
            $this->telegramService->sendMessage(
                $telegramUser->telegramBot->bot_token,
                $telegramUser->telegram_id,
                $message,
                [
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboard,
                    ]),
                ]
            );

            $assignment->update(['telegram_user_id' => $telegramUser->id]);
            $assignment->markAsDelivered();

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram send failed', [
                'assignment_id' => $assignment->id,
                'telegram_user_id' => $telegramUser->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Format offer message for Telegram
     */
    protected function formatOfferMessage(OfferLeadAssignment $assignment): string
    {
        $offer = $assignment->offer;
        $lead = $assignment->lead;

        $greeting = $lead->name ? "Salom, {$lead->name}! 👋" : "Salom! 👋";

        $message = "{$greeting}\n\n";
        $message .= "🎁 <b>{$offer->name}</b>\n\n";

        if ($offer->description) {
            $message .= "{$offer->description}\n\n";
        }

        if ($offer->value_proposition) {
            $message .= "💡 <b>Siz uchun:</b>\n{$offer->value_proposition}\n\n";
        }

        // Bonus stack
        if ($offer->components->count() > 0) {
            $message .= "🎯 <b>Bonuslar:</b>\n";
            foreach ($offer->components as $component) {
                $value = $component->value ? " (" . number_format($component->value, 0, '', ' ') . " so'm)" : '';
                $message .= "✅ {$component->name}{$value}\n";
            }
            $message .= "\n";
        }

        // Pricing
        $price = $assignment->offered_price ?? $offer->pricing;
        if ($price) {
            $formattedPrice = number_format($price, 0, '', ' ');

            if ($assignment->discount_amount && $assignment->discount_amount > 0) {
                $originalPrice = number_format($price + $assignment->discount_amount, 0, '', ' ');
                $message .= "💰 <s>{$originalPrice} so'm</s> → <b>{$formattedPrice} so'm</b>\n";
                $message .= "🔥 Chegirma: " . number_format($assignment->discount_amount, 0, '', ' ') . " so'm\n\n";
            } else {
                $message .= "💰 <b>Narx: {$formattedPrice} so'm</b>\n\n";
            }
        }

        // Guarantee
        if ($offer->guarantee_type) {
            $guaranteeLabels = [
                'unconditional' => '100% pul qaytarish kafolati',
                'conditional' => 'Shartli kafolat',
                'performance' => 'Natija kafolati',
            ];
            $guaranteeLabel = $guaranteeLabels[$offer->guarantee_type] ?? $offer->guarantee_type;
            $message .= "🛡 <b>Kafolat:</b> {$guaranteeLabel}";

            if ($offer->guarantee_period_days) {
                $message .= " ({$offer->guarantee_period_days} kun)";
            }
            $message .= "\n\n";
        }

        // Scarcity/Urgency
        if ($offer->scarcity) {
            $message .= "⏰ <b>Cheklov:</b> {$offer->scarcity}\n";
        }

        if ($offer->urgency) {
            $message .= "🔥 <b>Shoshiling:</b> {$offer->urgency}\n";
        }

        if ($assignment->expires_at) {
            $expiresIn = now()->diffForHumans($assignment->expires_at, ['parts' => 2]);
            $message .= "\n⚠️ <i>Taklif muddati: {$expiresIn}</i>";
        }

        return $message;
    }

    /**
     * Build inline keyboard for offer
     */
    protected function buildOfferKeyboard(OfferLeadAssignment $assignment): array
    {
        $keyboard = [];

        // Main CTA button
        $keyboard[] = [
            [
                'text' => '✅ Batafsil ko\'rish',
                'url' => $assignment->getPublicUrl(),
            ],
        ];

        // Action buttons
        $keyboard[] = [
            [
                'text' => '💬 Savol berish',
                'callback_data' => "offer_question:{$assignment->tracking_code}",
            ],
            [
                'text' => '📞 Qo\'ng\'iroq qilish',
                'callback_data' => "offer_call:{$assignment->tracking_code}",
            ],
        ];

        return $keyboard;
    }

    /**
     * Send via SMS (placeholder)
     */
    protected function sendViaSms(OfferLeadAssignment $assignment): bool
    {
        // TODO: Implement SMS sending
        Log::info('SMS sending not implemented yet', ['assignment_id' => $assignment->id]);
        return false;
    }

    /**
     * Send via Email (placeholder)
     */
    protected function sendViaEmail(OfferLeadAssignment $assignment): bool
    {
        // TODO: Implement Email sending
        Log::info('Email sending not implemented yet', ['assignment_id' => $assignment->id]);
        return false;
    }

    /**
     * Send via WhatsApp (placeholder)
     */
    protected function sendViaWhatsApp(OfferLeadAssignment $assignment): bool
    {
        // TODO: Implement WhatsApp sending
        Log::info('WhatsApp sending not implemented yet', ['assignment_id' => $assignment->id]);
        return false;
    }

    /**
     * Mark as sent manually
     */
    protected function markAsSentManually(OfferLeadAssignment $assignment): bool
    {
        return true;
    }

    /**
     * Record offer view
     */
    public function recordView(OfferLeadAssignment $assignment, array $metadata = []): void
    {
        $isFirstView = !$assignment->first_viewed_at;

        $assignment->markAsViewed();

        if ($metadata) {
            $assignment->update([
                'metadata' => array_merge($assignment->metadata ?? [], [
                    'view_data' => $metadata,
                    'last_view_ip' => $metadata['ip'] ?? null,
                    'last_view_user_agent' => $metadata['user_agent'] ?? null,
                ]),
            ]);
        }

        $this->updateMetrics($assignment, 'view', $isFirstView);
    }

    /**
     * Record offer click
     */
    public function recordClick(OfferLeadAssignment $assignment, string $action = 'cta', array $metadata = []): void
    {
        $isFirstClick = !$assignment->clicked_at;

        $assignment->markAsClicked();

        // Log click action
        $clicks = $assignment->metadata['clicks'] ?? [];
        $clicks[] = [
            'action' => $action,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata,
        ];

        $assignment->update([
            'metadata' => array_merge($assignment->metadata ?? [], ['clicks' => $clicks]),
        ]);

        $this->updateMetrics($assignment, 'click', $isFirstClick);
    }

    /**
     * Record conversion
     */
    public function recordConversion(OfferLeadAssignment $assignment, ?float $finalPrice = null, array $metadata = []): void
    {
        $assignment->markAsConverted($finalPrice);

        if ($metadata) {
            $assignment->update([
                'metadata' => array_merge($assignment->metadata ?? [], [
                    'conversion_data' => $metadata,
                ]),
            ]);
        }

        $this->updateMetrics($assignment, 'conversion');

        // Update offer conversion rate
        $this->updateOfferConversionRate($assignment->offer);
    }

    /**
     * Record rejection
     */
    public function recordRejection(OfferLeadAssignment $assignment, ?string $reason = null): void
    {
        $assignment->markAsRejected($reason);
        $this->updateMetrics($assignment, 'rejection');
    }

    /**
     * Update metrics for offer
     */
    protected function updateMetrics(OfferLeadAssignment $assignment, string $type, bool $isUnique = true): void
    {
        $metric = OfferMetric::getOrCreate($assignment->offer_id, $assignment->business_id);

        switch ($type) {
            case 'send':
                $metric->incrementMetric('sends_count');
                break;

            case 'delivery':
                $metric->incrementMetric('deliveries_count');
                break;

            case 'view':
                $metric->incrementMetric('views_count');
                if ($isUnique) {
                    $metric->incrementMetric('unique_views_count');
                }
                break;

            case 'click':
                $metric->incrementMetric('clicks_count');
                if ($isUnique) {
                    $metric->incrementMetric('unique_clicks_count');
                }
                break;

            case 'conversion':
                $metric->addRevenue($assignment->final_price ?? $assignment->offered_price ?? 0);
                break;

            case 'rejection':
                $metric->incrementMetric('rejections_count');
                break;
        }
    }

    /**
     * Update offer conversion rate
     */
    protected function updateOfferConversionRate(Offer $offer): void
    {
        $stats = OfferLeadAssignment::where('offer_id', $offer->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as conversions
            ', [OfferLeadAssignment::STATUS_CONVERTED])
            ->first();

        $conversionRate = $stats->total > 0
            ? round(($stats->conversions / $stats->total) * 100, 2)
            : 0;

        $offer->update(['conversion_rate' => $conversionRate]);
    }

    /**
     * Get offer analytics
     */
    public function getOfferAnalytics(Offer $offer, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = OfferLeadAssignment::where('offer_id', $offer->id);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_assignments,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as viewed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as clicked,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as interested,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as converted,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as expired,
            SUM(view_count) as total_views,
            SUM(click_count) as total_clicks,
            SUM(COALESCE(final_price, 0)) as total_revenue,
            AVG(CASE WHEN status = ? THEN TIMESTAMPDIFF(MINUTE, sent_at, converted_at) ELSE NULL END) as avg_conversion_time
        ', [
            OfferLeadAssignment::STATUS_PENDING,
            OfferLeadAssignment::STATUS_SENT,
            OfferLeadAssignment::STATUS_DELIVERED,
            OfferLeadAssignment::STATUS_VIEWED,
            OfferLeadAssignment::STATUS_CLICKED,
            OfferLeadAssignment::STATUS_INTERESTED,
            OfferLeadAssignment::STATUS_CONVERTED,
            OfferLeadAssignment::STATUS_REJECTED,
            OfferLeadAssignment::STATUS_EXPIRED,
            OfferLeadAssignment::STATUS_CONVERTED,
        ])->first();

        $sent = $stats->sent + $stats->delivered + $stats->viewed + $stats->clicked +
            $stats->interested + $stats->converted;

        return [
            'total_assignments' => (int) $stats->total_assignments,
            'status_breakdown' => [
                'pending' => (int) $stats->pending,
                'sent' => (int) $stats->sent,
                'delivered' => (int) $stats->delivered,
                'viewed' => (int) $stats->viewed,
                'clicked' => (int) $stats->clicked,
                'interested' => (int) $stats->interested,
                'converted' => (int) $stats->converted,
                'rejected' => (int) $stats->rejected,
                'expired' => (int) $stats->expired,
            ],
            'metrics' => [
                'total_views' => (int) $stats->total_views,
                'total_clicks' => (int) $stats->total_clicks,
                'total_revenue' => (float) $stats->total_revenue,
                'avg_conversion_time_minutes' => round($stats->avg_conversion_time ?? 0),
            ],
            'rates' => [
                'delivery_rate' => $sent > 0 ? round(($stats->delivered / $sent) * 100, 2) : 0,
                'view_rate' => $sent > 0 ? round((($stats->viewed + $stats->clicked + $stats->converted) / $sent) * 100, 2) : 0,
                'click_rate' => $sent > 0 ? round((($stats->clicked + $stats->converted) / $sent) * 100, 2) : 0,
                'conversion_rate' => $sent > 0 ? round(($stats->converted / $sent) * 100, 2) : 0,
                'rejection_rate' => $stats->total_assignments > 0 ? round(($stats->rejected / $stats->total_assignments) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Get best performing offers for a business
     */
    public function getBestPerformingOffers(Business $business, int $limit = 5): Collection
    {
        return Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->withCount([
                'leadAssignments as conversions_count' => function ($query) {
                    $query->where('status', OfferLeadAssignment::STATUS_CONVERTED);
                },
                'leadAssignments as total_sent' => function ($query) {
                    $query->whereNotIn('status', [OfferLeadAssignment::STATUS_PENDING]);
                },
            ])
            ->orderByDesc('conversion_rate')
            ->limit($limit)
            ->get();
    }

    /**
     * Find best offer for a lead based on criteria
     */
    public function findBestOfferForLead(Lead $lead): ?Offer
    {
        // Get active offers for the business
        $offers = Offer::where('business_id', $lead->business_id)
            ->where('status', 'active')
            ->get();

        if ($offers->isEmpty()) {
            return null;
        }

        // Simple scoring based on conversion rate and value score
        $scoredOffers = $offers->map(function ($offer) use ($lead) {
            $score = 0;

            // Base score from conversion rate
            $score += $offer->conversion_rate * 2;

            // Value score contribution
            $score += $offer->value_score * 10;

            // Check if lead already received this offer
            $hasReceived = OfferLeadAssignment::where('offer_id', $offer->id)
                ->where('lead_id', $lead->id)
                ->exists();

            if ($hasReceived) {
                $score -= 50; // Penalize if already sent
            }

            return [
                'offer' => $offer,
                'score' => $score,
            ];
        });

        $best = $scoredOffers->sortByDesc('score')->first();

        return $best && $best['score'] > 0 ? $best['offer'] : null;
    }

    /**
     * Process scheduled offers
     */
    public function processScheduledOffers(): int
    {
        $assignments = OfferLeadAssignment::scheduledForNow()->get();

        $sent = 0;
        foreach ($assignments as $assignment) {
            if ($this->sendOffer($assignment, false)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Expire old offers
     */
    public function expireOldOffers(): int
    {
        return OfferLeadAssignment::where('status', '!=', OfferLeadAssignment::STATUS_EXPIRED)
            ->where('status', '!=', OfferLeadAssignment::STATUS_CONVERTED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => OfferLeadAssignment::STATUS_EXPIRED,
            ]);
    }
}
