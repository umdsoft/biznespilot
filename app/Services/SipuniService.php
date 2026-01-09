<?php

namespace App\Services;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\SipuniAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SipuniService
{
    protected ?SipuniAccount $account = null;

    /**
     * SipUni API base URL
     * Documented: https://help.sipuni.com/articles/134-182-113--sozdanie-zvonka-na-nomer-s-pomoshyu-api/
     */
    protected const API_BASE_URL = 'https://sipuni.com/api';

    /**
     * Set the SipUni account to use
     */
    public function setAccount(SipuniAccount $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Generate hash signature for SipUni API
     * SipUni uses MD5 hash of concatenated params + secret
     * Format: param1=value1+param2=value2+...+secret -> MD5
     */
    protected function generateHash(array $params, string $secret): string
    {
        // Sort params alphabetically by key
        ksort($params);

        // Concatenate with + delimiter
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = $value;
        }

        // Add secret at the end and hash
        $signString = implode('+', $parts) . '+' . $secret;

        Log::debug('SipUni hash generation', [
            'params' => $params,
            'signString' => $signString,
            'hash' => md5($signString),
        ]);

        return md5($signString);
    }

    /**
     * Test connection with credentials
     * SipUni doesn't have a dedicated "test" endpoint, so we verify by trying to get statistics
     */
    public function testConnection(string $userId, string $secret): array
    {
        try {
            Log::info('SipUni testing connection', ['user' => $userId]);

            // Try to get call statistics for today (this validates credentials)
            $today = Carbon::now()->format('d.m.Y');

            $params = [
                'from' => $today,
                'to' => $today,
                'user' => $userId,
            ];

            // Generate hash - SipUni uses specific order
            $hashString = $params['from'] . '+' . $params['to'] . '+' . $params['user'] . '+' . $secret;
            $params['hash'] = md5($hashString);

            Log::info('SipUni statistics request', [
                'url' => self::API_BASE_URL . '/statistic/export',
                'params' => $params,
                'hashString' => $hashString,
            ]);

            $response = Http::timeout(15)
                ->get(self::API_BASE_URL . '/statistic/export', $params);

            Log::info('SipUni API response', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            // Check response
            if ($response->successful()) {
                $body = $response->body();

                // If we get CSV data or empty result, credentials are valid
                if (str_contains($body, 'error') || str_contains($body, 'Error')) {
                    $data = $response->json();
                    if (isset($data['error'])) {
                        return [
                            'success' => false,
                            'error' => $this->translateError($data['error']),
                        ];
                    }
                }

                // Success - credentials are valid
                return [
                    'success' => true,
                    'message' => 'Ulanish muvaffaqiyatli',
                    'balance' => 0, // SipUni doesn't expose balance via public API
                ];
            }

            // Check for specific errors
            if ($response->status() === 403 || $response->status() === 401) {
                return [
                    'success' => false,
                    'error' => 'User ID yoki Secret noto\'g\'ri',
                ];
            }

            // Try alternative approach - make a test call request (without actually calling)
            // Just check if the endpoint accepts our credentials
            $callParams = [
                'phone' => '998900000000',
                'sipnumber' => '100',
                'user' => $userId,
            ];
            $callHashString = $callParams['phone'] . '+' . $callParams['sipnumber'] . '+' . $callParams['user'] . '+' . $secret;
            $callParams['hash'] = md5($callHashString);

            $callResponse = Http::timeout(10)
                ->get(self::API_BASE_URL . '/callback/call_number', $callParams);

            Log::info('SipUni callback test', [
                'status' => $callResponse->status(),
                'body' => $callResponse->body(),
            ]);

            if ($callResponse->successful()) {
                $data = $callResponse->json();

                // "wrong hash" means our hash calculation is wrong
                // "wrong user" means user ID is wrong
                // Any other response means credentials work
                if (isset($data['error'])) {
                    $error = strtolower($data['error']);
                    if (str_contains($error, 'hash') || str_contains($error, 'user')) {
                        return [
                            'success' => false,
                            'error' => $this->translateError($data['error']),
                        ];
                    }
                }

                // If we get here, credentials are valid (call might fail for other reasons)
                return [
                    'success' => true,
                    'message' => 'Ulanish muvaffaqiyatli',
                    'balance' => 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'SipUni API ga ulanib bo\'lmadi (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('SipUni connection test failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Translate SipUni error messages to Uzbek
     */
    protected function translateError(string $error): string
    {
        $translations = [
            'invalid user' => 'User ID noto\'g\'ri',
            'invalid hash' => 'Secret Key noto\'g\'ri',
            'user not found' => 'Foydalanuvchi topilmadi',
            'access denied' => 'Kirish taqiqlangan',
            'authentication failed' => 'Autentifikatsiya xatosi - User ID yoki Secret noto\'g\'ri',
            'wrong hash' => 'Secret Key noto\'g\'ri',
            'wrong user' => 'User ID noto\'g\'ri',
            'error' => 'API xatosi',
        ];

        $lowerError = strtolower($error);
        foreach ($translations as $key => $translation) {
            if (str_contains($lowerError, $key)) {
                return $translation;
            }
        }

        return $error;
    }

    /**
     * Make an outbound call
     * SipUni API: https://help.sipuni.com/articles/134-182-113--sozdanie-zvonka-na-nomer-s-pomoshyu-api/
     * Endpoint: /callback/call_number
     * Params: phone, sipnumber, user, reverse, antiaon, hash
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $sipNumber = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'SipUni account not configured'];
        }

        try {
            $phone = $this->formatPhoneNumber($toNumber);
            $sipnumber = $sipNumber ?? $this->account->extension ?? '100';
            $user = $this->account->api_key;

            // Create call log entry first
            $callLog = CallLog::create([
                'id' => Str::uuid(),
                'business_id' => $this->account->business_id,
                'lead_id' => $lead?->id,
                'user_id' => Auth::id(),
                'provider' => CallLog::PROVIDER_SIPUNI,
                'direction' => CallLog::DIRECTION_OUTBOUND,
                'from_number' => $this->account->caller_id,
                'to_number' => $phone,
                'status' => CallLog::STATUS_INITIATED,
                'started_at' => now(),
            ]);

            // SipUni callback API params
            // Hash format: phone+sipnumber+user+secret
            $hashString = $phone . '+' . $sipnumber . '+' . $user . '+' . $this->account->api_secret;
            $hash = md5($hashString);

            $params = [
                'phone' => $phone,
                'sipnumber' => $sipnumber,
                'user' => $user,
                'hash' => $hash,
                'reverse' => '0', // 0 = call internal first, then external
            ];

            Log::info('SipUni making call', [
                'url' => self::API_BASE_URL . '/callback/call_number',
                'params' => $params,
                'hashString' => $hashString,
            ]);

            $response = Http::timeout(30)
                ->get(self::API_BASE_URL . '/callback/call_number', $params);

            Log::info('SipUni call response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['error'])) {
                    $callLog->markAsFailed($data['error']);
                    return [
                        'success' => false,
                        'error' => $this->translateError($data['error']),
                    ];
                }

                // Success - SipUni returns call_id
                $callLog->update([
                    'provider_call_id' => $data['call_id'] ?? $data['id'] ?? null,
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
                    'provider_call_id' => $callLog->provider_call_id,
                    'message' => 'Qo\'ng\'iroq boshlandi',
                ];
            }

            $callLog->markAsFailed($response->body());

            return [
                'success' => false,
                'error' => 'Qo\'ng\'iroq qilib bo\'lmadi (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('SipUni make call failed', [
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
     * Handle webhook callback from SipUni
     */
    public function handleWebhook(array $data): void
    {
        $callId = $data['call_id'] ?? null;

        if (!$callId) {
            Log::warning('SipUni webhook: No call ID provided', $data);
            return;
        }

        $callLog = CallLog::where('provider_call_id', $callId)
            ->orWhere('id', $callId)
            ->first();

        if (!$callLog) {
            // Try to find by phone numbers for inbound calls
            if (isset($data['src_num']) && isset($data['dst_num'])) {
                $this->handleInboundCall($data);
            }
            return;
        }

        $event = $data['event'] ?? $data['state'] ?? null;

        switch ($event) {
            case 'Calling':
            case 'calling':
                $callLog->update(['status' => CallLog::STATUS_RINGING]);
                break;

            case 'Connected':
            case 'connected':
                $callLog->markAsAnswered();
                break;

            case 'Disconnected':
            case 'disconnected':
            case 'ended':
                $duration = $data['duration'] ?? $data['call_duration'] ?? 0;
                $status = $callLog->answered_at
                    ? CallLog::STATUS_COMPLETED
                    : CallLog::STATUS_NO_ANSWER;

                $callLog->update([
                    'status' => $status,
                    'duration' => $duration,
                    'ended_at' => now(),
                ]);

                // Record statistics
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'busy':
                $callLog->update(['status' => CallLog::STATUS_BUSY, 'ended_at' => now()]);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'failed':
                $callLog->markAsFailed($data['reason'] ?? 'Unknown');
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;
        }

        // Update recording URL if provided
        if (!empty($data['record_url']) || !empty($data['recording_url'])) {
            $callLog->update([
                'recording_url' => $data['record_url'] ?? $data['recording_url'],
            ]);
        }
    }

    /**
     * Handle inbound call (create new call log)
     */
    protected function handleInboundCall(array $data): void
    {
        // Find business by caller_id
        $account = SipuniAccount::where('caller_id', $data['dst_num'] ?? '')
            ->orWhere('api_key', $data['user'] ?? '')
            ->first();

        if (!$account) {
            Log::warning('SipUni inbound: Account not found', $data);
            return;
        }

        // Try to find lead by phone number
        $fromNumber = $this->formatPhoneNumber($data['src_num'] ?? '');
        $lead = Lead::where('business_id', $account->business_id)
            ->where(function ($q) use ($fromNumber) {
                $q->where('phone', $fromNumber)
                    ->orWhere('phone', 'like', '%' . substr($fromNumber, -9));
            })
            ->first();

        // Create call log for inbound call
        CallLog::create([
            'id' => Str::uuid(),
            'business_id' => $account->business_id,
            'lead_id' => $lead?->id,
            'provider' => CallLog::PROVIDER_SIPUNI,
            'provider_call_id' => $data['call_id'] ?? null,
            'direction' => CallLog::DIRECTION_INBOUND,
            'from_number' => $data['src_num'] ?? '',
            'to_number' => $data['dst_num'] ?? '',
            'status' => CallLog::STATUS_RINGING,
            'started_at' => now(),
            'metadata' => $data,
        ]);
    }

    /**
     * Get account balance
     */
    public function getBalance(): ?float
    {
        if (!$this->account) {
            return null;
        }

        try {
            $params = [
                'user' => $this->account->api_key,
            ];
            $params['hash'] = $this->generateHash($params, $this->account->api_secret);

            // Try GET first
            $response = Http::timeout(15)
                ->withQueryParameters($params)
                ->get(self::API_BASE_URL . '/statistic/balance');

            if ($response->successful()) {
                $data = $response->json();
                if (!isset($data['error'])) {
                    $balance = $data['balance'] ?? $data['money'] ?? 0;

                    $this->account->update([
                        'balance' => $balance,
                        'last_sync_at' => now(),
                    ]);

                    return $balance;
                }
            }

            // Try POST if GET failed
            $response = Http::timeout(15)
                ->asForm()
                ->post(self::API_BASE_URL . '/statistic/balance', $params);

            if ($response->successful()) {
                $data = $response->json();
                if (!isset($data['error'])) {
                    $balance = $data['balance'] ?? $data['money'] ?? 0;

                    $this->account->update([
                        'balance' => $balance,
                        'last_sync_at' => now(),
                    ]);

                    return $balance;
                }
            }
        } catch (\Exception $e) {
            Log::error('SipUni get balance failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get call history from SipUni
     */
    public function getCallHistory(Carbon $from, Carbon $to): array
    {
        if (!$this->account) {
            return [];
        }

        try {
            $params = [
                'user' => $this->account->api_key,
                'from' => $from->format('d.m.Y'),
                'to' => $to->format('d.m.Y'),
            ];
            $params['hash'] = $this->generateSignature($params);

            $response = Http::timeout(30)
                ->get(self::API_BASE_URL . '/statistic/calls', $params);

            if ($response->successful()) {
                return $response->json()['calls'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error('SipUni get call history failed', ['error' => $e->getMessage()]);
        }

        return [];
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
     * Generate SipUni API signature
     */
    protected function generateSignature(array $params): string
    {
        ksort($params);
        $signString = '';
        foreach ($params as $key => $value) {
            $signString .= $key . '=' . $value;
        }
        $signString .= $this->account->api_secret;

        return md5($signString);
    }

    /**
     * Format phone number
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if needed (Uzbekistan)
        if (strlen($phone) === 9) {
            $phone = '998' . $phone;
        }

        return $phone;
    }
}
