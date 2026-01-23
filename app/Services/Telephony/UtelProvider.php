<?php

namespace App\Services\Telephony;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\UtelAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * UTEL Provider - O'zbekiston IP telefoniya integratsiyasi
 * API URL is set per account via settings (e.g., api.cc279.utel.uz)
 */
class UtelProvider extends AbstractTelephonyProvider
{
    protected ?UtelAccount $account = null;

    protected string $baseUrl = 'https://api.utel.uz/api'; // Default, set from account settings

    /**
     * Set the UTEL account
     */
    public function setAccount(UtelAccount $account): self
    {
        $this->account = $account;
        $this->baseUrl = $account->getApiBaseUrl();

        return $this;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'utel';
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return 'UTEL';
    }

    /**
     * Check if configured
     */
    public function isConfigured(): bool
    {
        return $this->account && $this->account->isConfigured();
    }

    /**
     * Authenticate with UTEL API
     */
    public function authenticate(): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        // Check if we have valid token
        if ($this->account->hasValidToken()) {
            return ['success' => true, 'token' => $this->account->access_token];
        }

        try {
            $password = Crypt::decryptString($this->account->password);

            $response = Http::timeout(15)
                ->accept('application/json')
                ->post($this->baseUrl . '/v1/auth/login', [
                    'email' => $this->account->email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // UTEL API returns: {"status":"success","result":{"access_token":"..."}}
                $token = $data['result']['access_token']
                    ?? $data['access_token']
                    ?? $data['token']
                    ?? null;

                if ($token) {
                    $expiresAt = isset($data['expires_at'])
                        ? Carbon::parse($data['expires_at'])
                        : (isset($data['result']['expires_at'])
                            ? Carbon::parse($data['result']['expires_at'])
                            : now()->addHours(24));

                    $this->account->update([
                        'access_token' => $token,
                        'token_expires_at' => $expiresAt,
                    ]);

                    return ['success' => true, 'token' => $token];
                }
            }

            return [
                'success' => false,
                'error' => 'Autentifikatsiya muvaffaqiyatsiz',
            ];
        } catch (\Exception $e) {
            Log::error('UTEL authentication failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Autentifikatsiya xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(array $credentials): array
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            return ['success' => false, 'error' => 'Email va parol talab qilinadi'];
        }

        try {
            $response = Http::timeout(15)
                ->accept('application/json')
                ->post($this->baseUrl . '/v1/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // UTEL API returns: {"status":"success","result":{"access_token":"..."}}
                $token = $data['result']['access_token']
                    ?? $data['access_token']
                    ?? $data['token']
                    ?? null;

                if ($token) {
                    return [
                        'success' => true,
                        'message' => 'Ulanish muvaffaqiyatli',
                        'token' => $token,
                        'expires_at' => $data['expires_at'] ?? $data['result']['expires_at'] ?? null,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Autentifikatsiya muvaffaqiyatsiz',
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'error' => 'Email yoki parol noto\'g\'ri',
                ];
            }

            return [
                'success' => false,
                'error' => 'Ulanib bo\'lmadi: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('UTEL connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parse webhook data
     */
    public function parseWebhook(Request $request): array
    {
        $data = $request->all();

        $eventType = $data['event'] ?? $data['type'] ?? $data['action'] ?? 'unknown';
        $callId = $data['call_id'] ?? $data['id'] ?? $data['uuid'] ?? null;

        $direction = $this->parseDirection($data['type'] ?? $data['direction'] ?? 'outbound');
        $fromNumber = $this->normalizePhoneNumber($data['source'] ?? $data['from'] ?? $data['caller'] ?? '');
        $toNumber = $this->normalizePhoneNumber($data['destination'] ?? $data['to'] ?? $data['callee'] ?? '');

        $duration = (int)($data['duration'] ?? $data['billsec'] ?? 0);
        $status = $this->parseCallStatus($data['status'] ?? $data['disposition'] ?? '', $duration);

        return [
            'event' => $eventType,
            'call_id' => $callId,
            'direction' => $direction,
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
            'status' => $status,
            'duration' => $duration,
            'recording_url' => $data['record_url'] ?? $data['recording_url'] ?? null,
            'metadata' => $data,
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(Request $request, ?string $secret = null): bool
    {
        $secret = $secret ?? $this->account?->webhook_secret;

        if (!$secret) {
            return true; // No secret configured, skip verification
        }

        $signature = $request->header('X-Utel-Signature') ?? $request->header('X-Webhook-Signature');

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
        try {
            Log::info('UTEL webhook data', $data);

            $eventType = $data['event'] ?? $data['type'] ?? $data['action'] ?? null;
            $callId = $data['call_id'] ?? $data['id'] ?? $data['uuid'] ?? null;

            if (!$callId) {
                Log::warning('UTEL webhook: No call ID', $data);
                return;
            }

            $businessId = $data['business_id'] ?? $this->account?->business_id ?? null;

            if (!$businessId) {
                Log::warning('UTEL webhook: No business ID', $data);
                return;
            }

            $direction = $this->parseDirection($data['type'] ?? $data['direction'] ?? 'outbound');
            $fromNumber = $this->normalizePhoneNumber($data['source'] ?? $data['from'] ?? $data['caller'] ?? '');
            $toNumber = $this->normalizePhoneNumber($data['destination'] ?? $data['to'] ?? $data['callee'] ?? '');
            $customerNumber = $direction === CallLog::DIRECTION_INBOUND ? $fromNumber : $toNumber;

            // Find or create call log
            $callLog = $this->findCallLog($callId);

            if (!$callLog) {
                $callLog = CallLog::create([
                    'id' => Str::uuid(),
                    'business_id' => $businessId,
                    'provider' => $this->getName(),
                    'provider_call_id' => $callId,
                    'direction' => $direction,
                    'from_number' => $fromNumber,
                    'to_number' => $toNumber,
                    'status' => CallLog::STATUS_INITIATED,
                    'started_at' => now(),
                    'metadata' => $data,
                ]);

                // Find or create lead for incoming calls
                if ($direction === CallLog::DIRECTION_INBOUND && $customerNumber) {
                    $lead = $this->findOrCreateLeadForCall($businessId, $customerNumber, $direction, $data);
                    if ($lead) {
                        $callLog->update(['lead_id' => $lead->id]);
                    }
                }
            }

            // Process event
            $this->processWebhookEvent($callLog, $eventType, $data, $businessId);

        } catch (\Exception $e) {
            Log::error('UTEL webhook error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Process webhook event
     */
    protected function processWebhookEvent(CallLog $callLog, ?string $eventType, array $data, string $businessId): void
    {
        switch ($eventType) {
            case 'call.started':
            case 'call.ringing':
            case 'ringing':
                $callLog->update(['status' => CallLog::STATUS_RINGING]);
                break;

            case 'call.answered':
            case 'answered':
            case 'answer':
                $callLog->markAsAnswered();
                break;

            case 'call.ended':
            case 'call.completed':
            case 'hangup':
            case 'completed':
                $duration = (int)($data['duration'] ?? $data['billsec'] ?? 0);
                $status = $this->parseCallStatus($data['status'] ?? $data['disposition'] ?? 'completed', $duration);

                $callLog->update([
                    'status' => $status,
                    'duration' => $duration,
                    'ended_at' => now(),
                ]);

                if (!empty($data['record_url']) || !empty($data['recording_url'])) {
                    $callLog->update([
                        'recording_url' => $data['record_url'] ?? $data['recording_url'],
                    ]);
                }

                $this->recordCallStatistics($businessId, $callLog);
                break;

            case 'call.missed':
            case 'missed':
            case 'no_answer':
                $callLog->update([
                    'status' => CallLog::STATUS_NO_ANSWER,
                    'ended_at' => now(),
                ]);
                $this->recordCallStatistics($businessId, $callLog);
                break;

            case 'call.failed':
            case 'failed':
            case 'error':
                $reason = $data['reason'] ?? $data['cause'] ?? 'Unknown';
                $callLog->markAsFailed($reason);
                $this->recordCallStatistics($businessId, $callLog);
                break;
        }
    }

    /**
     * Make authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $auth = $this->authenticate();
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $request = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $auth['token'],
                    'Accept' => 'application/json',
                ]);

            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'PATCH' => $request->patch($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => $request->get($url, $data),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $errorData = $response->json();

            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'API xatosi: ' . $response->status(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('UTEL API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'So\'rov xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make an outbound call
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        try {
            $fromNumber = $fromNumber ?? $this->account->caller_id;
            $extension = $this->account->extension;
            $normalizedTo = $this->normalizePhoneNumber($toNumber);

            // Create call log entry first
            $callLog = CallLog::create([
                'id' => Str::uuid(),
                'business_id' => $this->account->business_id,
                'lead_id' => $lead?->id,
                'user_id' => Auth::id(),
                'provider' => $this->getName(),
                'direction' => CallLog::DIRECTION_OUTBOUND,
                'from_number' => $this->normalizePhoneNumber($fromNumber ?? ''),
                'to_number' => $normalizedTo,
                'status' => CallLog::STATUS_INITIATED,
                'started_at' => now(),
            ]);

            // UTEL originate API
            $result = $this->apiRequest('POST', '/v1/integration/services/originate', [
                'source' => $extension,
                'destination' => $normalizedTo,
                'caller_id' => $fromNumber,
            ]);

            if ($result['success']) {
                $data = $result['data'];
                $providerCallId = $data['call_id'] ?? $data['id'] ?? $data['uuid'] ?? null;

                $callLog->update([
                    'provider_call_id' => $providerCallId,
                    'status' => CallLog::STATUS_RINGING,
                    'metadata' => $data,
                ]);

                // Update lead's last contacted
                if ($lead) {
                    $lead->update(['last_contacted_at' => now()]);
                }

                return [
                    'success' => true,
                    'call_id' => $callLog->id,
                    'provider_call_id' => $providerCallId,
                    'message' => 'Qo\'ng\'iroq boshlandi',
                ];
            }

            $callLog->markAsFailed($result['error'] ?? 'Unknown error');

            return [
                'success' => false,
                'error' => 'UTEL xatosi: ' . ($result['error'] ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('UTEL make call failed', [
                'error' => $e->getMessage(),
                'to' => $toNumber,
            ]);

            if (isset($callLog)) {
                $callLog->markAsFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => 'Xatolik: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Hangup call
     */
    public function hangupCall(string $callId): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        try {
            $callLog = CallLog::find($callId);
            $providerCallId = $callLog?->provider_call_id ?? $callId;

            $result = $this->apiRequest('POST', '/v1/call/hangup', [
                'call_id' => $providerCallId,
            ]);

            if ($result['success']) {
                if ($callLog) {
                    $callLog->update([
                        'status' => CallLog::STATUS_COMPLETED,
                        'ended_at' => now(),
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Qo\'ng\'iroq tugatildi',
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get call status
     */
    public function getCallStatus(string $callId): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        return $this->apiRequest('GET', '/v1/call/status', ['call_id' => $callId]);
    }

    /**
     * Get call history
     */
    public function getCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? Carbon::now()->subDays(7);
        $dateTo = $dateTo ?? Carbon::now();

        return $this->apiRequest('GET', '/v1/call-history', [
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $dateTo->format('Y-m-d'),
            'per_page' => 100,
        ]);
    }

    /**
     * Sync call history
     */
    public function syncCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        try {
            $dateFrom = $dateFrom ?? Carbon::now()->subDays(7);
            $dateTo = $dateTo ?? Carbon::now();

            $synced = 0;
            $created = 0;
            $page = 1;

            do {
                $result = $this->apiRequest('GET', '/v1/call-history', [
                    'date_from' => $dateFrom->format('Y-m-d'),
                    'date_to' => $dateTo->format('Y-m-d'),
                    'page' => $page,
                    'per_page' => 100,
                ]);

                if (!$result['success']) {
                    break;
                }

                $data = $result['data'];
                $calls = $data['data'] ?? $data['items'] ?? $data ?? [];

                if (empty($calls)) {
                    break;
                }

                foreach ($calls as $call) {
                    $callId = $call['id'] ?? $call['call_id'] ?? null;

                    if (!$callId) {
                        continue;
                    }

                    // Check if call already exists
                    if (!$this->callExists($callId)) {
                        $this->processCallRecord($call);
                        $created++;
                    }

                    $synced++;
                }

                $page++;
                $hasMore = isset($data['next_page_url']) || (isset($data['meta']['last_page']) && $page <= $data['meta']['last_page']);

            } while ($hasMore && $page <= 10);

            // Update last sync time
            $this->account->update(['last_sync_at' => now()]);

            return [
                'success' => true,
                'synced' => $synced,
                'created' => $created,
            ];
        } catch (\Exception $e) {
            Log::error('UTEL sync call history failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Sinxronizatsiya xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process a call record from API
     */
    protected function processCallRecord(array $call): void
    {
        $callId = $call['id'] ?? $call['call_id'] ?? null;
        if (!$callId) {
            return;
        }

        $direction = $this->parseDirection($call['type'] ?? $call['direction'] ?? 'outbound');
        $fromNumber = $this->normalizePhoneNumber($call['source'] ?? $call['from'] ?? '');
        $toNumber = $this->normalizePhoneNumber($call['destination'] ?? $call['to'] ?? '');
        $customerNumber = $direction === CallLog::DIRECTION_INBOUND ? $fromNumber : $toNumber;

        $duration = (int)($call['duration'] ?? $call['billsec'] ?? 0);
        $status = $this->parseCallStatus($call['status'] ?? $call['disposition'] ?? '', $duration);

        $startedAt = isset($call['started_at'])
            ? Carbon::parse($call['started_at'])
            : (isset($call['date']) ? Carbon::parse($call['date']) : now());

        $callLog = CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $this->account->business_id,
            'provider' => $this->getName(),
            'provider_call_id' => $callId,
            'direction' => $direction,
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
            'status' => $status,
            'duration' => $duration,
            'started_at' => $startedAt,
            'ended_at' => $duration > 0 ? $startedAt->copy()->addSeconds($duration) : null,
            'recording_url' => $call['record_url'] ?? $call['recording_url'] ?? null,
            'metadata' => $call,
        ]);

        // Link to lead for incoming calls
        if ($direction === CallLog::DIRECTION_INBOUND && $customerNumber) {
            $lead = $this->findOrCreateLeadForCall($this->account->business_id, $customerNumber, $direction, $call);
            if ($lead) {
                $callLog->update(['lead_id' => $lead->id]);
            }
        }

        // Record statistics
        $this->recordCallStatistics($this->account->business_id, $callLog);
    }

    /**
     * Get account balance
     */
    public function getBalance(): array
    {
        $result = $this->apiRequest('GET', '/v1/billing/account/info');

        if ($result['success']) {
            $data = $result['data'];
            $balance = $data['balance'] ?? $data['data']['balance'] ?? 0;
            $currency = $data['currency'] ?? $data['data']['currency'] ?? 'UZS';

            $this->account->update([
                'balance' => $balance,
                'currency' => $currency,
                'last_sync_at' => now(),
            ]);

            return [
                'success' => true,
                'data' => [
                    'balance' => $balance,
                    'currency' => $currency,
                ],
            ];
        }

        return $result;
    }

    /**
     * Get recording URL
     */
    public function getRecordingUrl(string $callId): ?string
    {
        // First check if we have it in database
        $callLog = CallLog::find($callId) ?? $this->findCallLog($callId);

        if ($callLog && $callLog->recording_url) {
            return $callLog->recording_url;
        }

        // Try to fetch from API
        $result = $this->apiRequest('GET', '/v1/call/recording', ['call_id' => $callId]);

        if ($result['success']) {
            $url = $result['data']['url'] ?? $result['data']['recording_url'] ?? null;

            if ($url && $callLog) {
                $callLog->update(['recording_url' => $url]);
            }

            return $url;
        }

        return null;
    }

    /**
     * Get users/extensions
     */
    public function getUsers(): array
    {
        return $this->apiRequest('GET', '/v1/ats/ps-user');
    }

    /**
     * Configure webhook
     */
    public function configureWebhook(string $webhookUrl): array
    {
        return $this->apiRequest('PUT', '/v1/integration/webhook', [
            'url' => $webhookUrl,
            'events' => [
                'call.started',
                'call.answered',
                'call.ended',
                'call.missed',
                'call.failed',
                'call.transfer',
                'recording.ready',
            ],
            'is_active' => true,
        ]);
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
        $linked = 0;
        $failed = 0;

        $orphanCalls = CallLog::where('business_id', $businessId)
            ->where('provider', $this->getName())
            ->whereNull('lead_id')
            ->get();

        foreach ($orphanCalls as $call) {
            try {
                $phoneNumber = $call->direction === CallLog::DIRECTION_INBOUND
                    ? $call->from_number
                    : $call->to_number;

                if (empty($phoneNumber)) {
                    $failed++;
                    continue;
                }

                $lead = $this->findOrCreateLeadForCall($businessId, $phoneNumber, $call->direction);

                if ($lead) {
                    $call->update(['lead_id' => $lead->id]);
                    $linked++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to link orphan call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return [
            'success' => true,
            'linked' => $linked,
            'failed' => $failed,
            'total' => $orphanCalls->count(),
        ];
    }
}
