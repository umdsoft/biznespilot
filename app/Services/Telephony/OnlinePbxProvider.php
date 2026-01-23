<?php

namespace App\Services\Telephony;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\PbxAccount;
use App\Services\OnlinePbxService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OnlinePBX Provider - Wrapper around existing OnlinePbxService
 * Implements TelephonyProviderInterface for unified telephony operations
 */
class OnlinePbxProvider extends AbstractTelephonyProvider
{
    protected ?PbxAccount $account = null;

    protected OnlinePbxService $service;

    public function __construct(OnlinePbxService $service)
    {
        $this->service = $service;
    }

    /**
     * Set the PBX account
     */
    public function setAccount(PbxAccount $account): self
    {
        $this->account = $account;
        $this->service->setAccount($account);

        return $this;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'onlinepbx';
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return 'OnlinePBX';
    }

    /**
     * Check if configured
     */
    public function isConfigured(): bool
    {
        return $this->account && $this->account->isConfigured();
    }

    /**
     * Authenticate with OnlinePBX API
     */
    public function authenticate(): array
    {
        return $this->service->authenticate();
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(array $credentials): array
    {
        $apiUrl = $credentials['api_url'] ?? null;
        $apiKey = $credentials['api_key'] ?? null;
        $keyId = $credentials['key_id'] ?? null;

        if (!$apiUrl || !$apiKey) {
            return ['success' => false, 'error' => 'API URL va API key talab qilinadi'];
        }

        return $this->service->testConnection($apiUrl, $apiKey, $keyId);
    }

    /**
     * Parse webhook data
     */
    public function parseWebhook(Request $request): array
    {
        $data = $request->all();

        $event = $data['event'] ?? $data['type'] ?? $data['state'] ?? 'unknown';
        $callId = $data['call_id'] ?? $data['uuid'] ?? $data['id'] ?? null;

        // Get phone numbers
        $callerNumber = $data['caller_id'] ?? $data['caller'] ?? $data['from'] ?? $data['src'] ?? null;
        $calledNumber = $data['called_id'] ?? $data['callee'] ?? $data['to'] ?? $data['dst'] ?? null;

        // Detect direction
        $direction = $this->detectCallDirection($callerNumber, $calledNumber, $data);

        // Determine customer phone number based on direction
        if ($direction === CallLog::DIRECTION_INBOUND) {
            $fromNumber = $this->normalizePhoneNumber($callerNumber ?? '');
            $toNumber = $calledNumber ?? '';
        } else {
            $fromNumber = $callerNumber ?? '';
            $toNumber = $this->normalizePhoneNumber($calledNumber ?? '');
        }

        $duration = (int)($data['duration'] ?? $data['billsec'] ?? $data['dialog_duration'] ?? 0);
        $status = $this->determineCallStatus($data);

        return [
            'event' => $event,
            'call_id' => $callId,
            'direction' => $direction,
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
            'status' => $status,
            'duration' => $duration,
            'recording_url' => $data['recording_url'] ?? $data['record'] ?? $data['download_url'] ?? null,
            'metadata' => $data,
        ];
    }

    /**
     * Detect call direction from phone numbers
     */
    protected function detectCallDirection(?string $callerNumber, ?string $calledNumber, array $data): string
    {
        // Check explicit direction in data
        $direction = strtolower($data['direction'] ?? '');
        if (in_array($direction, ['inbound', 'in', 'incoming', 'from-external'])) {
            return CallLog::DIRECTION_INBOUND;
        }
        if (in_array($direction, ['outbound', 'out', 'outgoing', 'to-external'])) {
            return CallLog::DIRECTION_OUTBOUND;
        }

        // If caller is short (internal extension), it's outbound
        if ($callerNumber && strlen(preg_replace('/[^0-9]/', '', $callerNumber)) < 7) {
            return CallLog::DIRECTION_OUTBOUND;
        }

        // If callee is short (internal extension), it's inbound
        if ($calledNumber && strlen(preg_replace('/[^0-9]/', '', $calledNumber)) < 7) {
            return CallLog::DIRECTION_INBOUND;
        }

        // Check event type for hints
        $event = strtolower($data['event'] ?? $data['type'] ?? '');
        if (str_contains($event, 'incoming') || str_contains($event, 'inbound')) {
            return CallLog::DIRECTION_INBOUND;
        }

        // Default to inbound if caller is external number
        if ($callerNumber && strlen(preg_replace('/[^0-9]/', '', $callerNumber)) >= 7) {
            return CallLog::DIRECTION_INBOUND;
        }

        return CallLog::DIRECTION_OUTBOUND;
    }

    /**
     * Determine call status from webhook data
     */
    protected function determineCallStatus(array $data): string
    {
        // Check event type first
        $event = strtolower($data['event'] ?? $data['type'] ?? $data['state'] ?? '');

        if (in_array($event, ['call_missed', 'missed', 'call_unanswered'])) {
            return CallLog::STATUS_NO_ANSWER;
        }
        if (in_array($event, ['call_answered', 'answered', 'call_completed', 'completed'])) {
            return CallLog::STATUS_COMPLETED;
        }
        if (in_array($event, ['call_busy', 'busy'])) {
            return CallLog::STATUS_BUSY;
        }
        if (in_array($event, ['call_failed', 'failed'])) {
            return CallLog::STATUS_FAILED;
        }
        if (in_array($event, ['no_answer', 'noanswer'])) {
            return CallLog::STATUS_NO_ANSWER;
        }

        // Check hangup_cause
        $hangupCause = strtoupper($data['hangup_cause'] ?? $data['cause'] ?? '');

        if (in_array($hangupCause, ['NORMAL_CLEARING', 'SUCCESS'])) {
            return CallLog::STATUS_COMPLETED;
        }
        if (in_array($hangupCause, ['NO_ANSWER', 'NO_USER_RESPONSE'])) {
            return CallLog::STATUS_NO_ANSWER;
        }
        if (in_array($hangupCause, ['USER_BUSY', 'BUSY'])) {
            return CallLog::STATUS_BUSY;
        }
        if (in_array($hangupCause, ['ORIGINATOR_CANCEL', 'CALL_REJECTED'])) {
            return CallLog::STATUS_CANCELLED;
        }

        // Check duration
        $dialogDuration = (int)($data['dialog_duration'] ?? $data['billsec'] ?? 0);
        if ($dialogDuration > 0) {
            return CallLog::STATUS_COMPLETED;
        }

        $callDuration = (int)($data['call_duration'] ?? $data['duration'] ?? 0);
        if ($callDuration > 0 && $dialogDuration === 0) {
            return CallLog::STATUS_NO_ANSWER;
        }

        return CallLog::STATUS_INITIATED;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(Request $request, ?string $secret = null): bool
    {
        $secret = $secret ?? $this->account?->getSetting('webhook_secret');

        if (!$secret) {
            return true; // No secret configured
        }

        $signature = $request->header('X-Webhook-Signature') ?? $request->header('X-OnlinePBX-Signature');

        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle incoming webhook
     */
    public function handleWebhook(array $data): void
    {
        $this->service->handleWebhook($data);
    }

    /**
     * Make an outbound call
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array
    {
        return $this->service->makeCall($toNumber, $lead, $fromNumber);
    }

    /**
     * Hangup call
     */
    public function hangupCall(string $callId): array
    {
        return $this->service->hangupCall($callId);
    }

    /**
     * Get call status
     */
    public function getCallStatus(string $callId): array
    {
        return $this->service->getCallStatus($callId);
    }

    /**
     * Get call history
     */
    public function getCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        return $this->service->getCallHistory($dateFrom, $dateTo);
    }

    /**
     * Sync call history
     */
    public function syncCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        return $this->service->syncCallHistory($dateFrom);
    }

    /**
     * Get account balance
     */
    public function getBalance(): array
    {
        return $this->service->getBalance();
    }

    /**
     * Get recording URL
     */
    public function getRecordingUrl(string $callId): ?string
    {
        return $this->service->getRecordingUrl($callId);
    }

    /**
     * Get users/extensions
     */
    public function getUsers(): array
    {
        return $this->service->getUsers();
    }

    /**
     * Configure webhook - OnlinePBX requires manual setup
     */
    public function configureWebhook(string $webhookUrl): array
    {
        return [
            'success' => true,
            'message' => 'OnlinePBX webhook manual sozlanishi kerak. URL: ' . $webhookUrl,
            'webhook_url' => $webhookUrl,
        ];
    }

    /**
     * Get statistics
     */
    public function getStatistics(string $businessId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $stats = CallDailyStat::where('business_id', $businessId)
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(total_calls), 0) as total_calls,
                COALESCE(SUM(outbound_calls), 0) as outbound_calls,
                COALESCE(SUM(inbound_calls), 0) as inbound_calls,
                COALESCE(SUM(answered_calls), 0) as answered_calls,
                COALESCE(SUM(missed_calls), 0) as missed_calls,
                COALESCE(SUM(failed_calls), 0) as failed_calls,
                COALESCE(SUM(total_duration), 0) as total_duration
            ')
            ->first();

        $totalCalls = (int)($stats->total_calls ?? 0);
        $answeredCalls = (int)($stats->answered_calls ?? 0);

        return [
            'total_calls' => $totalCalls,
            'outbound_calls' => (int)($stats->outbound_calls ?? 0),
            'inbound_calls' => (int)($stats->inbound_calls ?? 0),
            'answered_calls' => $answeredCalls,
            'missed_calls' => (int)($stats->missed_calls ?? 0),
            'failed_calls' => (int)($stats->failed_calls ?? 0),
            'total_duration' => (int)($stats->total_duration ?? 0),
            'answer_rate' => $totalCalls > 0
                ? round(($answeredCalls / $totalCalls) * 100, 1)
                : 0,
        ];
    }

    /**
     * Link orphan call logs
     */
    public function linkOrphanCallLogs(string $businessId): array
    {
        return $this->service->linkOrphanCallLogs($businessId);
    }
}
