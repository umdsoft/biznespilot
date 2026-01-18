<?php

namespace App\Services;

use App\Contracts\PbxServiceInterface;
use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PbxAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnlinePbxService implements PbxServiceInterface
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
        if (! $this->account) {
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
        if (! $this->account) {
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
            $authHeader = $this->getAuthHeader();
            $baseUrl = $this->getBaseUrl();

            Log::debug('OnlinePBX authenticate: Starting', [
                'base_url' => $baseUrl,
                'has_auth_header' => ! empty($authHeader),
            ]);

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $authHeader,
                ])
                ->get($baseUrl.'/auth.json');

            if ($response->successful()) {
                $data = $response->json();

                // Check if response is HTML (login page) instead of JSON
                $body = $response->body();
                if (str_starts_with(trim($body), '<!DOCTYPE') || str_starts_with(trim($body), '<html')) {
                    Log::error('OnlinePBX authenticate: Received HTML instead of JSON', [
                        'base_url' => $baseUrl,
                    ]);

                    return [
                        'success' => false,
                        'error' => 'API URL noto\'g\'ri yoki API kaliti yaroqsiz',
                    ];
                }

                // Check if API returned success
                if (($data['status'] ?? 0) == 1) {
                    $this->authKey = $data['data']['key'] ?? null;

                    Log::debug('OnlinePBX authenticate: Success', [
                        'has_key' => ! empty($this->authKey),
                    ]);

                    return [
                        'success' => true,
                        'key' => $this->authKey,
                        'data' => $data,
                    ];
                }

                Log::warning('OnlinePBX authenticate: API returned error', [
                    'status' => $data['status'] ?? null,
                    'comment' => $data['comment'] ?? null,
                ]);

                return [
                    'success' => false,
                    'error' => $data['comment'] ?? 'Autentifikatsiya xatosi',
                ];
            }

            Log::error('OnlinePBX authenticate: HTTP error', [
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'Authentication failed: HTTP '.$response->status(),
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
            $url = rtrim($apiUrl, '/').'/auth.json';

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
                'error' => 'Ulanib bo\'lmadi: '.$response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get the authentication key for API calls after initial auth
     * Uses session key if available, otherwise falls back to original credentials
     */
    protected function getSessionAuthHeader(): string
    {
        // If we have an auth key from successful authentication, use it
        if ($this->authKey) {
            return $this->authKey;
        }

        // Fallback to original credentials
        return $this->getAuthHeader();
    }

    /**
     * Get call history
     */
    public function getCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'Account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            $params = [];
            if ($dateFrom) {
                $params['date_from'] = $dateFrom->format('c');
            }
            if ($dateTo) {
                $params['date_to'] = $dateTo->format('c');
            }

            // Use session key obtained from authentication
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->get($this->getBaseUrl().'/history.json', $params);

            // Check if response is actually JSON (not HTML login page)
            $contentType = $response->header('Content-Type') ?? '';
            $body = $response->body();

            if ((! str_contains($contentType, 'application/json') && str_starts_with(trim($body), '<!DOCTYPE')) || str_starts_with(trim($body), '<html')) {
                Log::error('OnlinePBX history returned HTML instead of JSON', [
                    'status' => $response->status(),
                    'content_type' => $contentType,
                ]);

                return [
                    'success' => false,
                    'error' => 'API sessiya muddati tugagan yoki autentifikatsiya xatosi',
                ];
            }

            if ($response->successful()) {
                $data = $response->json();

                // Check if API returned success status
                if (($data['status'] ?? 0) == 1 || isset($data['data'])) {
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? [],
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['comment'] ?? 'API xatosi',
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get call history: HTTP '.$response->status(),
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
     * Creates/updates leads from calls with duplicate detection
     */
    public function handleWebhook(array $data): void
    {
        Log::info('OnlinePBX Webhook processing', $data);

        // Get event type
        $event = $data['event'] ?? $data['type'] ?? $data['state'] ?? null;
        $direction = $data['direction'] ?? null;
        $callId = $data['call_id'] ?? $data['uuid'] ?? $data['id'] ?? null;

        // Get phone numbers - for outbound: caller is internal, callee is external
        $callerNumber = $data['caller_id'] ?? $data['caller'] ?? $data['from'] ?? $data['src'] ?? null;
        $calledNumber = $data['called_id'] ?? $data['callee'] ?? $data['to'] ?? $data['dst'] ?? null;

        // Determine direction if not explicitly set
        if (! $direction) {
            $direction = $this->detectCallDirection($callerNumber, $calledNumber, $data);
        }

        Log::info('OnlinePBX Webhook: Detected direction', [
            'direction' => $direction,
            'event' => $event,
            'call_id' => $callId,
            'caller' => $callerNumber,
            'callee' => $calledNumber,
        ]);

        // For incoming calls, create/update lead using caller number
        if ($this->isIncomingCall($direction, $data)) {
            $this->handleIncomingCall($data, $callerNumber, $callId);
        }
        // For outbound calls, create/update lead using callee number (the number being called)
        elseif ($this->isOutboundCall($direction, $data)) {
            $this->handleOutboundCall($data, $calledNumber, $callId);
        }
        // If direction couldn't be determined, try to create lead anyway based on phone number length
        else {
            Log::info('OnlinePBX Webhook: Direction unknown, trying fallback', $data);
            $this->handleUnknownDirectionCall($data, $callerNumber, $calledNumber, $callId);
        }

        // Handle call status updates
        $this->handleCallStatus($data, $event, $callId);
    }

    /**
     * Detect call direction based on phone number patterns
     */
    protected function detectCallDirection(?string $callerNumber, ?string $calledNumber, array $data): string
    {
        // If caller is short (internal extension), it's outbound
        if ($callerNumber && strlen(preg_replace('/[^0-9]/', '', $callerNumber)) < 7) {
            return 'outbound';
        }

        // If callee is short (internal extension), it's inbound
        if ($calledNumber && strlen(preg_replace('/[^0-9]/', '', $calledNumber)) < 7) {
            return 'inbound';
        }

        // Check event type for hints
        $event = strtolower($data['event'] ?? $data['type'] ?? '');
        if (str_contains($event, 'incoming') || str_contains($event, 'inbound')) {
            return 'inbound';
        }
        if (str_contains($event, 'outgoing') || str_contains($event, 'outbound')) {
            return 'outbound';
        }

        // Default to inbound if caller is external number
        if ($callerNumber && strlen(preg_replace('/[^0-9]/', '', $callerNumber)) >= 7) {
            return 'inbound';
        }

        return 'unknown';
    }

    /**
     * Handle call with unknown direction - try to create lead from external number
     */
    protected function handleUnknownDirectionCall(array $data, ?string $callerNumber, ?string $calledNumber, ?string $callId): void
    {
        // Try caller first (most likely external for incoming)
        $externalNumber = null;
        $isInbound = true;

        if ($callerNumber && strlen(preg_replace('/[^0-9]/', '', $callerNumber)) >= 7) {
            $externalNumber = $callerNumber;
            $isInbound = true;
        } elseif ($calledNumber && strlen(preg_replace('/[^0-9]/', '', $calledNumber)) >= 7) {
            $externalNumber = $calledNumber;
            $isInbound = false;
        }

        if (! $externalNumber) {
            Log::warning('OnlinePBX: Could not determine external number', $data);

            return;
        }

        // Find PBX account
        $pbxAccount = $this->findPbxAccountByDomain($data);
        if (! $pbxAccount) {
            $pbxAccount = PbxAccount::where('is_active', true)->first();
        }

        if (! $pbxAccount) {
            Log::warning('OnlinePBX: No active PBX account found');

            return;
        }

        $businessId = $pbxAccount->business_id;
        $normalizedPhone = $this->normalizePhoneNumber($externalNumber);

        // Find or create lead
        $lead = $this->findOrCreateLead($businessId, $normalizedPhone, $externalNumber, $data);

        // Check for duplicate call log
        if ($callId) {
            $existingCall = CallLog::where('provider_call_id', $callId)->first();
            if ($existingCall) {
                if (! $existingCall->lead_id && $lead) {
                    $existingCall->update(['lead_id' => $lead->id]);
                }

                return;
            }
        }

        // Create call log
        CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $businessId,
            'lead_id' => $lead->id,
            'provider' => 'onlinepbx',
            'provider_call_id' => $callId,
            'direction' => $isInbound ? CallLog::DIRECTION_INBOUND : CallLog::DIRECTION_OUTBOUND,
            'from_number' => $this->normalizePhoneNumber($callerNumber ?? ''),
            'to_number' => $this->normalizePhoneNumber($calledNumber ?? ''),
            'status' => CallLog::STATUS_RINGING,
            'started_at' => now(),
            'metadata' => $data,
        ]);

        Log::info('OnlinePBX: Created call from unknown direction', [
            'lead_id' => $lead->id,
            'direction' => $isInbound ? 'inbound' : 'outbound',
        ]);
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
     * Check if this is an outbound call
     */
    protected function isOutboundCall(?string $direction, array $data): bool
    {
        $direction = strtolower($direction ?? '');

        // Check various formats
        if (in_array($direction, ['outbound', 'out', 'outgoing', 'to-external'])) {
            return true;
        }

        // Check for outgoing event type
        $event = strtolower($data['event'] ?? $data['state'] ?? '');
        if (str_contains($event, 'outgoing') || str_contains($event, 'outbound')) {
            return true;
        }

        return false;
    }

    /**
     * Handle outbound call - create or update lead and log call
     */
    protected function handleOutboundCall(array $data, ?string $phoneNumber, ?string $callId): void
    {
        // Process both call_start and other events (for calls we might have missed)
        $event = strtolower($data['event'] ?? '');

        if (empty($phoneNumber)) {
            Log::warning('OnlinePBX outbound call: No phone number', $data);

            return;
        }

        // Skip internal calls (short numbers like 100, 101, etc.)
        if (strlen(preg_replace('/[^0-9]/', '', $phoneNumber)) < 7) {
            Log::info('OnlinePBX outbound call: Skipping internal call', ['callee' => $phoneNumber]);

            return;
        }

        // Find business from PBX account by domain
        $pbxAccount = $this->findPbxAccountByDomain($data);

        if (! $pbxAccount) {
            Log::warning('OnlinePBX outbound call: No matching PBX account', $data);

            return;
        }

        $businessId = $pbxAccount->business_id;

        // Normalize phone number for duplicate detection
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Find or create lead with duplicate detection
        $lead = $this->findOrCreateLeadForOutbound($businessId, $normalizedPhone, $phoneNumber, $data);

        // Check if call log already exists for this call (prevent duplicates)
        if ($callId) {
            $existingCall = CallLog::where('provider_call_id', $callId)->first();
            if ($existingCall) {
                // Just update the existing call log's lead_id if not set
                if (! $existingCall->lead_id && $lead) {
                    $existingCall->update(['lead_id' => $lead->id]);
                }
                Log::info('OnlinePBX outbound call: Call log already exists', [
                    'call_id' => $callId,
                    'lead_id' => $lead->id,
                ]);

                return;
            }
        }

        // Create call log entry for outbound call
        $this->createOutboundCallLog($businessId, $lead, $data, $callId);

        Log::info('OnlinePBX outbound call processed', [
            'lead_id' => $lead->id,
            'phone' => $normalizedPhone,
            'is_new' => $lead->wasRecentlyCreated,
        ]);
    }

    /**
     * Find PBX account by domain in webhook data
     */
    protected function findPbxAccountByDomain(array $data): ?PbxAccount
    {
        $domain = $data['domain'] ?? $data['pbx_domain'] ?? $data['from_domain'] ?? null;

        if ($domain) {
            $account = PbxAccount::where('is_active', true)
                ->where('api_url', 'like', '%'.$domain.'%')
                ->first();

            if ($account) {
                return $account;
            }
        }

        // Return first active account as fallback
        return PbxAccount::where('is_active', true)->first();
    }

    /**
     * Find or create lead for outbound call
     */
    protected function findOrCreateLeadForOutbound(string $businessId, string $normalizedPhone, string $originalPhone, array $data): Lead
    {
        // Try to find existing lead by normalized phone
        $lead = Lead::where('business_id', $businessId)
            ->where(function ($query) use ($normalizedPhone, $originalPhone) {
                $last9 = substr($normalizedPhone, -9);
                $query->where('phone', 'like', '%'.$last9)
                    ->orWhere('phone', $normalizedPhone)
                    ->orWhere('phone', $originalPhone);
            })
            ->first();

        // Determine call status from webhook data
        $callStatus = $this->determineCallStatus($data);

        if ($lead) {
            // Update last contacted
            $lead->update([
                'last_contacted_at' => now(),
            ]);

            // Increment call count in data and update last call status
            $leadData = $lead->data ?? [];
            $leadData['call_count'] = ($leadData['call_count'] ?? 0) + 1;
            $leadData['last_call_at'] = now()->toISOString();
            $leadData['last_call_status'] = $callStatus;
            $leadData['source'] = $leadData['source'] ?? 'phone_call_outbound';

            // Count answered and missed calls
            if (in_array($callStatus, ['completed', 'answered'])) {
                $leadData['answered_calls'] = ($leadData['answered_calls'] ?? 0) + 1;
            } elseif (in_array($callStatus, ['missed', 'no_answer'])) {
                $leadData['missed_calls'] = ($leadData['missed_calls'] ?? 0) + 1;
            }

            $lead->update(['data' => $leadData]);

            return $lead;
        }

        // Create new lead
        $source = $this->getOrCreatePhoneSource($businessId);

        // Determine initial call counts
        $answeredCalls = in_array($callStatus, ['completed', 'answered']) ? 1 : 0;
        $missedCalls = in_array($callStatus, ['missed', 'no_answer']) ? 1 : 0;

        return Lead::create([
            'business_id' => $businessId,
            'source_id' => $source?->id,
            'name' => $this->formatPhoneDisplay($originalPhone),
            'phone' => $normalizedPhone,
            'status' => 'new',
            'estimated_value' => 0,
            'notes' => 'Chiquvchi qo\'ng\'iroq orqali yaratildi',
            'last_contacted_at' => now(),
            'data' => [
                'source' => 'phone_call_outbound',
                'call_count' => 1,
                'answered_calls' => $answeredCalls,
                'missed_calls' => $missedCalls,
                'last_call_status' => $callStatus,
                'first_call_at' => now()->toISOString(),
                'last_call_at' => now()->toISOString(),
                'original_phone' => $originalPhone,
            ],
        ]);
    }

    /**
     * Create call log entry for outbound call
     */
    protected function createOutboundCallLog(string $businessId, Lead $lead, array $data, ?string $callId): CallLog
    {
        $fromNumber = $data['caller_id'] ?? $data['caller'] ?? $data['from'] ?? '';
        $toNumber = $data['called_id'] ?? $data['callee'] ?? $data['to'] ?? '';

        return CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $businessId,
            'lead_id' => $lead->id,
            'provider' => 'onlinepbx',
            'provider_call_id' => $callId,
            'direction' => CallLog::DIRECTION_OUTBOUND,
            'from_number' => $fromNumber,
            'to_number' => $this->normalizePhoneNumber($toNumber),
            'status' => CallLog::STATUS_RINGING,
            'started_at' => now(),
            'metadata' => $data,
        ]);
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

        // Skip internal calls (short numbers like 100, 101, etc.)
        if (strlen(preg_replace('/[^0-9]/', '', $phoneNumber)) < 7) {
            Log::info('OnlinePBX incoming call: Skipping internal call', ['caller' => $phoneNumber]);

            return;
        }

        // Find business from PBX account by called number or settings
        $calledNumber = $data['called_id'] ?? $data['to'] ?? $data['dst'] ?? null;
        $pbxAccount = $this->findPbxAccount($calledNumber, $data);

        if (! $pbxAccount) {
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

        // Check if call log already exists for this call (prevent duplicates)
        if ($callId) {
            $existingCall = CallLog::where('provider_call_id', $callId)->first();
            if ($existingCall) {
                // Just update the existing call log's lead_id if not set
                if (! $existingCall->lead_id && $lead) {
                    $existingCall->update(['lead_id' => $lead->id]);
                }
                Log::info('OnlinePBX incoming call: Call log already exists', [
                    'call_id' => $callId,
                    'lead_id' => $lead->id,
                ]);

                return;
            }
        }

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
                    $q->where('caller_id', 'like', '%'.substr($normalizedNumber, -9).'%')
                        ->orWhere('caller_id', 'like', '%'.substr($calledNumber, -9).'%');
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
                ->where('api_url', 'like', '%'.$domain.'%')
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
            $phone = '998'.$phone;
        } elseif (strlen($phone) === 12 && str_starts_with($phone, '998')) {
            // Already full format
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            // Russian format: 8 -> 7
            $phone = '7'.substr($phone, 1);
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
                $query->where('phone', 'like', '%'.$last9)
                    ->orWhere('phone', $normalizedPhone)
                    ->orWhere('phone', $originalPhone);
            })
            ->first();

        // Determine call status from webhook data
        $callStatus = $this->determineCallStatus($data);

        if ($lead) {
            // Update last contacted
            $lead->update([
                'last_contacted_at' => now(),
            ]);

            // Increment call count in data and update last call status
            $leadData = $lead->data ?? [];
            $leadData['call_count'] = ($leadData['call_count'] ?? 0) + 1;
            $leadData['last_call_at'] = now()->toISOString();
            $leadData['last_call_status'] = $callStatus;
            $leadData['source'] = $leadData['source'] ?? 'phone_call';

            // Count answered and missed calls
            if (in_array($callStatus, ['completed', 'answered'])) {
                $leadData['answered_calls'] = ($leadData['answered_calls'] ?? 0) + 1;
            } elseif (in_array($callStatus, ['missed', 'no_answer'])) {
                $leadData['missed_calls'] = ($leadData['missed_calls'] ?? 0) + 1;
            }

            $lead->update(['data' => $leadData]);

            return $lead;
        }

        // Create new lead
        $source = $this->getOrCreatePhoneSource($businessId);

        // Determine initial call counts
        $answeredCalls = in_array($callStatus, ['completed', 'answered']) ? 1 : 0;
        $missedCalls = in_array($callStatus, ['missed', 'no_answer']) ? 1 : 0;

        return Lead::create([
            'business_id' => $businessId,
            'source_id' => $source?->id,
            'name' => $this->formatPhoneDisplay($originalPhone),
            'phone' => $normalizedPhone,
            'status' => 'new',
            'estimated_value' => 0,
            'notes' => 'Kiruvchi qo\'ng\'iroq orqali yaratildi',
            'last_contacted_at' => now(),
            'data' => [
                'source' => 'phone_call',
                'call_count' => 1,
                'answered_calls' => $answeredCalls,
                'missed_calls' => $missedCalls,
                'last_call_status' => $callStatus,
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
        // First try to find by code (most reliable)
        $source = LeadSource::where('business_id', $businessId)
            ->where('code', 'phone_call')
            ->first();

        if ($source) {
            return $source;
        }

        // Fallback: search by name
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

        // Create new source with unique code
        try {
            $source = LeadSource::create([
                'business_id' => $businessId,
                'code' => 'phone_call_'.substr($businessId, 0, 8),
                'name' => 'Telefon qo\'ng\'iroq',
                'category' => 'offline',
                'icon' => 'phone',
                'color' => '#10B981',
                'is_paid' => false,
                'is_trackable' => true,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            // If code already exists, try with timestamp
            Log::warning('LeadSource creation failed, retrying with unique code', [
                'error' => $e->getMessage(),
            ]);

            $source = LeadSource::create([
                'business_id' => $businessId,
                'code' => 'phone_call_'.time(),
                'name' => 'Telefon qo\'ng\'iroq',
                'category' => 'offline',
                'icon' => 'phone',
                'color' => '#10B981',
                'is_paid' => false,
                'is_trackable' => true,
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
            return '+'.substr($normalized, 0, 3).' ('.substr($normalized, 3, 2).') '
                .substr($normalized, 5, 3).'-'.substr($normalized, 8, 2).'-'.substr($normalized, 10, 2);
        }

        return '+'.$normalized;
    }

    /**
     * Determine call status from webhook data
     */
    protected function determineCallStatus(array $data): string
    {
        // Check event type first
        $event = $data['event'] ?? $data['type'] ?? $data['state'] ?? null;

        if ($event) {
            $event = strtolower($event);

            // OnlinePBX event types
            if (in_array($event, ['call_missed', 'missed', 'call_unanswered'])) {
                return 'missed';
            }
            if (in_array($event, ['call_answered', 'answered', 'call_completed', 'completed', 'завершили'])) {
                return 'completed';
            }
            if (in_array($event, ['call_busy', 'busy'])) {
                return 'busy';
            }
            if (in_array($event, ['call_failed', 'failed'])) {
                return 'failed';
            }
            if (in_array($event, ['no_answer', 'noanswer', 'пропущенный'])) {
                return 'no_answer';
            }
        }

        // Check hangup_cause
        $hangupCause = $data['hangup_cause'] ?? $data['cause'] ?? null;
        if ($hangupCause) {
            $hangupCause = strtoupper($hangupCause);

            if (in_array($hangupCause, ['NORMAL_CLEARING', 'SUCCESS'])) {
                return 'completed';
            }
            if (in_array($hangupCause, ['NO_ANSWER', 'NO_USER_RESPONSE'])) {
                return 'no_answer';
            }
            if (in_array($hangupCause, ['USER_BUSY', 'BUSY'])) {
                return 'busy';
            }
            if (in_array($hangupCause, ['ORIGINATOR_CANCEL', 'CALL_REJECTED'])) {
                return 'missed';
            }
        }

        // Check dialog_duration - if > 0, call was answered
        $dialogDuration = intval($data['dialog_duration'] ?? $data['billsec'] ?? 0);
        if ($dialogDuration > 0) {
            return 'completed';
        }

        // Check call_duration vs dialog_duration
        $callDuration = intval($data['call_duration'] ?? $data['duration'] ?? 0);
        if ($callDuration > 0 && $dialogDuration === 0) {
            return 'missed';
        }

        // Default to 'initiated' if we can't determine
        return 'initiated';
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
        if (! $callId) {
            return;
        }

        $callLog = CallLog::where('provider_call_id', $callId)
            ->orWhere('id', $callId)
            ->first();

        if (! $callLog) {
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
        if (! empty($data['recording_url']) || ! empty($data['record'])) {
            $recordingUrl = $data['recording_url'] ?? $data['record'] ?? null;
            if ($recordingUrl) {
                $callLog->update(['recording_url' => $recordingUrl]);
            }
        }
    }

    /**
     * Sync call history from OnlinePBX
     * This fetches call history from API and creates leads/call logs for missed webhooks
     */
    public function syncCallHistory(?Carbon $dateFrom = null): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'Account not configured'];
        }

        $dateFrom = $dateFrom ?? Carbon::now()->subDays(1);
        $history = $this->getCallHistory($dateFrom);

        if (! $history['success']) {
            return $history;
        }

        Log::info('OnlinePBX syncCallHistory: Starting sync', [
            'business_id' => $this->account->business_id,
            'date_from' => $dateFrom->toDateTimeString(),
            'total_calls' => count($history['data'] ?? []),
        ]);

        $synced = 0;
        $created = 0;
        $updated = 0;

        foreach ($history['data'] as $call) {
            $callId = $call['uuid'] ?? $call['id'] ?? null;

            if (! $callId) {
                continue;
            }

            // Check if already exists
            $existingCall = CallLog::where('provider_call_id', $callId)->first();

            if (! $existingCall) {
                // Process this call - add event type for proper handling
                $call['event'] = 'call_start';
                $this->processHistoryCall($call);
                $created++;
            } else {
                // Update existing call with any new data (duration, recording, etc.)
                $this->updateCallFromHistory($existingCall, $call);
                $updated++;
            }

            $synced++;
        }

        Log::info('OnlinePBX syncCallHistory: Completed', [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
        ]);

        return [
            'success' => true,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Process a call from history API (not webhook)
     */
    protected function processHistoryCall(array $call): void
    {
        $callId = $call['uuid'] ?? $call['id'] ?? null;
        $direction = strtolower($call['direction'] ?? '');

        // Get phone numbers based on direction
        if ($direction === 'inbound' || $direction === 'in' || $direction === 'incoming') {
            // Incoming call: caller is external, callee is internal
            $externalNumber = $call['caller'] ?? $call['from'] ?? $call['src'] ?? null;
            $this->processIncomingHistoryCall($call, $externalNumber, $callId);
        } else {
            // Outbound call: caller is internal, callee is external
            $externalNumber = $call['callee'] ?? $call['to'] ?? $call['dst'] ?? null;
            if ($externalNumber && strlen(preg_replace('/[^0-9]/', '', $externalNumber)) >= 7) {
                $this->processOutboundHistoryCall($call, $externalNumber, $callId);
            }
        }
    }

    /**
     * Process incoming call from history
     */
    protected function processIncomingHistoryCall(array $data, ?string $phoneNumber, ?string $callId): void
    {
        if (empty($phoneNumber)) {
            return;
        }

        // Skip internal numbers
        if (strlen(preg_replace('/[^0-9]/', '', $phoneNumber)) < 7) {
            return;
        }

        $businessId = $this->account->business_id;
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Find or create lead
        $lead = $this->findOrCreateLead($businessId, $normalizedPhone, $phoneNumber, $data);

        // Create call log
        $fromNumber = $data['caller'] ?? $data['from'] ?? $data['src'] ?? '';
        $toNumber = $data['callee'] ?? $data['to'] ?? $data['dst'] ?? '';

        $callLog = CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $businessId,
            'lead_id' => $lead->id,
            'provider' => 'onlinepbx',
            'provider_call_id' => $callId,
            'direction' => CallLog::DIRECTION_INBOUND,
            'from_number' => $this->normalizePhoneNumber($fromNumber),
            'to_number' => $toNumber,
            'status' => $this->mapHistoryStatus($data),
            'duration' => intval($data['dialog_duration'] ?? $data['duration'] ?? $data['billsec'] ?? 0),
            'started_at' => $this->parseHistoryDate($data),
            'ended_at' => $this->parseHistoryEndDate($data),
            'recording_url' => $data['download_url'] ?? $data['record'] ?? $data['recording_url'] ?? null,
            'metadata' => $data,
        ]);

        Log::info('OnlinePBX: Created incoming call from history', [
            'call_id' => $callLog->id,
            'lead_id' => $lead->id,
            'phone' => $normalizedPhone,
        ]);
    }

    /**
     * Process outbound call from history
     */
    protected function processOutboundHistoryCall(array $data, ?string $phoneNumber, ?string $callId): void
    {
        if (empty($phoneNumber)) {
            return;
        }

        $businessId = $this->account->business_id;
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Find or create lead
        $lead = $this->findOrCreateLeadForOutbound($businessId, $normalizedPhone, $phoneNumber, $data);

        // Create call log
        $fromNumber = $data['caller'] ?? $data['from'] ?? $data['src'] ?? '';
        $toNumber = $data['callee'] ?? $data['to'] ?? $data['dst'] ?? '';

        $callLog = CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $businessId,
            'lead_id' => $lead->id,
            'provider' => 'onlinepbx',
            'provider_call_id' => $callId,
            'direction' => CallLog::DIRECTION_OUTBOUND,
            'from_number' => $fromNumber,
            'to_number' => $this->normalizePhoneNumber($toNumber),
            'status' => $this->mapHistoryStatus($data),
            'duration' => intval($data['dialog_duration'] ?? $data['duration'] ?? $data['billsec'] ?? 0),
            'started_at' => $this->parseHistoryDate($data),
            'ended_at' => $this->parseHistoryEndDate($data),
            'recording_url' => $data['download_url'] ?? $data['record'] ?? $data['recording_url'] ?? null,
            'metadata' => $data,
        ]);

        Log::info('OnlinePBX: Created outbound call from history', [
            'call_id' => $callLog->id,
            'lead_id' => $lead->id,
            'phone' => $normalizedPhone,
        ]);
    }

    /**
     * Update existing call log with data from history
     */
    protected function updateCallFromHistory(CallLog $callLog, array $data): void
    {
        $updates = [];

        // Update duration if we have it
        $duration = intval($data['dialog_duration'] ?? $data['duration'] ?? $data['billsec'] ?? 0);
        if ($duration > 0 && $callLog->duration === 0) {
            $updates['duration'] = $duration;
        }

        // Update recording URL if available
        $recordingUrl = $data['download_url'] ?? $data['record'] ?? $data['recording_url'] ?? null;
        if ($recordingUrl && ! $callLog->recording_url) {
            $updates['recording_url'] = $recordingUrl;
        }

        // Update status based on duration and history data
        // If call has duration > 0, it was definitely answered/completed
        if ($duration > 0 && ! in_array($callLog->status, [CallLog::STATUS_COMPLETED, CallLog::STATUS_ANSWERED])) {
            $updates['status'] = CallLog::STATUS_COMPLETED;
            $updates['ended_at'] = $this->parseHistoryEndDate($data) ?? now();
        }
        // Otherwise, use mapHistoryStatus for calls still in ringing/initiated state
        elseif (in_array($callLog->status, [CallLog::STATUS_RINGING, CallLog::STATUS_INITIATED])) {
            $status = $this->mapHistoryStatus($data);
            if ($status) {
                $updates['status'] = $status;
                $updates['ended_at'] = $this->parseHistoryEndDate($data) ?? now();
            }
        }

        if (! empty($updates)) {
            $callLog->update($updates);
        }
    }

    /**
     * Map history call status to our status
     */
    protected function mapHistoryStatus(array $data): string
    {
        $hangupCause = strtolower($data['hangup_cause'] ?? '');
        $dialogDuration = intval($data['dialog_duration'] ?? 0);

        if ($dialogDuration > 0) {
            return CallLog::STATUS_COMPLETED;
        }

        return match ($hangupCause) {
            'normal_clearing', 'normal' => CallLog::STATUS_COMPLETED,
            'user_busy', 'busy' => CallLog::STATUS_BUSY,
            'no_answer', 'no_user_response' => CallLog::STATUS_NO_ANSWER,
            'call_rejected', 'rejected' => CallLog::STATUS_MISSED,
            'originator_cancel' => CallLog::STATUS_CANCELLED,
            default => CallLog::STATUS_COMPLETED,
        };
    }

    /**
     * Parse date from history data
     */
    protected function parseHistoryDate(array $data): ?Carbon
    {
        $date = $data['date'] ?? $data['start_time'] ?? $data['created_at'] ?? null;

        if (! $date) {
            return now();
        }

        // If it's a timestamp
        if (is_numeric($date)) {
            return Carbon::createFromTimestamp($date);
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Parse end date from history data
     */
    protected function parseHistoryEndDate(array $data): ?Carbon
    {
        $startDate = $this->parseHistoryDate($data);
        $duration = intval($data['call_duration'] ?? $data['duration'] ?? 0);

        if ($startDate && $duration > 0) {
            return $startDate->copy()->addSeconds($duration);
        }

        return $startDate;
    }

    /**
     * Make an outbound call via OnlinePBX
     * API endpoint: /call/start
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'OnlinePBX account not configured'];
        }

        try {
            // Authenticate first
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            $fromNumber = $fromNumber ?? $this->account->caller_id;
            $extension = $this->account->extension ?? '100';
            $normalizedTo = $this->normalizePhoneNumber($toNumber);

            // Create call log entry first
            $callLog = CallLog::create([
                'id' => Str::uuid(),
                'business_id' => $this->account->business_id,
                'lead_id' => $lead?->id,
                'user_id' => Auth::id(),
                'provider' => 'onlinepbx',
                'direction' => CallLog::DIRECTION_OUTBOUND,
                'from_number' => $this->normalizePhoneNumber($fromNumber),
                'to_number' => $normalizedTo,
                'status' => CallLog::STATUS_INITIATED,
                'started_at' => now(),
            ]);

            // OnlinePBX API call to initiate outbound call
            // Format: POST /call/start with from (extension) and to (phone number)
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->getBaseUrl().'/call/start.json', [
                    'from' => $extension,
                    'to' => $normalizedTo,
                    'caller_id' => $fromNumber,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if API returned success
                if (($data['status'] ?? 0) == 1 || isset($data['data']['call_id'])) {
                    $providerCallId = $data['data']['call_id'] ?? $data['data']['uuid'] ?? $data['call_id'] ?? null;

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

                // API returned error
                $error = $data['comment'] ?? $data['error'] ?? 'Unknown error';
                $callLog->markAsFailed($error);

                return [
                    'success' => false,
                    'error' => 'OnlinePBX xatosi: '.$error,
                ];
            }

            $callLog->markAsFailed('HTTP '.$response->status());

            return [
                'success' => false,
                'error' => 'Qo\'ng\'iroq qilib bo\'lmadi: '.$response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX make call failed', [
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
     * Hangup an active call
     */
    public function hangupCall(string $callId): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'OnlinePBX account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            $callLog = CallLog::find($callId);
            $providerCallId = $callLog?->provider_call_id ?? $callId;

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->post($this->getBaseUrl().'/call/hangup.json', [
                    'call_id' => $providerCallId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
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

                return [
                    'success' => false,
                    'error' => $data['comment'] ?? 'Hangup failed',
                ];
            }

            return [
                'success' => false,
                'error' => 'Qo\'ng\'iroqni tugatib bo\'lmadi',
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX hangup call failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get account balance from OnlinePBX
     */
    public function getBalance(): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'OnlinePBX account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            // OnlinePBX balance endpoint
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->get($this->getBaseUrl().'/account/balance.json');

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    $balance = $data['data']['balance'] ?? $data['data']['amount'] ?? 0;
                    $currency = $data['data']['currency'] ?? 'RUB';

                    // Update account balance
                    $this->account->update([
                        'balance' => $balance,
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

                return [
                    'success' => false,
                    'error' => $data['comment'] ?? 'Failed to get balance',
                ];
            }

            return [
                'success' => false,
                'error' => 'Balansni olishda xatolik',
            ];
        } catch (\Exception $e) {
            Log::error('OnlinePBX get balance failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get call status
     */
    public function getCallStatus(string $callId): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'OnlinePBX account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->get($this->getBaseUrl().'/call/status.json', [
                    'call_id' => $callId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? $data,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['comment'] ?? 'Failed to get status',
                ];
            }

            return [
                'success' => false,
                'error' => 'Status olish xatosi',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get gateways (SIP trunks) from OnlinePBX
     */
    public function getGateways(): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'OnlinePBX account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->get($this->getBaseUrl().'/gateway/get.json');

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? [],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Gateway olish xatosi',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get users/extensions from OnlinePBX
     */
    public function getUsers(): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'OnlinePBX account not configured'];
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return $auth;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->get($this->getBaseUrl().'/user/get.json');

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? [],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Users olish xatosi',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get recording URL for a call
     */
    public function getRecordingUrl(string $callId): ?string
    {
        if (! $this->account) {
            return null;
        }

        try {
            $auth = $this->authenticate();
            if (! $auth['success']) {
                return null;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $this->getSessionAuthHeader(),
                ])
                ->get($this->getBaseUrl().'/record/get.json', [
                    'call_id' => $callId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    return $data['data']['url'] ?? $data['data']['record_url'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OnlinePBX get recording failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Link orphan call logs to leads based on phone number matching
     * This helps fix existing data where call logs weren't properly linked to leads
     */
    public function linkOrphanCallLogs(string $businessId): array
    {
        $linked = 0;
        $failed = 0;

        // Get all call logs without lead_id for this business
        $orphanCalls = CallLog::where('business_id', $businessId)
            ->whereNull('lead_id')
            ->get();

        Log::info('OnlinePBX: Linking orphan call logs', [
            'business_id' => $businessId,
            'orphan_count' => $orphanCalls->count(),
        ]);

        foreach ($orphanCalls as $call) {
            try {
                // Determine which phone number to use for matching
                $phoneNumber = $call->direction === CallLog::DIRECTION_INBOUND
                    ? $call->from_number
                    : $call->to_number;

                if (empty($phoneNumber)) {
                    $failed++;

                    continue;
                }

                $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

                // Find lead by phone number
                $lead = Lead::where('business_id', $businessId)
                    ->where(function ($query) use ($normalizedPhone, $phoneNumber) {
                        $last9 = substr($normalizedPhone, -9);
                        $query->where('phone', 'like', '%'.$last9)
                            ->orWhere('phone', $normalizedPhone)
                            ->orWhere('phone', $phoneNumber);
                    })
                    ->first();

                if ($lead) {
                    $call->update(['lead_id' => $lead->id]);
                    $linked++;
                } else {
                    // Create a new lead for this call
                    $source = $this->getOrCreatePhoneSource($businessId);
                    $lead = Lead::create([
                        'business_id' => $businessId,
                        'source_id' => $source?->id,
                        'name' => $this->formatPhoneDisplay($phoneNumber),
                        'phone' => $normalizedPhone,
                        'status' => 'new',
                        'estimated_value' => 0,
                        'notes' => $call->direction === CallLog::DIRECTION_INBOUND
                            ? 'Kiruvchi qo\'ng\'iroq orqali yaratildi (sinxronlash)'
                            : 'Chiquvchi qo\'ng\'iroq orqali yaratildi (sinxronlash)',
                        'last_contacted_at' => $call->started_at ?? now(),
                        'data' => [
                            'source' => $call->direction === CallLog::DIRECTION_INBOUND
                                ? 'phone_call'
                                : 'phone_call_outbound',
                            'call_count' => 1,
                            'first_call_at' => ($call->started_at ?? now())->toISOString(),
                            'last_call_at' => ($call->started_at ?? now())->toISOString(),
                            'synced_from_orphan' => true,
                        ],
                    ]);

                    $call->update(['lead_id' => $lead->id]);
                    $linked++;
                }
            } catch (\Exception $e) {
                Log::error('OnlinePBX: Failed to link orphan call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('OnlinePBX: Finished linking orphan call logs', [
            'linked' => $linked,
            'failed' => $failed,
        ]);

        return [
            'success' => true,
            'linked' => $linked,
            'failed' => $failed,
            'total' => $orphanCalls->count(),
        ];
    }
}
