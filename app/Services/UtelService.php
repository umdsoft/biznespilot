<?php

namespace App\Services;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Task;
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

    protected string $baseUrl = 'https://api.utel.uz/api'; // Default, should be set per business

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
     * Set the base URL for API requests
     */
    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $email, string $password): array
    {
        // Trim whitespace from credentials
        $email = trim($email);
        $password = trim($password);

        try {
            Log::info('UTEL testConnection starting', [
                'url' => $this->baseUrl.'/v1/auth/login',
                'email' => $email,
                'password_length' => strlen($password),
            ]);

            $response = Http::timeout(15)
                ->accept('application/json')
                ->post($this->baseUrl.'/v1/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);

            Log::info('UTEL API response', [
                'status' => $response->status(),
                'body' => $response->body(),
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
                $data = $response->json();
                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Email yoki parol noto\'g\'ri',
                ];
            }

            if ($response->status() === 422) {
                $data = $response->json();
                $errors = $data['errors'] ?? [];
                $errorMsg = $data['message'] ?? 'Validation xatosi';
                if (!empty($errors)) {
                    $errorMsg .= ': ' . collect($errors)->flatten()->implode(', ');
                }
                return [
                    'success' => false,
                    'error' => $errorMsg,
                ];
            }

            return [
                'success' => false,
                'error' => 'Ulanib bo\'lmadi: '.$response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('UTEL connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate and get/refresh token
     */
    public function authenticate(): array
    {
        if (! $this->account) {
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
                ->post($this->baseUrl.'/v1/auth/login', [
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
                'error' => 'Autentifikatsiya xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Make authenticated API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $auth = $this->authenticate();
        if (! $auth['success']) {
            return $auth;
        }

        try {
            $request = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$auth['token'],
                    'Accept' => 'application/json',
                ]);

            $url = $this->baseUrl.$endpoint;

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
                'error' => $errorData['message'] ?? 'API xatosi: '.$response->status(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('UTEL API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'So\'rov xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Make an outbound call
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array
    {
        if (! $this->account) {
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
                'error' => 'UTEL xatosi: '.($result['error'] ?? 'Unknown error'),
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
                'error' => 'Xatolik: '.$e->getMessage(),
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
        if (! $this->account) {
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

                if (! $result['success']) {
                    break;
                }

                $data = $result['data'];
                $calls = $data['data'] ?? $data['items'] ?? $data ?? [];

                if (empty($calls)) {
                    break;
                }

                foreach ($calls as $call) {
                    $callId = $call['id'] ?? $call['call_id'] ?? null;

                    if (! $callId) {
                        continue;
                    }

                    // Check if call already exists
                    $existingCall = CallLog::where('provider_call_id', $callId)
                        ->where('provider', 'utel')
                        ->first();

                    if (! $existingCall) {
                        $this->processCallRecord($call);
                        $created++;
                    } else {
                        // For existing calls, check if we need to link to lead or clear missed calls
                        $this->updateExistingCallLeadLink($existingCall, $call);
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
                'error' => 'Sinxronizatsiya xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Update existing call's lead link and clear missed calls if needed
     */
    protected function updateExistingCallLeadLink(CallLog $callLog, array $callData): void
    {
        // Parse direction
        $typeData = $callData['type'] ?? $callData['direction'] ?? 'outbound';
        $typeName = is_array($typeData) ? ($typeData['name'] ?? 'Outgoing') : $typeData;
        $direction = $this->parseDirection($typeName);

        // Parse phone numbers
        $fromNumber = $this->normalizePhoneNumber($callData['src'] ?? $callData['from'] ?? '');
        $toNumber = $this->normalizePhoneNumber($callData['dst'] ?? $callData['to'] ?? '');
        $customerNumber = $direction === CallLog::DIRECTION_INBOUND ? $fromNumber : $toNumber;

        // Parse status
        $duration = (int) ($callData['conversation'] ?? $callData['duration'] ?? 0);
        $statusData = $callData['status'] ?? '';
        $statusName = is_array($statusData) ? ($statusData['name'] ?? '') : $statusData;
        $status = $this->parseCallStatus($statusName, $duration);

        // If call has no lead_id, try to link it
        if (! $callLog->lead_id && $customerNumber) {
            if ($direction === CallLog::DIRECTION_OUTBOUND) {
                $this->linkOutboundCallToLead($callLog, $customerNumber);
            }
        }

        // Update call status if changed
        if ($callLog->status !== $status) {
            $callLog->update(['status' => $status, 'duration' => $duration]);
        }

        // Clear missed calls if this was an answered call
        if ($status === CallLog::STATUS_ANSWERED && $duration > 0 && $callLog->lead_id) {
            $this->clearMissedCalls($callLog);
        }
    }

    /**
     * Process a call record from UTEL
     * UTEL API format:
     * - type: {"number": 1, "name": "Incoming"} or {"number": 2, "name": "Outgoing"}
     * - status: {"number": 1, "name": "Answered"} or {"number": 2, "name": "Not answered"}
     * - src: source phone, dst: destination phone
     * - date_time: call timestamp, recorded_file_url: recording URL
     */
    protected function processCallRecord(array $call): void
    {
        // Use call_id as unique identifier (UUID format)
        $callId = $call['call_id'] ?? $call['id'] ?? null;
        if (! $callId) {
            return;
        }

        // Parse direction - handle both object and string formats
        $typeData = $call['type'] ?? $call['direction'] ?? 'outbound';
        $typeName = is_array($typeData) ? ($typeData['name'] ?? 'Outgoing') : $typeData;
        $direction = $this->parseDirection($typeName);

        // Parse phone numbers - UTEL uses src/dst
        $fromNumber = $this->normalizePhoneNumber($call['src'] ?? $call['source'] ?? $call['from'] ?? '');
        $toNumber = $this->normalizePhoneNumber($call['dst'] ?? $call['destination'] ?? $call['to'] ?? '');

        // For incoming calls, the customer is the source (src)
        // For outgoing calls, the customer is the destination (dst)
        $customerNumber = $direction === CallLog::DIRECTION_INBOUND ? $fromNumber : $toNumber;

        // Duration and conversation time
        // IMPORTANT: duration = total time (including ringing), conversation = actual talk time
        // If conversation = 0, call was NOT answered even if duration > 0
        $duration = (int) ($call['duration'] ?? $call['billsec'] ?? 0);
        $conversation = (int) ($call['conversation'] ?? 0);

        // Parse status - handle both object and string formats
        // UTEL status takes priority - "Not answered" means missed regardless of duration
        $statusData = $call['status'] ?? $call['disposition'] ?? '';
        $statusName = is_array($statusData) ? ($statusData['name'] ?? '') : $statusData;
        // Pass conversation time (not duration) - if conversation = 0, call was not answered
        $status = $this->parseCallStatus($statusName, $conversation);

        // Parse timestamp - UTEL uses date_time
        $startedAt = isset($call['date_time'])
            ? Carbon::parse($call['date_time'])
            : (isset($call['started_at'])
                ? Carbon::parse($call['started_at'])
                : (isset($call['date']) ? Carbon::parse($call['date']) : now()));

        // Recording URL - UTEL uses recorded_file_url
        $recordingUrl = $call['recorded_file_url'] ?? $call['record_url'] ?? $call['recording_url'] ?? null;

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
            'conversation' => $conversation, // Actual talk time (0 if not answered)
            'started_at' => $startedAt,
            'ended_at' => $duration > 0 ? $startedAt->copy()->addSeconds($duration) : null,
            'recording_url' => $recordingUrl,
            'metadata' => $call,
        ]);

        // Try to find and link lead for incoming calls (create if not found)
        if ($direction === CallLog::DIRECTION_INBOUND && $customerNumber) {
            $this->findOrCreateLeadForCall($callLog, $customerNumber);
            $callLog->refresh(); // Reload to get updated lead_id
        }

        // For outbound calls, try to find existing lead by phone number (don't create new)
        if ($direction === CallLog::DIRECTION_OUTBOUND && $customerNumber) {
            $this->linkOutboundCallToLead($callLog, $customerNumber);
            $callLog->refresh(); // Reload to get updated lead_id
        }

        // Record statistics
        CallDailyStat::recordCall($this->account->business_id, $callLog);

        // Create callback task for missed incoming calls
        if ($status === CallLog::STATUS_NO_ANSWER && $direction === CallLog::DIRECTION_INBOUND) {
            // createMissedCallTask will verify phone match before incrementing
            $this->createMissedCallTask($callLog);
        }

        // Clear missed calls if call was answered
        if ($status === CallLog::STATUS_ANSWERED && $duration > 0) {
            $this->clearMissedCalls($callLog);
        }
    }

    /**
     * Handle incoming webhook from UTEL
     */
    public function handleWebhook(array $data): void
    {
        try {
            Log::info('UTEL webhook data', $data);

            // UTEL sends nested data: data.data.name, data.data.call.id, etc.
            $nestedData = $data['data'] ?? $data;
            $eventType = $nestedData['name'] ?? $nestedData['event'] ?? $data['event'] ?? $data['type'] ?? $data['action'] ?? null;

            // Extract call_id from various possible locations
            $callId = $nestedData['call']['id']
                ?? $nestedData['call']['cdr']['id']
                ?? $nestedData['call_history']['call_id']
                ?? $nestedData['call_id']
                ?? $data['call_id']
                ?? $data['id']
                ?? $data['uuid']
                ?? null;

            if (! $callId) {
                Log::warning('UTEL webhook: No call ID found in data structure', [
                    'keys' => array_keys($data),
                    'nested_keys' => is_array($nestedData) ? array_keys($nestedData) : 'not_array',
                ]);
                return;
            }

            $businessId = $data['business_id'] ?? $this->account?->business_id ?? null;

            if (! $businessId) {
                Log::warning('UTEL webhook: No business ID', $data);
                return;
            }

            // Extract call data from nested structure
            $callData = $nestedData['call'] ?? $nestedData['call_history'] ?? $nestedData;
            $cdrData = $callData['cdr'] ?? $callData;

            // Parse direction - from cdr.type or call_history.type
            $typeData = $cdrData['type'] ?? $callData['type'] ?? $nestedData['type'] ?? $data['type'] ?? $data['direction'] ?? 'outbound';
            $typeName = is_array($typeData) ? ($typeData['name'] ?? 'Outgoing') : $typeData;
            $direction = $this->parseDirection($typeName);

            // Parse phone numbers from nested structure
            $fromNumber = $this->normalizePhoneNumber(
                $cdrData['src'] ?? $cdrData['caller'] ?? $callData['src'] ?? $nestedData['src'] ?? $data['src'] ?? $data['from'] ?? ''
            );
            $toNumber = $this->normalizePhoneNumber(
                $cdrData['dst'] ?? $cdrData['external_number'] ?? $callData['exten'] ?? $nestedData['dst'] ?? $data['dst'] ?? $data['to'] ?? ''
            );
            $customerNumber = $direction === CallLog::DIRECTION_INBOUND ? $fromNumber : $toNumber;

            Log::info('UTEL webhook parsed data', [
                'call_id' => $callId,
                'event' => $eventType,
                'direction' => $direction,
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'customer_number' => $customerNumber,
            ]);

            // Find or create call log
            $callLog = CallLog::where('provider_call_id', $callId)
                ->where('provider', 'utel')
                ->first();

            if (! $callLog) {
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
                    $callLog->refresh(); // Reload to get updated lead_id
                }

                // For outbound calls, link to existing lead
                if ($direction === CallLog::DIRECTION_OUTBOUND && $customerNumber) {
                    $this->linkOutboundCallToLead($callLog, $customerNumber);
                    $callLog->refresh(); // Reload to get updated lead_id
                }
            }

            // Normalize event type - UTEL may send "Call Started", "call_started", or "call.started"
            $normalizedEvent = strtolower(str_replace([' ', '_'], '.', $eventType ?? ''));

            // Process event
            switch ($normalizedEvent) {
                case 'call.started':
                case 'dial.started':
                case 'call.ringing':
                case 'ringing':
                    $callLog->update(['status' => CallLog::STATUS_RINGING]);
                    break;

                case 'call.answered':
                case 'dial.answered':
                case 'answered':
                case 'answer':
                    $callLog->markAsAnswered();
                    // Clear missed calls when call is answered
                    $this->clearMissedCalls($callLog);
                    break;

                case 'call.ended':
                case 'dial.ended':
                case 'call.completed':
                case 'call.saved':
                case 'hangup':
                case 'completed':
                    // UTEL sends nested data - extract duration and status from correct location
                    // call_saved: data.call_history.conversation, data.call_history.status
                    // call_ended: data.call.cdr.conversation, data.call.cdr.ended_at - started_at
                    $callHistory = $nestedData['call_history'] ?? null;
                    $callCdr = $nestedData['call']['cdr'] ?? null;

                    // Get duration - prefer conversation time (actual talk time)
                    $duration = 0;
                    if ($callHistory) {
                        $duration = (int) ($callHistory['conversation'] ?? $callHistory['duration'] ?? 0);
                    } elseif ($callCdr) {
                        $duration = (int) ($callCdr['conversation'] ?? 0);
                        // If no conversation time but has ended_at/started_at, calculate duration
                        if ($duration === 0 && isset($callCdr['ended_at']) && isset($callCdr['started_at'])) {
                            $duration = (int) $callCdr['ended_at'] - (int) $callCdr['started_at'];
                        }
                    }
                    // Get conversation time (actual talk time) - this is what determines if call was answered
                    $conversation = 0;
                    if ($callHistory) {
                        $conversation = (int) ($callHistory['conversation'] ?? 0);
                    } elseif ($callCdr) {
                        $conversation = (int) ($callCdr['conversation'] ?? 0);
                    }

                    // Fallback to top-level data for duration (but keep conversation separate)
                    if ($duration === 0) {
                        $duration = (int) ($data['duration'] ?? $data['billsec'] ?? 0);
                    }

                    // Get status - handle object format from UTEL
                    // UTEL status takes priority over duration/conversation
                    $statusData = null;
                    if ($callHistory && isset($callHistory['status'])) {
                        $statusData = $callHistory['status'];
                    } elseif ($callCdr && $conversation > 0) {
                        // If conversation > 0, call was answered
                        $statusData = 'Answered';
                    } else {
                        $statusData = $data['status'] ?? $data['disposition'] ?? 'Not answered';
                    }
                    $statusName = is_array($statusData) ? ($statusData['name'] ?? 'Not answered') : $statusData;
                    // Pass conversation (not duration) to properly determine answered/missed
                    $status = $this->parseCallStatus($statusName, $conversation);

                    $callLog->update([
                        'status' => $status,
                        'duration' => $duration,
                        'conversation' => $conversation, // Actual talk time (0 if not answered)
                        'ended_at' => now(),
                    ]);

                    // Recording URL - UTEL uses recorded_file_url in call_history
                    $recordingUrl = $callHistory['recorded_file_url'] ?? $data['recorded_file_url'] ?? $data['record_url'] ?? $data['recording_url'] ?? null;
                    if ($recordingUrl) {
                        $callLog->update([
                            'recording_url' => $recordingUrl,
                        ]);
                    }

                    CallDailyStat::recordCall($businessId, $callLog);

                    // Create callback task if call was missed/not answered
                    if ($status === CallLog::STATUS_NO_ANSWER && $direction === CallLog::DIRECTION_INBOUND) {
                        $this->createMissedCallTask($callLog);
                    }

                    // Clear missed calls if call was answered (has duration)
                    if ($status === CallLog::STATUS_ANSWERED && $duration > 0) {
                        $this->clearMissedCalls($callLog);
                    }
                    break;

                case 'call.transferred':
                    // Just log transfer, don't change status
                    Log::info('UTEL call transferred', ['call_id' => $callId]);
                    break;

                case 'call.missed':
                case 'missed':
                case 'no.answer':
                    $callLog->update([
                        'status' => CallLog::STATUS_NO_ANSWER,
                        'ended_at' => now(),
                    ]);
                    CallDailyStat::recordCall($businessId, $callLog);

                    // Create callback task for missed call
                    $this->createMissedCallTask($callLog);
                    break;

                case 'call.failed':
                case 'failed':
                case 'error':
                    $reason = $data['reason'] ?? $data['cause'] ?? 'Unknown';
                    $callLog->markAsFailed($reason);
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                default:
                    Log::info('UTEL webhook: Unknown event type', ['event' => $eventType, 'normalized' => $normalizedEvent]);
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

        if (in_array($status, ['no_answer', 'noanswer', 'not answered', 'not_answered', 'missed', 'unanswered', 'cancel'])) {
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
            $phone = '998'.$phone;
        }

        return $phone;
    }

    /**
     * Find or create lead for incoming call
     */
    protected function findOrCreateLeadForCall(CallLog $callLog, string $phoneNumber): void
    {
        $businessId = $callLog->business_id;

        // Normalize phone for matching
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        $last9Digits = substr($cleanPhone, -9);

        // Skip if phone is too short (less than 9 digits)
        if (strlen($last9Digits) < 9) {
            Log::warning('UTEL: Phone number too short, skipping lead match', [
                'call_id' => $callLog->id,
                'phone' => $phoneNumber,
                'clean_phone' => $cleanPhone,
            ]);
            return;
        }

        Log::info('UTEL: Searching for lead by phone', [
            'call_id' => $callLog->id,
            'original_phone' => $phoneNumber,
            'clean_phone' => $cleanPhone,
            'last_9_digits' => $last9Digits,
            'business_id' => $businessId,
        ]);

        // Try to find existing lead by phone number - EXACT matching at end
        $lead = Lead::where('business_id', $businessId)
            ->where(function ($query) use ($cleanPhone, $last9Digits) {
                // Exact matches first (higher priority)
                $query->where('phone', $cleanPhone)
                    ->orWhere('phone', '+' . $cleanPhone)
                    ->orWhere('phone', '998' . $last9Digits)
                    ->orWhere('phone', '+998' . $last9Digits)
                    // Match ONLY at end of phone number (not anywhere)
                    ->orWhere('phone', 'LIKE', '%' . $last9Digits);
            })
            ->first();

        // IMPORTANT: Verify the match is correct by checking the last 9 digits
        if ($lead) {
            $leadPhoneClean = preg_replace('/[^0-9]/', '', $lead->phone);
            $leadLast9 = substr($leadPhoneClean, -9);

            // If the last 9 digits don't match, this is a FALSE POSITIVE - don't link!
            if ($leadLast9 !== $last9Digits) {
                Log::warning('UTEL: FALSE POSITIVE - Lead phone does not match caller phone!', [
                    'call_id' => $callLog->id,
                    'caller_phone' => $phoneNumber,
                    'caller_last_9' => $last9Digits,
                    'lead_id' => $lead->id,
                    'lead_phone' => $lead->phone,
                    'lead_last_9' => $leadLast9,
                ]);
                // Treat as no match - will create new lead
                $lead = null;
            }
        }

        if ($lead) {
            $callLog->update(['lead_id' => $lead->id]);
            $lead->update(['last_contacted_at' => now()]);

            Log::info('UTEL: Linked incoming call to existing lead', [
                'call_id' => $callLog->id,
                'lead_id' => $lead->id,
                'incoming_phone' => $phoneNumber,
                'matched_lead_phone' => $lead->phone,
            ]);
        } else {
            // Create new lead from incoming call
            // Find phone/call lead source - search by code or name
            $leadSource = LeadSource::where('business_id', $businessId)
                ->where(function ($q) {
                    $q->where('code', 'LIKE', '%phone%')
                        ->orWhere('code', 'LIKE', '%call%')
                        ->orWhere('code', 'LIKE', '%telefon%')
                        ->orWhere('name', 'LIKE', '%Telefon%')
                        ->orWhere('name', 'LIKE', '%telefon%')
                        ->orWhere('name', 'LIKE', '%Phone%')
                        ->orWhere('name', 'LIKE', '%Call%');
                })
                ->first();

            // Fallback to any offline source
            if (! $leadSource) {
                $leadSource = LeadSource::where('business_id', $businessId)
                    ->where('category', 'offline')
                    ->first();
            }

            // If still no source, create one for phone calls
            if (! $leadSource) {
                $leadSource = LeadSource::create([
                    'business_id' => $businessId,
                    'code' => 'phone_call',
                    'name' => 'Telefon qo\'ng\'iroq',
                    'category' => 'offline',
                    'icon' => 'phone',
                    'color' => '#10B981',
                    'is_active' => true,
                    'sort_order' => 100,
                ]);
            }

            // Format phone as +998XXXXXXXXX for consistency
            $formattedPhone = '+998' . $last9Digits;

            $newLead = Lead::create([
                'id' => Str::uuid(),
                'business_id' => $businessId,
                'source_id' => $leadSource?->id,
                'name' => 'Kiruvchi qo\'ng\'iroq (UTEL)',
                'phone' => $formattedPhone,
                'status' => 'new',
                'last_contacted_at' => now(),
                'data' => [
                    'created_from' => 'utel_incoming_call',
                    'call_id' => $callLog->id,
                    'original_phone' => $phoneNumber,
                ],
            ]);

            $callLog->update(['lead_id' => $newLead->id]);

            // Create follow-up task for the new lead
            $this->createFollowUpTask($newLead, 'Yangi lid bilan bog\'lanish', 'Kiruvchi qo\'ng\'iroqdan yangi lid yaratildi. Mijoz bilan bog\'laning.');

            Log::info('Created lead from UTEL incoming call', [
                'lead_id' => $newLead->id,
                'phone' => $formattedPhone,
                'original_phone' => $phoneNumber,
            ]);
        }
    }

    /**
     * Link outbound call to existing lead by phone number
     * Does not create new lead - only links if lead already exists
     */
    protected function linkOutboundCallToLead(CallLog $callLog, string $phoneNumber): void
    {
        // Normalize phone for search - get last 9 digits (O'zbekiston raqamlari uchun)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        $last9Digits = substr($cleanPhone, -9);

        // Skip if phone is too short
        if (strlen($last9Digits) < 9) {
            return;
        }

        // Find existing lead by phone - match EXACTLY at the end of phone number
        // This prevents matching wrong leads when phone numbers share some digits
        $lead = Lead::where('business_id', $this->account->business_id)
            ->where(function ($query) use ($last9Digits, $cleanPhone) {
                // Exact match with full number
                $query->where('phone', $cleanPhone)
                    ->orWhere('phone', '+' . $cleanPhone)
                    ->orWhere('phone', '998' . $last9Digits)
                    ->orWhere('phone', '+998' . $last9Digits)
                    // Match ending with last 9 digits (not anywhere in the string)
                    ->orWhere('phone', 'LIKE', '%' . $last9Digits);
            })
            ->first();

        // IMPORTANT: Verify the match is correct
        if ($lead) {
            $leadPhoneClean = preg_replace('/[^0-9]/', '', $lead->phone);
            $leadLast9 = substr($leadPhoneClean, -9);

            // If the last 9 digits don't match, this is a FALSE POSITIVE
            if ($leadLast9 !== $last9Digits) {
                Log::warning('UTEL: FALSE POSITIVE in outbound call linking!', [
                    'call_id' => $callLog->id,
                    'target_phone' => $phoneNumber,
                    'target_last_9' => $last9Digits,
                    'lead_id' => $lead->id,
                    'lead_phone' => $lead->phone,
                    'lead_last_9' => $leadLast9,
                ]);
                return; // Don't link to wrong lead
            }
        }

        if ($lead) {
            $callLog->update(['lead_id' => $lead->id]);

            Log::info('Linked outbound call to existing lead', [
                'call_id' => $callLog->id,
                'lead_id' => $lead->id,
                'phone' => $phoneNumber,
                'matched_lead_phone' => $lead->phone,
            ]);
        }
    }

    /**
     * Create a follow-up task for a lead
     */
    protected function createFollowUpTask(Lead $lead, string $title, string $description, string $priority = 'high'): Task
    {
        // Get user_id - use assigned_to or fallback to business owner (user_id is owner in Business model)
        $userId = $lead->assigned_to;
        if (! $userId) {
            $business = \App\Models\Business::find($lead->business_id);
            $userId = $business?->user_id; // Business uses user_id for owner
        }

        return Task::create([
            'id' => Str::uuid(),
            'business_id' => $lead->business_id,
            'user_id' => $userId, // Required field
            'lead_id' => $lead->id,
            'assigned_to' => $lead->assigned_to,
            'title' => $title,
            'description' => $description,
            'type' => 'follow_up',
            'priority' => $priority,
            'status' => 'pending',
            'due_date' => now()->addHours(1), // 1 soat ichida bajarish kerak
        ]);
    }

    /**
     * Create a callback task for missed call
     */
    public function createMissedCallTask(CallLog $callLog): ?Task
    {
        // Refresh callLog to get latest lead_id from database
        $callLog->refresh();

        if (! $callLog->lead_id) {
            Log::info('UTEL: No lead_id for missed call, skipping task creation', [
                'call_id' => $callLog->id,
                'from_number' => $callLog->from_number,
            ]);
            return null;
        }

        $lead = Lead::find($callLog->lead_id);
        if (! $lead) {
            Log::warning('UTEL: Lead not found for missed call', [
                'call_id' => $callLog->id,
                'lead_id' => $callLog->lead_id,
            ]);
            return null;
        }

        // IMPORTANT: Verify that the call's phone matches the lead's phone
        // This prevents incrementing missed calls on wrong lead
        $callPhone = $callLog->direction === CallLog::DIRECTION_INBOUND
            ? $callLog->from_number
            : $callLog->to_number;
        $callPhoneClean = preg_replace('/[^0-9]/', '', $callPhone ?? '');
        $callLast9 = substr($callPhoneClean, -9);

        $leadPhoneClean = preg_replace('/[^0-9]/', '', $lead->phone ?? '');
        $leadLast9 = substr($leadPhoneClean, -9);

        if (strlen($callLast9) >= 9 && strlen($leadLast9) >= 9 && $callLast9 !== $leadLast9) {
            Log::error('UTEL: MISMATCH - Call phone does not match lead phone! NOT incrementing missed calls.', [
                'call_id' => $callLog->id,
                'call_phone' => $callPhone,
                'call_last_9' => $callLast9,
                'lead_id' => $lead->id,
                'lead_phone' => $lead->phone,
                'lead_last_9' => $leadLast9,
            ]);
            return null; // Do NOT increment missed calls on wrong lead!
        }

        // IMPORTANT: Check if this call has already been processed to avoid duplicate counting
        // UTEL sends both call_saved and call_ended events for the same call
        $lastProcessedCallId = $lead->data['last_processed_missed_call_id'] ?? null;
        $providerCallId = $callLog->provider_call_id;

        if ($lastProcessedCallId === $providerCallId) {
            Log::info('UTEL: Call already processed for missed calls, skipping duplicate', [
                'call_id' => $callLog->id,
                'provider_call_id' => $providerCallId,
                'lead_id' => $lead->id,
            ]);
            // Return existing task if any
            return Task::where('lead_id', $lead->id)
                ->where('type', 'call')
                ->where('status', 'pending')
                ->where('title', 'LIKE', '%qayta qo\'ng\'iroq%')
                ->first();
        }

        Log::info('UTEL: Creating missed call task', [
            'call_id' => $callLog->id,
            'provider_call_id' => $providerCallId,
            'call_phone' => $callPhone,
            'lead_id' => $lead->id,
            'lead_phone' => $lead->phone,
        ]);

        // Increment missed calls counter and store last processed call_id
        $missedCalls = ($lead->data['missed_calls'] ?? 0) + 1;
        $lead->update([
            'data' => array_merge($lead->data ?? [], [
                'missed_calls' => $missedCalls,
                'last_call_status' => 'no_answer',
                'last_missed_call_at' => now()->toIso8601String(),
                'last_processed_missed_call_id' => $providerCallId, // Track to avoid duplicate counting
            ]),
        ]);

        // Check if there's already a pending callback task
        $existingTask = Task::where('lead_id', $lead->id)
            ->where('type', 'call')
            ->where('status', 'pending')
            ->where('title', 'LIKE', '%qayta qo\'ng\'iroq%')
            ->first();

        if ($existingTask) {
            // Update existing task priority if multiple missed calls
            if ($missedCalls >= 3) {
                $existingTask->update(['priority' => 'urgent']);
            }
            return $existingTask;
        }

        // Get user_id - use assigned_to or fallback to business owner (user_id is owner in Business model)
        $userId = $lead->assigned_to;
        if (! $userId) {
            $business = \App\Models\Business::find($lead->business_id);
            $userId = $business?->user_id; // Business uses user_id for owner
        }

        // Create new callback task
        $task = Task::create([
            'id' => Str::uuid(),
            'business_id' => $lead->business_id,
            'user_id' => $userId, // Required field - fallback to business owner
            'lead_id' => $lead->id,
            'assigned_to' => $lead->assigned_to,
            'title' => 'O\'tkazib yuborilgan qo\'ng\'iroq - qayta qo\'ng\'iroq qilish',
            'description' => "Mijoz qo'ng'iroq qildi, lekin javob berilmadi. Tezroq qayta bog'laning!\n\nTelefon: {$lead->phone}\nO'tkazib yuborilgan: {$missedCalls} ta",
            'type' => 'call',
            'priority' => $missedCalls >= 2 ? 'urgent' : 'high',
            'status' => 'pending',
            'due_date' => now()->addHour(), // 1 soat ichida qayta qo'ng'iroq qilish kerak
        ]);

        Log::info('Created missed call task', [
            'lead_id' => $lead->id,
            'task_id' => $task->id,
            'missed_calls' => $missedCalls,
        ]);

        return $task;
    }

    /**
     * Reconcile missed calls for all leads in a business
     * Checks if any leads with missed_calls > 0 have had successful outbound calls
     */
    public function reconcileMissedCalls(): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'UTEL account not configured'];
        }

        $reconciled = 0;
        $businessId = $this->account->business_id;

        // Find all leads with missed calls
        $leadsWithMissedCalls = Lead::where('business_id', $businessId)
            ->whereNotNull('data')
            ->get()
            ->filter(function ($lead) {
                return ($lead->data['missed_calls'] ?? 0) > 0;
            });

        foreach ($leadsWithMissedCalls as $lead) {
            // Check if there's a successful outbound call to this lead after the last missed call
            $lastMissedAt = isset($lead->data['last_missed_call_at'])
                ? Carbon::parse($lead->data['last_missed_call_at'])
                : null;

            // Get the last 9 digits of the phone for EXACT matching at end
            $cleanPhone = preg_replace('/[^0-9]/', '', $lead->phone);
            $last9Digits = substr($cleanPhone, -9);

            // Skip if phone is too short
            if (strlen($last9Digits) < 9) {
                continue;
            }

            // First check: calls already linked to this lead
            $successfulCall = CallLog::where('business_id', $businessId)
                ->where('lead_id', $lead->id)
                ->where('direction', CallLog::DIRECTION_OUTBOUND)
                ->where('status', CallLog::STATUS_ANSWERED)
                ->where('duration', '>', 0)
                ->when($lastMissedAt, function ($q) use ($lastMissedAt) {
                    $q->where('started_at', '>', $lastMissedAt);
                })
                ->first();

            // Second check: calls not linked but matching phone EXACTLY at end
            if (! $successfulCall) {
                $successfulCall = CallLog::where('business_id', $businessId)
                    ->whereNull('lead_id')
                    ->where('direction', CallLog::DIRECTION_OUTBOUND)
                    ->where('status', CallLog::STATUS_ANSWERED)
                    ->where('duration', '>', 0)
                    ->where(function ($q) use ($last9Digits, $cleanPhone) {
                        // Exact matches first
                        $q->where('to_number', $cleanPhone)
                            ->orWhere('to_number', '+' . $cleanPhone)
                            ->orWhere('to_number', '998' . $last9Digits)
                            ->orWhere('to_number', '+998' . $last9Digits)
                            // Match ONLY at end of phone number (not anywhere)
                            ->orWhere('to_number', 'LIKE', '%' . $last9Digits);
                    })
                    ->when($lastMissedAt, function ($q) use ($lastMissedAt) {
                        $q->where('started_at', '>', $lastMissedAt);
                    })
                    ->first();
            }

            if ($successfulCall) {
                // Link the call to the lead if not linked
                if (! $successfulCall->lead_id) {
                    $successfulCall->update(['lead_id' => $lead->id]);
                }

                // Clear missed calls
                $lead->update([
                    'data' => array_merge($lead->data ?? [], [
                        'missed_calls' => 0,
                        'last_call_status' => 'answered',
                        'last_successful_call_at' => $successfulCall->started_at->toIso8601String(),
                    ]),
                ]);

                // Complete pending callback tasks
                Task::where('lead_id', $lead->id)
                    ->where('type', 'call')
                    ->where('status', 'pending')
                    ->where('title', 'LIKE', '%qayta qo\'ng\'iroq%')
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                $reconciled++;

                Log::info('Reconciled missed calls for lead', [
                    'lead_id' => $lead->id,
                    'lead_phone' => $lead->phone,
                    'call_id' => $successfulCall->id,
                    'call_to_number' => $successfulCall->to_number,
                ]);
            }
        }

        return [
            'success' => true,
            'reconciled' => $reconciled,
            'total_checked' => $leadsWithMissedCalls->count(),
        ];
    }

    /**
     * Clear missed calls counter when a successful call is made
     */
    public function clearMissedCalls(CallLog $callLog): void
    {
        if (! $callLog->lead_id) {
            return;
        }

        $lead = Lead::find($callLog->lead_id);
        if (! $lead) {
            return;
        }

        // Only clear if there were missed calls
        $currentMissedCalls = $lead->data['missed_calls'] ?? 0;
        if ($currentMissedCalls === 0) {
            return;
        }

        // Update lead data - clear missed calls
        $lead->update([
            'data' => array_merge($lead->data ?? [], [
                'missed_calls' => 0,
                'last_call_status' => 'answered',
                'last_successful_call_at' => now()->toIso8601String(),
            ]),
        ]);

        // Complete any pending callback tasks for this lead
        Task::where('lead_id', $lead->id)
            ->where('type', 'call')
            ->where('status', 'pending')
            ->where('title', 'LIKE', '%qayta qo\'ng\'iroq%')
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        Log::info('Cleared missed calls for lead', [
            'lead_id' => $lead->id,
            'previous_missed_calls' => $currentMissedCalls,
        ]);
    }
}
