<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\DreamBuyer;
use App\Models\Offer;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Marketing Automation Service
 *
 * Handles automated campaigns, broadcasts, drip campaigns, and trigger-based messaging
 */
class MarketingAutomationService
{
    protected WhatsAppService $whatsappService;
    protected InstagramDMService $instagramService;
    protected ClaudeAIService $claudeAI;

    public function __construct(
        WhatsAppService $whatsappService,
        InstagramDMService $instagramService,
        ClaudeAIService $claudeAI
    ) {
        $this->whatsappService = $whatsappService;
        $this->instagramService = $instagramService;
        $this->claudeAI = $claudeAI;
    }

    /**
     * Create and schedule a campaign
     */
    public function createCampaign(Business $business, array $data): Campaign
    {
        return Campaign::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'type' => $data['type'], // broadcast, drip, trigger
            'channel' => $data['channel'], // whatsapp, instagram, all
            'message_template' => $data['message_template'],
            'target_audience' => $data['target_audience'] ?? 'all',
            'schedule_type' => $data['schedule_type'] ?? 'immediate',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => 'draft',
            'settings' => $data['settings'] ?? [],
        ]);
    }

    /**
     * Send broadcast message to audience
     */
    public function sendBroadcast(Campaign $campaign): array
    {
        $business = $campaign->business;
        $customers = $this->getTargetAudience($campaign);

        $sentCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($customers as $customer) {
            try {
                $message = $this->personalizeMessage($campaign->message_template, $customer, $business);

                $result = $this->sendToChannel(
                    $campaign->channel,
                    $customer,
                    $message
                );

                if ($result) {
                    $sentCount++;
                    $this->logCampaignMessage($campaign, $customer, 'sent');
                } else {
                    $failedCount++;
                    $this->logCampaignMessage($campaign, $customer, 'failed');
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Campaign broadcast error', [
                    'campaign_id' => $campaign->id,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $campaign->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [
            'success' => true,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'total' => $customers->count(),
        ];
    }

    /**
     * Start drip campaign
     */
    public function startDripCampaign(Campaign $campaign, Customer $customer): void
    {
        $steps = $campaign->settings['steps'] ?? [];

        foreach ($steps as $index => $step) {
            $delay = $step['delay_hours'] ?? 24;
            $sendAt = now()->addHours($delay * $index);

            \App\Models\CampaignMessage::create([
                'campaign_id' => $campaign->id,
                'customer_id' => $customer->id,
                'step_number' => $index + 1,
                'message_content' => $step['message'],
                'scheduled_at' => $sendAt,
                'status' => 'scheduled',
            ]);
        }
    }

    /**
     * Process scheduled campaign messages
     */
    public function processScheduledMessages(): int
    {
        $messages = \App\Models\CampaignMessage::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->with(['campaign', 'customer'])
            ->get();

        $processed = 0;

        foreach ($messages as $message) {
            try {
                $personalizedMessage = $this->personalizeMessage(
                    $message->message_content,
                    $message->customer,
                    $message->campaign->business
                );

                $result = $this->sendToChannel(
                    $message->campaign->channel,
                    $message->customer,
                    $personalizedMessage
                );

                $message->update([
                    'status' => $result ? 'sent' : 'failed',
                    'sent_at' => $result ? now() : null,
                ]);

                $processed++;
            } catch (\Exception $e) {
                $message->update(['status' => 'failed']);
                Log::error('Scheduled message error', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Trigger automated message based on event
     */
    public function triggerAutomatedMessage(string $trigger, Customer $customer, array $context = []): ?array
    {
        $campaigns = Campaign::where('business_id', $customer->business_id)
            ->where('type', 'trigger')
            ->where('status', 'active')
            ->where('settings->trigger_event', $trigger)
            ->get();

        foreach ($campaigns as $campaign) {
            $message = $this->personalizeMessage($campaign->message_template, $customer, $campaign->business, $context);

            return $this->sendToChannel($campaign->channel, $customer, $message);
        }

        return null;
    }

    /**
     * Get target audience for campaign
     */
    protected function getTargetAudience(Campaign $campaign)
    {
        $query = Customer::where('business_id', $campaign->business_id);

        $targetAudience = $campaign->target_audience;

        if ($targetAudience === 'all') {
            return $query->get();
        }

        if ($targetAudience === 'active') {
            return $query->where('status', 'active')->get();
        }

        if ($targetAudience === 'recent') {
            return $query->where('created_at', '>=', now()->subDays(30))->get();
        }

        if (is_array($targetAudience) && isset($targetAudience['tags'])) {
            return $query->whereJsonContains('tags', $targetAudience['tags'])->get();
        }

        return $query->get();
    }

    /**
     * Personalize message with customer and business data
     */
    protected function personalizeMessage(string $template, Customer $customer, Business $business, array $context = []): string
    {
        $replacements = [
            '{customer_name}' => $customer->name,
            '{business_name}' => $business->name,
            '{customer_phone}' => $customer->phone ?? '',
        ];

        // Add dream buyer info
        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('is_primary', true)
            ->first();

        if ($dreamBuyer) {
            $replacements['{dream_buyer_name}'] = $dreamBuyer->name;
        }

        // Add active offers
        $activeOffers = Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->limit(1)
            ->first();

        if ($activeOffers) {
            $replacements['{offer_name}'] = $activeOffers->name;
            $replacements['{offer_price}'] = $activeOffers->price ?? '';
        }

        // Add context data
        foreach ($context as $key => $value) {
            $replacements['{' . $key . '}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Send message to appropriate channel
     */
    protected function sendToChannel(string $channel, Customer $customer, string $message): ?array
    {
        if ($channel === 'whatsapp' || $channel === 'all') {
            if ($customer->phone) {
                return $this->whatsappService->sendTextMessage($customer->phone, $message);
            }
        }

        if ($channel === 'instagram' || $channel === 'all') {
            // Instagram requires user ID, stored in phone field for IG customers
            if ($customer->source === 'instagram' && $customer->phone) {
                return $this->instagramService->sendMessage($customer->phone, $message);
            }
        }

        return null;
    }

    /**
     * Log campaign message
     */
    protected function logCampaignMessage(Campaign $campaign, Customer $customer, string $status): void
    {
        \App\Models\CampaignMessage::create([
            'campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'message_content' => $campaign->message_template,
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    /**
     * Get campaign analytics
     */
    public function getCampaignAnalytics(Campaign $campaign): array
    {
        $messages = \App\Models\CampaignMessage::where('campaign_id', $campaign->id)->get();

        return [
            'total_sent' => $messages->where('status', 'sent')->count(),
            'total_failed' => $messages->where('status', 'failed')->count(),
            'total_scheduled' => $messages->where('status', 'scheduled')->count(),
            'delivery_rate' => $messages->count() > 0
                ? ($messages->where('status', 'sent')->count() / $messages->count()) * 100
                : 0,
        ];
    }

    /**
     * Generate AI campaign message
     */
    public function generateAICampaignMessage(Business $business, string $campaignGoal, array $context = []): string
    {
        $systemPrompt = "Siz marketing campaign xabarlari yaratuvchi AI assistantsiz.";
        $systemPrompt .= "\nBiznes: {$business->name}";
        $systemPrompt .= "\nMaqsad: {$campaignGoal}";

        $userMessage = "Marketing campaign uchun professional, qisqa va ta'sirchan xabar yarating.";
        $userMessage .= "\nXabar o'zbek tilida bo'lishi kerak va quyidagi placeholderlarni ishlatishingiz mumkin:";
        $userMessage .= "\n- {customer_name} - Mijoz ismi";
        $userMessage .= "\n- {business_name} - Biznes nomi";
        $userMessage .= "\n- {offer_name} - Taklif nomi";

        try {
            $response = $this->claudeAI->sendMessage(
                [['role' => 'user', 'content' => $userMessage]],
                $systemPrompt,
                512
            );

            return $response['content'] ?? "Salom {customer_name}! {business_name}dan maxsus taklif!";
        } catch (\Exception $e) {
            return "Salom {customer_name}! {business_name}dan yangi taklif bor!";
        }
    }
}
