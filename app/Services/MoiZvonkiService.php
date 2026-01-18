<?php

namespace App\Services;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MoiZvonkiAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoiZvonkiService
{
    protected ?MoiZvonkiAccount $account = null;

    /**
     * Set the MoiZvonki account to use
     */
    public function setAccount(MoiZvonkiAccount $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get API base URL
     */
    protected function getBaseUrl(): string
    {
        if (! $this->account) {
            return '';
        }

        return $this->account->getApiBaseUrl();
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $apiUrl, string $apiKey, string $email): array
    {
        try {
            // Normalize URL
            if (! str_starts_with($apiUrl, 'http')) {
                $apiUrl = 'https://'.$apiUrl;
            }
            $apiUrl = rtrim($apiUrl, '/');

            // Try to get account info or events to verify connection
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl.'/api/v1/crm/events', [
                    'email' => $email,
                    'api_key' => $apiKey,
                    'date_from' => Carbon::now()->subDay()->timestamp,
                    'date_to' => Carbon::now()->timestamp,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check for error in response
                if (isset($data['error'])) {
                    return [
                        'success' => false,
                        'error' => $data['error'],
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'Ulanish muvaffaqiyatli',
                ];
            }

            // Try alternative endpoint format
            $response = Http::timeout(15)
                ->get($apiUrl.'/api/v1/account/info', [
                    'email' => $email,
                    'api_key' => $apiKey,
                ]);

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
            Log::error('MoiZvonki connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Handle incoming webhook from MoiZvonki
     *
     * MoiZvonki sends call events via webhook when configured
     */
    public function handleWebhook(array $data): void
    {
        try {
            Log::info('MoiZvonki webhook data', $data);

            // MoiZvonki webhook payload structure:
            // - type: call_started, call_answered, call_ended
            // - call_id: unique call identifier
            // - direction: in/out
            // - from: caller number
            // - to: destination number
            // - duration: call duration in seconds
            // - record_url: recording URL (if available)
            // - status: call result status
            // - employee_id: employee identifier
            // - contact_name: contact name if known

            $eventType = $data['type'] ?? $data['event'] ?? $data['event_type'] ?? null;
            $callId = $data['call_id'] ?? $data['id'] ?? $data['uuid'] ?? null;

            if (! $callId) {
                Log::warning('MoiZvonki webhook: No call ID', $data);

                return;
            }

            // Try to find business from account or data
            $businessId = $data['business_id'] ?? $this->account?->business_id ?? null;

            if (! $businessId) {
                Log::warning('MoiZvonki webhook: No business ID', $data);

                return;
            }

            // Determine direction
            $direction = $this->parseDirection($data['direction'] ?? $data['call_direction'] ?? 'in');

            // Parse phone numbers
            $fromNumber = $this->normalizePhoneNumber($data['from'] ?? $data['caller'] ?? $data['src'] ?? '');
            $toNumber = $this->normalizePhoneNumber($data['to'] ?? $data['called'] ?? $data['dst'] ?? '');

            // For incoming calls, the customer number is "from"
            // For outgoing calls, the customer number is "to"
            $customerNumber = $direction === CallLog::DIRECTION_INBOUND ? $fromNumber : $toNumber;

            // Find or create call log
            $callLog = CallLog::where('provider_call_id', $callId)
                ->where('provider', 'moizvonki')
                ->first();

            if (! $callLog) {
                // Create new call log
                $callLog = CallLog::create([
                    'id' => Str::uuid(),
                    'business_id' => $businessId,
                    'provider' => 'moizvonki',
                    'provider_call_id' => $callId,
                    'direction' => $direction,
                    'from_number' => $fromNumber,
                    'to_number' => $toNumber,
                    'status' => CallLog::STATUS_INITIATED,
                    'started_at' => isset($data['start_time'])
                        ? Carbon::createFromTimestamp($data['start_time'])
                        : now(),
                    'metadata' => $data,
                ]);

                // Try to find or create lead for incoming calls
                if ($direction === CallLog::DIRECTION_INBOUND && $customerNumber) {
                    $this->findOrCreateLeadForCall($callLog, $customerNumber, $businessId);
                }
            }

            // Process event based on type
            switch ($eventType) {
                case 'call_started':
                case 'calling':
                case 'ringing':
                    $callLog->update(['status' => CallLog::STATUS_RINGING]);
                    break;

                case 'call_answered':
                case 'answered':
                case 'answer':
                    $callLog->markAsAnswered();
                    break;

                case 'call_ended':
                case 'hangup':
                case 'ended':
                case 'completed':
                    $duration = (int) ($data['duration'] ?? $data['billsec'] ?? $data['talk_time'] ?? 0);
                    $status = $this->parseCallStatus($data['status'] ?? $data['disposition'] ?? 'completed', $duration);

                    $callLog->update([
                        'status' => $status,
                        'duration' => $duration,
                        'ended_at' => isset($data['end_time'])
                            ? Carbon::createFromTimestamp($data['end_time'])
                            : now(),
                    ]);

                    // Update recording URL if available
                    if (! empty($data['record_url']) || ! empty($data['recording_url'])) {
                        $callLog->update([
                            'recording_url' => $data['record_url'] ?? $data['recording_url'],
                        ]);
                    }

                    // Record statistics
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                case 'missed':
                case 'no_answer':
                    $callLog->update([
                        'status' => CallLog::STATUS_NO_ANSWER,
                        'ended_at' => now(),
                    ]);
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                case 'busy':
                    $callLog->update([
                        'status' => CallLog::STATUS_BUSY,
                        'ended_at' => now(),
                    ]);
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                case 'failed':
                case 'error':
                    $reason = $data['reason'] ?? $data['cause'] ?? 'Unknown';
                    $callLog->markAsFailed($reason);
                    CallDailyStat::recordCall($businessId, $callLog);
                    break;

                default:
                    // Update status based on data if type is not specified
                    if (isset($data['duration']) && (int) $data['duration'] > 0) {
                        $duration = (int) $data['duration'];
                        $callLog->update([
                            'status' => CallLog::STATUS_COMPLETED,
                            'duration' => $duration,
                            'ended_at' => now(),
                        ]);

                        if (! empty($data['record_url']) || ! empty($data['recording_url'])) {
                            $callLog->update([
                                'recording_url' => $data['record_url'] ?? $data['recording_url'],
                            ]);
                        }

                        CallDailyStat::recordCall($businessId, $callLog);
                    }
                    break;
            }

        } catch (\Exception $e) {
            Log::error('MoiZvonki webhook error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Sync call history from MoiZvonki
     */
    public function syncCallHistory(?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'MoiZvonki account not configured'];
        }

        try {
            $fromDate = $fromDate ?? Carbon::now()->subDays(7);
            $toDate = $toDate ?? Carbon::now();

            $response = Http::timeout(60)
                ->post($this->getBaseUrl().'/api/v1/crm/events', [
                    'email' => $this->account->email,
                    'api_key' => $this->account->api_key,
                    'date_from' => $fromDate->timestamp,
                    'date_to' => $toDate->timestamp,
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'API xatosi: '.$response->status(),
                ];
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => $data['error'],
                ];
            }

            $events = $data['events'] ?? $data['data'] ?? $data ?? [];
            $synced = 0;
            $created = 0;

            foreach ($events as $event) {
                $callId = $event['call_id'] ?? $event['id'] ?? null;

                if (! $callId) {
                    continue;
                }

                // Check if call already exists
                $exists = CallLog::where('provider_call_id', $callId)
                    ->where('provider', 'moizvonki')
                    ->exists();

                if (! $exists) {
                    // Process as webhook event
                    $event['business_id'] = $this->account->business_id;
                    $this->handleWebhook($event);
                    $created++;
                }

                $synced++;
            }

            // Update last sync time
            $this->account->update(['last_sync_at' => now()]);

            return [
                'success' => true,
                'synced' => $synced,
                'created' => $created,
            ];
        } catch (\Exception $e) {
            Log::error('MoiZvonki sync call history failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Sinxronizatsiya xatosi: '.$e->getMessage(),
            ];
        }
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
     * Parse call direction from MoiZvonki format
     */
    protected function parseDirection(string $direction): string
    {
        $direction = strtolower($direction);

        if (in_array($direction, ['in', 'incoming', 'inbound', '1'])) {
            return CallLog::DIRECTION_INBOUND;
        }

        return CallLog::DIRECTION_OUTBOUND;
    }

    /**
     * Parse call status from MoiZvonki format
     */
    protected function parseCallStatus(string $status, int $duration): string
    {
        $status = strtolower($status);

        if (in_array($status, ['answered', 'answer', 'completed', 'success'])) {
            return $duration > 0 ? CallLog::STATUS_COMPLETED : CallLog::STATUS_NO_ANSWER;
        }

        if (in_array($status, ['no_answer', 'noanswer', 'missed', 'unanswered'])) {
            return CallLog::STATUS_NO_ANSWER;
        }

        if (in_array($status, ['busy', 'user_busy'])) {
            return CallLog::STATUS_BUSY;
        }

        if (in_array($status, ['failed', 'error', 'congestion'])) {
            return CallLog::STATUS_FAILED;
        }

        // Default based on duration
        return $duration > 0 ? CallLog::STATUS_COMPLETED : CallLog::STATUS_NO_ANSWER;
    }

    /**
     * Normalize phone number to standard format
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading + if present
        $phone = ltrim($phone, '+');

        // Handle Uzbekistan numbers
        if (strlen($phone) === 9 && preg_match('/^[0-9]/', $phone)) {
            $phone = '998'.$phone;
        }

        // Remove leading 8 for Russian/CIS numbers
        if (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            $phone = '7'.substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Find or create lead for incoming call
     */
    protected function findOrCreateLeadForCall(CallLog $callLog, string $phoneNumber, string $businessId): void
    {
        // Try to find existing lead by phone number
        $lead = Lead::where('business_id', $businessId)
            ->where(function ($query) use ($phoneNumber) {
                $query->where('phone', 'LIKE', '%'.substr($phoneNumber, -9))
                    ->orWhere('phone', $phoneNumber)
                    ->orWhere('phone', '+'.$phoneNumber);
            })
            ->first();

        if ($lead) {
            // Link call to existing lead
            $callLog->update(['lead_id' => $lead->id]);
            $lead->update(['last_contacted_at' => now()]);
        } else {
            // Create new lead from incoming call
            $leadSource = LeadSource::where('business_id', $businessId)
                ->where('slug', 'phone')
                ->first();

            if (! $leadSource) {
                $leadSource = LeadSource::where('business_id', $businessId)
                    ->where('type', 'offline')
                    ->first();
            }

            $newLead = Lead::create([
                'id' => Str::uuid(),
                'business_id' => $businessId,
                'lead_source_id' => $leadSource?->id,
                'name' => 'Kiruvchi qo\'ng\'iroq',
                'phone' => '+'.$phoneNumber,
                'status' => 'new',
                'last_contacted_at' => now(),
                'metadata' => [
                    'created_from' => 'moizvonki_incoming_call',
                    'call_id' => $callLog->id,
                ],
            ]);

            $callLog->update(['lead_id' => $newLead->id]);

            Log::info('Created lead from MoiZvonki incoming call', [
                'lead_id' => $newLead->id,
                'phone' => $phoneNumber,
            ]);
        }
    }
}
