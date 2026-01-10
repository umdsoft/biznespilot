<?php

namespace App\Services;

use App\Models\PlayMobileAccount;
use App\Models\Lead;
use App\Models\SmsMessage;
use App\Models\SmsDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlayMobileSmsService
{
    private ?PlayMobileAccount $account = null;

    public function __construct(?PlayMobileAccount $account = null)
    {
        $this->account = $account;
    }

    /**
     * Set the PlayMobile account to use
     */
    public function setAccount(PlayMobileAccount $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $login, string $password, string $apiUrl = null): array
    {
        try {
            $apiUrl = $apiUrl ?: 'https://send.smsxabar.uz/broker-api/send';

            Log::info('PlayMobile test connection', ['login' => $login, 'url' => $apiUrl]);

            // Send a test request with empty messages to verify credentials
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Authorization' => 'Basic ' . base64_encode($login . ':' . $password),
                ])
                ->post($apiUrl, [
                    'messages' => [],
                ]);

            Log::info('PlayMobile test response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // 200 or 400 with specific error means credentials are valid
            // 401 means invalid credentials
            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'error' => 'Noto\'g\'ri login yoki parol',
                ];
            }

            return [
                'success' => true,
            ];
        } catch (\Exception $e) {
            Log::error('PlayMobile test connection error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS to a phone number
     */
    public function sendSms(
        string $phone,
        string $message,
        ?Lead $lead = null,
        ?string $templateId = null
    ): array {
        if (!$this->account) {
            return ['success' => false, 'error' => 'PlayMobile hisobi sozlanmagan'];
        }

        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhone($phone);

            if (!$normalizedPhone) {
                return ['success' => false, 'error' => 'Noto\'g\'ri telefon raqami formati'];
            }

            // Calculate SMS parts
            $partsCount = $this->calculateSmsParts($message);

            // Generate unique message ID
            $messageId = Str::uuid()->toString();

            // Build request payload
            $payload = [
                'messages' => [
                    [
                        'recipient' => $normalizedPhone,
                        'message-id' => substr($messageId, 0, 40),
                        'sms' => [
                            'originator' => $this->account->originator,
                            'content' => [
                                'text' => $message,
                            ],
                        ],
                    ],
                ],
            ];

            Log::info('PlayMobile sending SMS', [
                'phone' => $normalizedPhone,
                'message_id' => $messageId,
            ]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Authorization' => $this->account->getBasicAuthHeader(),
                ])
                ->post($this->account->api_url, $payload);

            $responseBody = $response->body();

            Log::info('PlayMobile send response', [
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            // Check response
            $isSuccess = $response->successful() && str_contains($responseBody, 'Request is received');

            // Create message log
            $smsMessage = SmsMessage::create([
                'business_id' => $this->account->business_id,
                'provider' => 'playmobile',
                'playmobile_account_id' => $this->account->id,
                'lead_id' => $lead?->id,
                'sent_by' => auth()->id(),
                'template_id' => $templateId,
                'phone' => $normalizedPhone,
                'message' => $message,
                'eskiz_message_id' => $messageId, // Using same field for external ID
                'status' => $isSuccess ? SmsMessage::STATUS_SENT : SmsMessage::STATUS_FAILED,
                'parts_count' => $partsCount,
                'error_message' => !$isSuccess ? $this->parseError($response) : null,
                'sent_at' => $isSuccess ? now() : null,
            ]);

            // Update daily stats
            $this->updateDailyStats($isSuccess, $partsCount);

            if (!$isSuccess) {
                return [
                    'success' => false,
                    'error' => $this->parseError($response),
                    'message_id' => $smsMessage->id,
                ];
            }

            return [
                'success' => true,
                'message_id' => $smsMessage->id,
                'external_id' => $messageId,
                'parts_count' => $partsCount,
            ];

        } catch (\Exception $e) {
            Log::error('PlayMobile SMS exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse error from response
     */
    private function parseError($response): string
    {
        $body = $response->body();

        // Try to parse JSON error
        $data = json_decode($body, true);
        if ($data && isset($data['error_description'])) {
            return $data['error_description'];
        }

        if ($response->status() === 401) {
            return 'Autentifikatsiya xatosi';
        }

        if ($response->status() === 400) {
            return 'Noto\'g\'ri so\'rov formati';
        }

        return 'SMS yuborib bo\'lmadi: ' . $body;
    }

    /**
     * Normalize phone number for Uzbekistan
     */
    private function normalizePhone(string $phone): ?string
    {
        // Remove all non-digits
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // Handle different formats
        if (strlen($normalized) === 9 && str_starts_with($normalized, '9')) {
            // Format: 9XXXXXXXX
            return '998' . $normalized;
        }

        if (strlen($normalized) === 12 && str_starts_with($normalized, '998')) {
            // Format: 998XXXXXXXXX
            return $normalized;
        }

        // Invalid format
        return null;
    }

    /**
     * Calculate SMS parts based on message content
     */
    public function calculateSmsParts(string $message): int
    {
        $length = mb_strlen($message);

        // Check if message contains non-ASCII (Cyrillic, etc.)
        $isUnicode = preg_match('/[^\x00-\x7F]/', $message);

        if ($isUnicode) {
            // Unicode: 70 chars per part, 67 for multipart
            if ($length <= 70) return 1;
            return (int) ceil($length / 67);
        } else {
            // GSM-7: 160 chars per part, 153 for multipart
            if ($length <= 160) return 1;
            return (int) ceil($length / 153);
        }
    }

    /**
     * Update daily statistics
     */
    private function updateDailyStats(bool $success, int $partsCount): void
    {
        $stat = SmsDailyStat::firstOrCreate(
            [
                'business_id' => $this->account->business_id,
                'stat_date' => today(),
            ],
            [
                'total_sent' => 0,
                'delivered' => 0,
                'failed' => 0,
                'pending' => 0,
                'parts_used' => 0,
            ]
        );

        $stat->increment('total_sent');
        $stat->increment('parts_used', $partsCount);

        if ($success) {
            $stat->increment('pending');
        } else {
            $stat->increment('failed');
        }
    }

    /**
     * Get SMS statistics for a business
     */
    public function getStatistics(string $businessId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $stats = SmsDailyStat::where('business_id', $businessId)
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(total_sent), 0) as total_sent,
                COALESCE(SUM(delivered), 0) as delivered,
                COALESCE(SUM(failed), 0) as failed,
                COALESCE(SUM(pending), 0) as pending,
                COALESCE(SUM(parts_used), 0) as parts_used
            ')
            ->first();

        $totalSent = (int) ($stats->total_sent ?? 0);
        $delivered = (int) ($stats->delivered ?? 0);

        return [
            'total_sent' => $totalSent,
            'delivered' => $delivered,
            'failed' => (int) ($stats->failed ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'parts_used' => (int) ($stats->parts_used ?? 0),
            'delivery_rate' => $totalSent > 0
                ? round(($delivered / $totalSent) * 100, 1)
                : 0,
        ];
    }
}
