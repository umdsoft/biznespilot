<?php

namespace App\Services\Telephony;

use App\Contracts\TelephonyProviderInterface;
use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Abstract base class for telephony providers
 * Provides common functionality shared across all providers
 */
abstract class AbstractTelephonyProvider implements TelephonyProviderInterface
{
    /**
     * Get webhook URL for this provider
     */
    public function getWebhookUrl(?string $businessId = null): string
    {
        $baseUrl = config('app.url');
        $providerName = $this->getName();

        if ($businessId) {
            return "{$baseUrl}/api/webhooks/{$providerName}/{$businessId}";
        }

        return "{$baseUrl}/api/webhooks/{$providerName}";
    }

    /**
     * Parse call direction from various formats
     */
    protected function parseDirection(string $direction): string
    {
        $direction = strtolower($direction);

        if (in_array($direction, ['in', 'incoming', 'inbound', 'internal_in', 'from-external'])) {
            return CallLog::DIRECTION_INBOUND;
        }

        return CallLog::DIRECTION_OUTBOUND;
    }

    /**
     * Parse call status from provider-specific format
     */
    protected function parseCallStatus(string $status, int $duration = 0): string
    {
        $status = strtolower($status);

        // Answered/Completed statuses
        if (in_array($status, ['answered', 'answer', 'completed', 'success', 'normal_clearing'])) {
            return $duration > 0 ? CallLog::STATUS_COMPLETED : CallLog::STATUS_NO_ANSWER;
        }

        // No answer statuses
        if (in_array($status, ['no_answer', 'noanswer', 'missed', 'unanswered', 'cancel', 'no_user_response'])) {
            return CallLog::STATUS_NO_ANSWER;
        }

        // Busy statuses
        if (in_array($status, ['busy', 'user_busy'])) {
            return CallLog::STATUS_BUSY;
        }

        // Failed statuses
        if (in_array($status, ['failed', 'error', 'congestion', 'rejected', 'call_rejected'])) {
            return CallLog::STATUS_FAILED;
        }

        // Cancelled
        if (in_array($status, ['cancelled', 'originator_cancel'])) {
            return CallLog::STATUS_CANCELLED;
        }

        // Default based on duration
        return $duration > 0 ? CallLog::STATUS_COMPLETED : CallLog::STATUS_NO_ANSWER;
    }

    /**
     * Normalize phone number to standard format
     */
    public function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading + if present (already handled by regex above)
        $phone = ltrim($phone, '0');

        // Handle Uzbekistan numbers (9 digits -> add 998)
        if (strlen($phone) === 9 && preg_match('/^[0-9]/', $phone)) {
            $phone = '998' . $phone;
        }

        // Handle Russian numbers (11 digits starting with 8 -> change to 7)
        if (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            $phone = '7' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Format phone number for display
     */
    protected function formatPhoneDisplay(string $phone): string
    {
        $normalized = $this->normalizePhoneNumber($phone);

        // Format Uzbekistan numbers: +998 (90) 123-45-67
        if (strlen($normalized) === 12 && str_starts_with($normalized, '998')) {
            return '+' . substr($normalized, 0, 3) . ' (' . substr($normalized, 3, 2) . ') '
                . substr($normalized, 5, 3) . '-' . substr($normalized, 8, 2) . '-' . substr($normalized, 10, 2);
        }

        return '+' . $normalized;
    }

    /**
     * Find or create lead for a phone call
     */
    protected function findOrCreateLeadForCall(
        string $businessId,
        string $phoneNumber,
        string $direction,
        array $metadata = []
    ): ?Lead {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Try to find existing lead by phone number
        $lead = Lead::where('business_id', $businessId)
            ->where(function ($query) use ($normalizedPhone, $phoneNumber) {
                $last9 = substr($normalizedPhone, -9);
                $query->where('phone', 'like', '%' . $last9)
                    ->orWhere('phone', $normalizedPhone)
                    ->orWhere('phone', $phoneNumber)
                    ->orWhere('phone', '+' . $normalizedPhone);
            })
            ->first();

        if ($lead) {
            // Update last contacted
            $lead->update(['last_contacted_at' => now()]);

            // Update call statistics in lead data
            $leadData = $lead->data ?? [];
            $leadData['call_count'] = ($leadData['call_count'] ?? 0) + 1;
            $leadData['last_call_at'] = now()->toISOString();
            $leadData['last_call_provider'] = $this->getName();

            $lead->update(['data' => $leadData]);

            return $lead;
        }

        // Only create new leads for incoming calls
        if ($direction !== CallLog::DIRECTION_INBOUND) {
            return null;
        }

        // Get or create phone lead source
        $source = $this->getOrCreatePhoneSource($businessId);

        // Create new lead
        $providerName = $this->getDisplayName();
        $newLead = Lead::create([
            'id' => Str::uuid(),
            'business_id' => $businessId,
            'lead_source_id' => $source?->id,
            'name' => "Kiruvchi qo'ng'iroq ({$providerName})",
            'phone' => '+' . $normalizedPhone,
            'status' => 'new',
            'last_contacted_at' => now(),
            'metadata' => array_merge($metadata, [
                'created_from' => $this->getName() . '_incoming_call',
                'original_phone' => $phoneNumber,
            ]),
        ]);

        Log::info("Created lead from {$this->getName()} incoming call", [
            'lead_id' => $newLead->id,
            'phone' => $normalizedPhone,
        ]);

        return $newLead;
    }

    /**
     * Get or create phone call lead source
     */
    protected function getOrCreatePhoneSource(string $businessId): ?LeadSource
    {
        // Try to find by code
        $source = LeadSource::where('business_id', $businessId)
            ->where('code', 'phone_call')
            ->first();

        if ($source) {
            return $source;
        }

        // Try by name
        $source = LeadSource::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('name', 'like', '%telefon%')
                    ->orWhere('name', 'like', '%phone%')
                    ->orWhere('name', 'like', '%qo\'ng\'iroq%');
            })
            ->first();

        if ($source) {
            return $source;
        }

        // Try by type offline
        $source = LeadSource::where('business_id', $businessId)
            ->where('type', 'offline')
            ->first();

        if ($source) {
            return $source;
        }

        // Create new source
        try {
            return LeadSource::create([
                'business_id' => $businessId,
                'code' => 'phone_call_' . substr($businessId, 0, 8),
                'name' => 'Telefon qo\'ng\'iroq',
                'category' => 'offline',
                'icon' => 'phone',
                'color' => '#10B981',
                'is_paid' => false,
                'is_trackable' => true,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning('LeadSource creation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Record call statistics
     */
    protected function recordCallStatistics(string $businessId, CallLog $callLog): void
    {
        try {
            CallDailyStat::recordCall($businessId, $callLog);
        } catch (\Exception $e) {
            Log::error('Failed to record call statistics', [
                'business_id' => $businessId,
                'call_id' => $callLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if call already exists (prevent duplicates)
     */
    protected function callExists(string $providerCallId): bool
    {
        return CallLog::where('provider_call_id', $providerCallId)
            ->where('provider', $this->getName())
            ->exists();
    }

    /**
     * Find existing call log by provider call ID
     */
    protected function findCallLog(string $providerCallId): ?CallLog
    {
        return CallLog::where('provider_call_id', $providerCallId)
            ->where('provider', $this->getName())
            ->first();
    }

    /**
     * Get recording content - default implementation
     */
    public function getRecordingContent(string $callId): array
    {
        $url = $this->getRecordingUrl($callId);

        if (!$url) {
            return ['success' => false, 'error' => 'Recording URL not found'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'content' => $response->body(),
                    'mime_type' => $response->header('Content-Type') ?? 'audio/mpeg',
                ];
            }

            return ['success' => false, 'error' => 'Failed to fetch recording'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
