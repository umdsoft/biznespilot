<?php

namespace App\Services\Telegram;

use App\Models\Lead;
use App\Models\TelegramBusinessConnection;
use App\Models\TelegramConversation;
use App\Models\TelegramUser;
use App\Services\Traits\EnforcesLeadQuota;
use Illuminate\Support\Facades\Log;

/**
 * Single action for creating/updating a Lead from a Telegram Business Chat.
 *
 * Consolidates lead creation logic (previously scattered across 12+ files).
 * Called when the AI detects purchase intent via [LEAD:...] marker.
 */
class CreateLeadFromTelegram
{
    use EnforcesLeadQuota;

    /**
     * Parse [LEAD:name=X;phone=Y;product=Z;intent=HOT;note=...] marker from AI reply.
     *
     * Returns cleaned reply (without marker) and parsed lead data.
     */
    public function parseMarker(string $aiReply): array
    {
        $cleaned = $aiReply;
        $leadData = null;

        if (preg_match('/\[LEAD:([^\]]+)\]/u', $aiReply, $m)) {
            $cleaned = trim(str_replace($m[0], '', $aiReply));
            $leadData = $this->parseParams($m[1]);
        }

        return [
            'reply' => $cleaned,
            'lead_data' => $leadData,
        ];
    }

    /**
     * Detect [HANDOFF] marker (customer wants human operator).
     */
    public function parseHandoff(string $aiReply): array
    {
        $handoff = str_contains($aiReply, '[HANDOFF]');
        $cleaned = trim(str_replace('[HANDOFF]', '', $aiReply));

        return [
            'reply' => $cleaned,
            'handoff' => $handoff,
        ];
    }

    /**
     * Create or update a Lead from parsed marker data.
     */
    public function handle(
        TelegramBusinessConnection $connection,
        TelegramUser $customer,
        TelegramConversation $conversation,
        array $leadData,
    ): ?Lead {
        if (! $connection->auto_create_lead) {
            Log::info('Lead auto-create disabled for connection', [
                'connection_id' => $connection->connection_id,
            ]);

            return null;
        }

        $name = $leadData['name']
            ?? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''))
            ?: 'Telegram Mijoz';

        $phone = $this->normalizePhone($leadData['phone'] ?? '');
        $intent = strtoupper($leadData['intent'] ?? 'WARM');

        // De-duplicate: try to find existing lead by telegram_user_id
        $existing = Lead::where('business_id', $connection->business_id)
            ->whereHas('telegramUser', fn ($q) => $q->where('id', $customer->id))
            ->first();

        $scoreMap = ['HOT' => 80, 'WARM' => 50, 'COLD' => 25];
        $score = $scoreMap[$intent] ?? 50;

        $payload = [
            'business_id' => $connection->business_id,
            'name' => $name,
            'phone' => $phone ?: null,
            'username' => $customer->username,
            'status' => $connection->lead_initial_stage ?: 'new',
            'score' => $score,
            'score_category' => $this->scoreCategory($score),
            'estimated_value' => null,
            'notes' => $leadData['note'] ?? null,
            'chatbot_source_type' => 'telegram_business',
            'chatbot_first_message' => substr($conversation->messages()->first()?->content['text'] ?? '', 0, 500),
            'chatbot_detected_intent' => $intent,
            'chatbot_data' => [
                'connection_id' => $connection->connection_id,
                'product_interest' => $leadData['product'] ?? null,
                'owner_username' => $connection->owner_username,
            ],
        ];

        if ($existing) {
            $existing->update(array_filter($payload, fn ($v) => $v !== null));
            $lead = $existing;
            Log::info('Lead updated from Telegram business chat', ['lead_id' => $lead->id]);
        } else {
            // Quota-gated lead creation (tarif limiti tugagan bo'lsa null qaytaradi)
            $lead = $this->createLeadWithQuotaCheck($payload);
            if ($lead) {
                Log::info('Lead created from Telegram business chat', [
                    'lead_id' => $lead->id,
                    'name' => $name,
                    'phone' => $phone,
                    'intent' => $intent,
                ]);
            } else {
                Log::notice('Lead skipped from Telegram business chat — quota exhausted', [
                    'phone' => $phone,
                    'intent' => $intent,
                ]);
                return null;
            }
        }

        // Link conversation → lead
        if (! $conversation->lead_id) {
            $conversation->update(['lead_id' => $lead->id]);
        }

        // Notify business owner
        $this->notifyOwner($connection, $lead, $leadData);

        return $lead;
    }

    // ==================== Helpers ====================

    protected function parseParams(string $str): array
    {
        $result = [];
        foreach (explode(';', $str) as $pair) {
            if (! str_contains($pair, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $pair, 2));
            if ($k) {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', $phone);
        if (! $digits) {
            return '';
        }

        // Uzbek number: ensure +998 prefix
        if (! str_starts_with($digits, '+')) {
            if (str_starts_with($digits, '998')) {
                $digits = '+'.$digits;
            } elseif (strlen($digits) === 9) {
                $digits = '+998'.$digits;
            }
        }

        return $digits;
    }

    protected function scoreCategory(int $score): string
    {
        return match (true) {
            $score >= 80 => 'hot',
            $score >= 50 => 'warm',
            $score >= 25 => 'cool',
            default => 'cold',
        };
    }

    protected function notifyOwner(TelegramBusinessConnection $connection, Lead $lead, array $leadData): void
    {
        if (! $connection->user_chat_id) {
            return;
        }

        try {
            $intent = strtoupper($leadData['intent'] ?? 'WARM');
            $emoji = ['HOT' => '🔥', 'WARM' => '☀️', 'COLD' => '❄️'][$intent] ?? '📩';

            $msg = "{$emoji} *Yangi lid!*\n\n"
                ."👤 {$lead->name}\n"
                .($lead->phone ? "📞 {$lead->phone}\n" : '')
                .(! empty($leadData['product']) ? "🛍 {$leadData['product']}\n" : '')
                ."⚡ Intent: *{$intent}* | Score: {$lead->score}\n"
                .(! empty($leadData['note']) ? "\n📝 {$leadData['note']}\n" : '')
                ."\n→ biznespilot.uz/business/sales";

            $api = new TelegramApiService($connection->telegramBot);
            $api->sendMessage($connection->user_chat_id, $msg, ['parse_mode' => 'Markdown']);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify owner about new lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
