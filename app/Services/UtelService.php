<?php

namespace App\Services;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\UtelAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UtelService
{
    protected ?UtelAccount $account = null;
    protected string $baseUrl = 'https://api.utel.uz';

    /**
     * Set the UTEL account to use
     */
    public function setAccount(UtelAccount $account): self
    {
        $this->account = $account;
        $this->baseUrl = $account->getApiBaseUrl();
        return $this;
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $email, string $password): array
    {
        try {
            $response = Http::timeout(15)
                ->post($this->baseUrl . '/v1/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['access_token']) || isset($data['token'])) {
                    return [
                        'success' => true,
                        'message' => 'Ulanish muvaffaqiyatli',
                        'token' => $data['access_token'] ?? $data['token'],
                        'expires_at' => $data['expires_at'] ?? null,
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
     * Authenticate and get/refresh token
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
                ->post($this->baseUrl . '/v1/auth/login', [
                    'email' => $this->account->email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['access_token']) || isset($data['token'])) {
                    $token = $data['access_token'] ?? $data['token'];
                    $expiresAt = isset($data['expires_at'])
                        ? Carbon::parse($data['expires_at'])
                        : now()->addHours(24);

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
                'provider' => 'utel',
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
     * Get call history from UTEL
     */
    public function getCallHistory(?Carbon $fromDate = null, ?Carbon $toDate = null, int $page = 1): array
    {
        $fromDate = $fromDate ?? Carbon::now()->subDays(7);
        $toDate = $toDate ?? Carbon::now();

        return $this->apiRequest('GET', '/v1/call-history', [
            'date_from' => $fromDate->format('Y-m-d'),
            'date_to' => $toDate->format('Y-m-d'),
            'page' => $page,
            'per_page' => 100,
        ]);
    }

    /**
     * Sync call history from UTEL
     */
    public function syncCallHistory(?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        try {
            $fromDate = $fromDate ?? Carbon::now()->subDays(7);
            $toDate = $toDate ?? Carbon::now();

            $synced = 0;
            $created = 0;
            $page = 1;

            do {
                $result = $this->getCallHistory($fromDate, $toDate, $page);

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
                    $exists = CallLog::where('provider_call_id', $callId)
                        ->where('provider', 'utel')
                        ->exists();

                    if (!$exists) {
                        $this->processCallRecord($call);
                        $created++;
                    }

                    $synced++;
                }

                $page++;
                $hasMore = isset($data['next_page_url']) || (isset($data['meta']['last_page']) && $page <= $data['meta']['last_page']);

            } while ($hasMore && $page <= 10); // Limit to 10 pages

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
     * Process a call record from UTEL
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

        $duration = (int) ($call['duration'] ?? $call['billsec'] ?? 0);
        $status = $this->parseCallStatus($call['status'] ?? $call['disposition'] ?? '', $duration);

        $startedAt = isset($call['started_at'])
            ? Carbon::parse($call['started_at'])
            : (isset($call['date']) ? Carbon::parse($call['date']) : now());

        $callLog = CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $this->account->business_id,
            'provider' => 'utel',
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

        // Try to find and link lead for incoming calls
        if ($direction === CallLog::DIRECTION_INBOUND && $customerNumber) {
            $this->findOrCreateLeadForCall($callLog, $customerNumber);
        }

        // Record statistics
        CallDailyStat::recordCall($this->account->business_id, $callLog);
    }

    /**
     * Handle incoming webhook from UTEL
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
            $callLog = CallLog::where('provider_call_id', $callId)
                ->where('provider', 'utel')
                ->first();

            if (!$callLog) {
                $callLog = CallLog::create([
                    'id' => Str::uuid(),
                    'business_id' => $businessId,
                    'provider' => 'utel',
                    'provider_call_id' => $callId,
                    'direction' => $direction,
                    'from_number' => $fromNumber,
                    'to_number' => $toNumber,
                    'status' => CallLog::STATUS_INITIATED,
                    'started_at' => now(),
                    'metadata' => $data,
                ]);

                // Try to find or create lead for incoming calls
                if ($direction === CallLog::DIRECTION_INBOUND && $customerNumber) {
                    $this->findOrCreateLeadForCall($callLog, $customerNumber);
                }
            }

            // Process event
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
                    $duration = (int) ($data['duration'] ?? $data['billsec'] ?? 0);
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

                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                case 'call.missed':
                case 'missed':
                case 'no_answer':
                    $callLog->update([
                        'status' => CallLog::STATUS_NO_ANSWER,
                        'ended_at' => now(),
                    ]);
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                case 'call.failed':
                case 'failed':
                case 'error':
                    $reason = $data['reason'] ?? $data['cause'] ?? 'Unknown';
                    $callLog->markAsFailed($reason);
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('UTEL webhook error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
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
     * Get call statistics
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

        $totalCalls = (int) ($stats->total_calls ?? 0);
        $answeredCalls = (int) ($stats->answered_calls ?? 0);

        return [
            'total_calls' => $totalCalls,
            'outbound_calls' => (int) ($stats->outbound_calls ?? 0),
            'inbound_calls' => (int) ($stats->inbound_calls ?? 0),
            'answered_calls' => $answeredCalls,
            'missed_calls' => (int) ($stats->missed_calls ?? 0),
            'failed_calls' => (int) ($stats->failed_calls ?? 0),
            'total_duration' => (int) ($stats->total_duration ?? 0),
            'answer_rate' => $totalCalls > 0
                ? round(($answeredCalls / $totalCalls) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get UTEL statistics from API
     */
    public function getApiStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $result = $this->apiRequest('GET', '/v1/statistic/calls-count', [
            'date_from' => $startDate->format('Y-m-d'),
            'date_to' => $endDate->format('Y-m-d'),
        ]);

        return $result;
    }

    /**
     * Configure webhook in UTEL
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
     * Get SIP users/extensions
     */
    public function getUsers(): array
    {
        return $this->apiRequest('GET', '/v1/ats/ps-user');
    }

    /**
     * Parse call direction from UTEL format
     */
    protected function parseDirection(string $direction): string
    {
        $direction = strtolower($direction);

        if (in_array($direction, ['in', 'incoming', 'inbound', 'internal_in'])) {
            return CallLog::DIRECTION_INBOUND;
        }

        return CallLog::DIRECTION_OUTBOUND;
    }

    /**
     * Parse call status from UTEL format
     */
    protected function parseCallStatus(string $status, int $duration): string
    {
        $status = strtolower($status);

        if (in_array($status, ['answered', 'answer', 'completed', 'success'])) {
            return $duration > 0 ? CallLog::STATUS_COMPLETED : CallLog::STATUS_NO_ANSWER;
        }

        if (in_array($status, ['no_answer', 'noanswer', 'missed', 'unanswered', 'cancel'])) {
            return CallLog::STATUS_NO_ANSWER;
        }

        if (in_array($status, ['busy', 'user_busy'])) {
            return CallLog::STATUS_BUSY;
        }

        if (in_array($status, ['failed', 'error', 'congestion', 'rejected'])) {
            return CallLog::STATUS_FAILED;
        }

        return $duration > 0 ? CallLog::STATUS_COMPLETED : CallLog::STATUS_NO_ANSWER;
    }

    /**
     * Normalize phone number to standard format
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $phone = ltrim($phone, '+');

        // Handle Uzbekistan numbers
        if (strlen($phone) === 9 && preg_match('/^[0-9]/', $phone)) {
            $phone = '998' . $phone;
        }

        return $phone;
    }

    /**
     * Find or create lead for incoming call
     */
    protected function findOrCreateLeadForCall(CallLog $callLog, string $phoneNumber): void
    {
        $businessId = $callLog->business_id;

        // Try to find existing lead by phone number
        $lead = Lead::where('business_id', $businessId)
            ->where(function ($query) use ($phoneNumber) {
                $query->where('phone', 'LIKE', '%' . substr($phoneNumber, -9))
                    ->orWhere('phone', $phoneNumber)
                    ->orWhere('phone', '+' . $phoneNumber);
            })
            ->first();

        if ($lead) {
            $callLog->update(['lead_id' => $lead->id]);
            $lead->update(['last_contacted_at' => now()]);
        } else {
            // Create new lead from incoming call
            $leadSource = LeadSource::where('business_id', $businessId)
                ->where('slug', 'phone')
                ->first();

            if (!$leadSource) {
                $leadSource = LeadSource::where('business_id', $businessId)
                    ->where('type', 'offline')
                    ->first();
            }

            $newLead = Lead::create([
                'id' => Str::uuid(),
                'business_id' => $businessId,
                'lead_source_id' => $leadSource?->id,
                'name' => 'Kiruvchi qo\'ng\'iroq (UTEL)',
                'phone' => '+' . $phoneNumber,
                'status' => 'new',
                'last_contacted_at' => now(),
                'metadata' => [
                    'created_from' => 'utel_incoming_call',
                    'call_id' => $callLog->id,
                ],
            ]);

            $callLog->update(['lead_id' => $newLead->id]);

            Log::info('Created lead from UTEL incoming call', [
                'lead_id' => $newLead->id,
                'phone' => $phoneNumber,
            ]);
        }
    }
}
