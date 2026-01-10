<?php

namespace App\Services;

use App\Models\CallDailyStat;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\PbxAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PbxService
{
    protected ?PbxAccount $account = null;

    /**
     * Set the PBX account to use
     */
    public function setAccount(PbxAccount $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $apiUrl, string $apiKey, ?string $apiSecret = null): array
    {
        try {
            $url = rtrim($apiUrl, '/') . '/status';

            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'X-API-Key' => $apiKey,
                ])
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Ulanish muvaffaqiyatli',
                ];
            }

            // Try alternative auth method
            $response = Http::timeout(15)
                ->withBasicAuth($apiKey, $apiSecret ?? '')
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
            Log::error('PBX connection test failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make an outbound call
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'PBX account not configured'];
        }

        try {
            $fromNumber = $fromNumber ?? $this->account->caller_id;

            // Create call log entry first
            $callLog = CallLog::create([
                'id' => Str::uuid(),
                'business_id' => $this->account->business_id,
                'lead_id' => $lead?->id,
                'user_id' => Auth::id(),
                'provider' => CallLog::PROVIDER_PBX,
                'direction' => CallLog::DIRECTION_OUTBOUND,
                'from_number' => $fromNumber,
                'to_number' => $this->formatPhoneNumber($toNumber),
                'status' => CallLog::STATUS_INITIATED,
                'started_at' => now(),
            ]);

            // Make API call to PBX
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->account->api_key,
                    'X-API-Key' => $this->account->api_key,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->account->api_url . 'calls/originate', [
                    'from' => $fromNumber,
                    'to' => $this->formatPhoneNumber($toNumber),
                    'extension' => $this->account->extension,
                    'caller_id' => $fromNumber,
                    'variables' => [
                        'CALL_ID' => $callLog->id,
                        'LEAD_ID' => $lead?->id,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

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
                'error' => 'Qo\'ng\'iroq qilib bo\'lmadi: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('PBX make call failed', [
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
     * Hangup a call
     */
    public function hangupCall(string $callId): array
    {
        if (!$this->account) {
            return ['success' => false, 'error' => 'PBX account not configured'];
        }

        try {
            $callLog = CallLog::find($callId);

            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->account->api_key,
                ])
                ->post($this->account->api_url . 'calls/' . ($callLog?->provider_call_id ?? $callId) . '/hangup');

            if ($response->successful() && $callLog) {
                $callLog->update([
                    'status' => CallLog::STATUS_COMPLETED,
                    'ended_at' => now(),
                ]);
            }

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Qo\'ng\'iroq tugatildi' : 'Xatolik',
            ];
        } catch (\Exception $e) {
            Log::error('PBX hangup call failed', ['error' => $e->getMessage()]);
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
        if (!$this->account) {
            return ['success' => false, 'error' => 'PBX account not configured'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->account->api_key,
                ])
                ->get($this->account->api_url . 'calls/' . $callId . '/status');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
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
     * Handle webhook callback from PBX
     */
    public function handleWebhook(array $data): void
    {
        $callId = $data['call_id'] ?? $data['CALL_ID'] ?? null;

        if (!$callId) {
            Log::warning('PBX webhook: No call ID provided', $data);
            return;
        }

        $callLog = CallLog::where('provider_call_id', $callId)
            ->orWhere('id', $callId)
            ->first();

        if (!$callLog) {
            Log::warning('PBX webhook: Call not found', ['call_id' => $callId]);
            return;
        }

        $event = $data['event'] ?? $data['status'] ?? null;

        switch ($event) {
            case 'ringing':
            case 'RINGING':
                $callLog->update(['status' => CallLog::STATUS_RINGING]);
                break;

            case 'answered':
            case 'ANSWER':
                $callLog->markAsAnswered();
                break;

            case 'hangup':
            case 'HANGUP':
            case 'completed':
                $duration = $data['duration'] ?? $data['billsec'] ?? 0;
                $callLog->markAsCompleted($duration);

                // Record statistics
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'failed':
            case 'FAILED':
                $reason = $data['reason'] ?? $data['cause'] ?? 'Unknown';
                $callLog->markAsFailed($reason);

                // Record statistics
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'busy':
            case 'BUSY':
                $callLog->update(['status' => CallLog::STATUS_BUSY, 'ended_at' => now()]);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;

            case 'no_answer':
            case 'NOANSWER':
                $callLog->update(['status' => CallLog::STATUS_NO_ANSWER, 'ended_at' => now()]);
                CallDailyStat::recordCall($callLog->business_id, $callLog);
                break;
        }

        // Update recording URL if provided
        if (!empty($data['recording_url'])) {
            $callLog->update(['recording_url' => $data['recording_url']]);
        }
    }

    /**
     * Get account balance
     */
    public function getBalance(): ?int
    {
        if (!$this->account) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->account->api_key,
                ])
                ->get($this->account->api_url . 'account/balance');

            if ($response->successful()) {
                $data = $response->json();
                $balance = $data['balance'] ?? $data['credits'] ?? 0;

                $this->account->update([
                    'balance' => $balance,
                    'last_sync_at' => now(),
                ]);

                return $balance;
            }
        } catch (\Exception $e) {
            Log::error('PBX get balance failed', ['error' => $e->getMessage()]);
        }

        return null;
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
