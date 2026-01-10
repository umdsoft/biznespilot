<?php

namespace App\Services;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PbxAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnlinePbxService
{
    protected ?PbxAccount $account = null;
    protected ?string $authKey = null;

    /**
     * Set the PBX account to use
     */
    public function setAccount(PbxAccount $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Get authentication header for OnlinePBX API
     * Format: "key_id:key"
     */
    protected function getAuthHeader(): string
    {
        if (!$this->account) {
            return '';
        }

        // OnlinePBX uses key_id:key format
        $keyId = $this->account->getSetting('key_id', '');
        $apiKey = $this->account->api_key;

        return $keyId ? "{$keyId}:{$apiKey}" : $apiKey;
    }

    /**
     * Get base URL for API
     */
    protected function getBaseUrl(): string
    {
        if (!$this->account) {
            return '';
        }

        // OnlinePBX API URL format: https://{domain}.onpbx.ru/
        $apiUrl = rtrim($this->account->api_url, '/');

        return $apiUrl;
    }

    /**
     * Authenticate with OnlinePBX
     */
    public function authenticate(): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getAuthHeader(),
                ])
                ->get($this->getBaseUrl() . '/auth.json');

            if ($response->successful()) {
                $data = $response->json();
                $this->authKey = $data['data']['key'] ?? null;

                return [
                    'success' => true,
                    'key' => $this->authKey,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Authentication failed: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX authentication failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $apiUrl, string $apiKey, ?string $keyId = null): array
    {
        try {
            $authHeader = $keyId ? "{$keyId}:{$apiKey}" : $apiKey;
            $url = rtrim($apiUrl, '/') . '/auth.json';

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $authHeader,
                ])
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Ulanish muvaffaqiyatli',
                ];
            }

            return [
                'success' => false,
                'error' => 'Ulanib bo\'lmadi: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX connection test failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get call history
     */
    public function getCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'Account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (!$auth['success']) {
                return $auth;
            }

            $params = [];
            if ($dateFrom) {
                $params['date_from'] = $dateFrom->format('c');
            }
            if ($dateTo) {
                $params['date_to'] = $dateTo->format('c');
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getAuthHeader(),
                ])
                ->get($this->getBaseUrl() . '/history.json', $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get call history',
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX get call history failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook from OnlinePBX
     * Creates/updates leads from incoming calls with duplicate detection
     */
    public function handleWebhook(array $data): void
    {
        Log::info('OnlinePBX Webhook processing', $data);

        // Get event type
        $event = $data['event'] ?? $data['type'] ?? $data['state'] ?? null;
        $direction = $data['direction'] ?? $data['type'] ?? null;
        $callId = $data['call_id'] ?? $data['uuid'] ?? $data['id'] ?? null;

        // Get phone numbers
        $callerNumber = $data['caller_id'] ?? $data['from'] ?? $data['src'] ?? null;
        $calledNumber = $data['called_id'] ?? $data['to'] ?? $data['dst'] ?? null;

        // For incoming calls, create/update lead
        if ($this->isIncomingCall($direction, $data)) {
            $this->handleIncomingCall($data, $callerNumber, $callId);
        }

        // Handle call status updates
        $this->handleCallStatus($data, $event, $callId);
    }

    /**
     * Check if this is an incoming call
     */
    protected function isIncomingCall(?string $direction, array $data): bool
    {
        $direction = strtolower($direction ?? '');

        // Check various formats
        if (in_array($direction, ['inbound', 'in', 'incoming', 'from-external'])) {
            return true;
        }

        // Check for incoming event type
        $event = strtolower($data['event'] ?? $data['state'] ?? '');
        if (str_contains($event, 'incoming') || str_contains($event, 'inbound')) {
            return true;
        }

        return false;
    }

    /**
     * Handle incoming call - create or update lead
     */
    protected function handleIncomingCall(array $data, ?string $phoneNumber, ?string $callId): void
    {
        if (empty($phoneNumber)) {
            Log::warning('OnlinePBX incoming call: No phone number', $data);
            return;
        }

        // Find business from PBX account by called number or settings
        $calledNumber = $data['called_id'] ?? $data['to'] ?? $data['dst'] ?? null;
        $pbxAccount = $this->findPbxAccount($calledNumber, $data);

        if (!$pbxAccount) {
            Log::warning('OnlinePBX incoming call: No matching PBX account', [
                'called_number' => $calledNumber,
            ]);
            return;
        }

        $businessId = $pbxAccount->business_id;

        // Normalize phone number for duplicate detection
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Find or create lead with duplicate detection
        $lead = $this->findOrCreateLead($businessId, $normalizedPhone, $phoneNumber, $data);

        // Create call log entry
        $this->createCallLog($businessId, $lead, $data, $callId);

        Log::info('OnlinePBX incoming call processed', [
            'lead_id' => $lead->id,
            'phone' => $normalizedPhone,
            'is_new' => $lead->wasRecentlyCreated,
        ]);
    }

    /**
     * Find PBX account by called number
     */
    protected function findPbxAccount(?string $calledNumber, array $data): ?PbxAccount
    {
        // First try to find by caller_id (called number)
        if ($calledNumber) {
            $normalizedNumber = $this->normalizePhoneNumber($calledNumber);

            $account = PbxAccount::where('is_active', true)
                ->where(function ($q) use ($normalizedNumber, $calledNumber) {
                    $q->where('caller_id', 'like', '%' . substr($normalizedNumber, -9) . '%')
                        ->orWhere('caller_id', 'like', '%' . substr($calledNumber, -9) . '%');
                })
                ->first();

            if ($account) {
                return $account;
            }
        }

        // Try to find by domain in webhook URL
        $domain = $data['domain'] ?? $data['pbx_domain'] ?? null;
        if ($domain) {
            $account = PbxAccount::where('is_active', true)
                ->where('api_url', 'like', '%' . $domain . '%')
                ->first();

            if ($account) {
                return $account;
            }
        }

        // Return first active account as fallback (for single-business setups)
        return PbxAccount::where('is_active', true)->first();
    }

    /**
     * Normalize phone number for duplicate detection
     */
    public function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Handle Uzbekistan numbers
        if (strlen($phone) === 9) {
            // Short format: 90 123 45 67 -> 998901234567
            $phone = '998' . $phone;
        } elseif (strlen($phone) === 12 && str_starts_with($phone, '998')) {
            // Already full format
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            // Russian format: 8 -> 7
            $phone = '7' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Find or create lead with duplicate detection
     */
    protected function findOrCreateLead(string $businessId, string $normalizedPhone, string $originalPhone, array $data): Lead
    {
        // Try to find existing lead by normalized phone
        $lead = Lead::where('business_id', $businessId)
            ->where(function ($query) use ($normalizedPhone, $originalPhone) {
                // Match by normalized phone (last 9 digits for flexibility)
                $last9 = substr($normalizedPhone, -9);
                $query->where('phone', 'like', '%' . $last9)
                    ->orWhere('phone', $normalizedPhone)
                    ->orWhere('phone', $originalPhone);
            })
            ->first();

        if ($lead) {
            // Update last contacted
            $lead->update([
                'last_contacted_at' => now(),
            ]);

            // Increment call count in data
            $leadData = $lead->data ?? [];
            $leadData['call_count'] = ($leadData['call_count'] ?? 0) + 1;
            $leadData['last_call_at'] = now()->toISOString();
            $lead->update(['data' => $leadData]);

            return $lead;
        }

        // Create new lead
        $source = $this->getOrCreatePhoneSource($businessId);

        return Lead::create([
            'uuid' => Str::uuid(),
            'business_id' => $businessId,
            'source_id' => $source?->id,
            'name' => 'Telefon: ' . $this->formatPhoneDisplay($originalPhone),
            'phone' => $normalizedPhone,
            'status' => 'new',
            'estimated_value' => 0,
            'notes' => 'Avtomatik yaratildi - kiruvchi qo\'ng\'iroq',
            'last_contacted_at' => now(),
            'data' => [
                'source' => 'phone_call',
                'call_count' => 1,
                'first_call_at' => now()->toISOString(),
                'last_call_at' => now()->toISOString(),
                'original_phone' => $originalPhone,
            ],
        ]);
    }

    /**
     * Get or create phone lead source
     */
    protected function getOrCreatePhoneSource(string $businessId): ?LeadSource
    {
        // Use callback to keep business_id scope with orWhere clauses
        $source = LeadSource::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('name', 'like', '%telefon%')
                    ->orWhere('name', 'like', '%phone%')
                    ->orWhere('name', 'like', '%qo\'ng\'iroq%');
            })
            ->first();

        if (!$source) {
            $source = LeadSource::create([
                'business_id' => $businessId,
                'name' => 'Telefon qo\'ng\'iroq',
                'type' => 'phone',
                'is_active' => true,
            ]);
        }

        return $source;
    }

    /**
     * Format phone for display
     */
    protected function formatPhoneDisplay(string $phone): string
    {
        $normalized = $this->normalizePhoneNumber($phone);

        if (strlen($normalized) === 12 && str_starts_with($normalized, '998')) {
            // Format: +998 (90) 123-45-67
            return '+' . substr($normalized, 0, 3) . ' (' . substr($normalized, 3, 2) . ') '
                . substr($normalized, 5, 3) . '-' . substr($normalized, 8, 2) . '-' . substr($normalized, 10, 2);
        }

        return '+' . $normalized;
    }

    /**
     * Create call log entry
     */
    protected function createCallLog(string $businessId, Lead $lead, array $data, ?string $callId): CallLog
    {
        $fromNumber = $data['caller_id'] ?? $data['from'] ?? $data['src'] ?? '';
        $toNumber = $data['called_id'] ?? $data['to'] ?? $data['dst'] ?? '';

        return CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $businessId,
            'lead_id' => $lead->id,
            'provider' => 'onlinepbx',
            'provider_call_id' => $callId,
            'direction' => CallLog::DIRECTION_INBOUND,
            'from_number' => $this->normalizePhoneNumber($fromNumber),
            'to_number' => $this->normalizePhoneNumber($toNumber),
            'status' => CallLog::STATUS_RINGING,
            'started_at' => now(),
            'metadata' => $data,
        ]);
    }

    /**
     * Handle call status updates
     */
    protected function handleCallStatus(array $data, ?string $event, ?string $callId): void
    {
        if (!$callId) {
            return;
        }

        $callLog = CallLog::where('provider_call_id', $callId)
            ->orWhere('id', $callId)
            ->first();

        if (!$callLog) {
            return;
        }

        $event = strtolower($event ?? '');

        switch ($event) {
            case 'ringing':
            case 'ring':
                $callLog->update(['status' => CallLog::STATUS_RINGING]);
                break;

            case 'answered':
            case 'answer':
            case 'connected':
                $callLog->markAsAnswered();
                break;

            case 'hangup':
            case 'end':
            case 'completed':
                $duration = $data['duration'] ?? $data['billsec'] ?? $data['talk_time'] ?? 0;
                $callLog->markAsCompleted($duration);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'failed':
            case 'error':
                $reason = $data['reason'] ?? $data['cause'] ?? $data['error'] ?? 'Unknown';
                $callLog->markAsFailed($reason);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'busy':
                $callLog->update(['status' => CallLog::STATUS_BUSY, 'ended_at' => now()]);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'no_answer':
            case 'noanswer':
            case 'timeout':
                $callLog->update(['status' => CallLog::STATUS_NO_ANSWER, 'ended_at' => now()]);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;
        }

        // Update recording URL if provided
        if (!empty($data['recording_url']) || !empty($data['record'])) {
            $recordingUrl = $data['recording_url'] ?? $data['record'] ?? null;
            if ($recordingUrl) {
                $callLog->update(['recording_url' => $recordingUrl]);
            }
        }
    }

    /**
     * Sync call history from OnlinePBX
     */
    public function syncCallHistory(?Carbon $dateFrom = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'Account not configured'];
        }

        $dateFrom = $dateFrom ?? Carbon::now()->subDays(7);
        $history = $this->getCallHistory($dateFrom);

        if (!$history['success']) {
            return $history;
        }

        $synced = 0;
        $created = 0;

        foreach ($history['data'] as $call) {
            $callId = $call['uuid'] ?? $call['id'] ?? null;

            if (!$callId) {
                continue;
            }

            // Check if already exists
            $exists = CallLog::where('provider_call_id', $callId)->exists();

            if (!$exists) {
                $this->handleWebhook($call);
                $created++;
            }

            $synced++;
        }

        return [
            'success' => true,
            'synced' => $synced,
            'created' => $created,
        ];
    }
}
