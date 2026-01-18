<?php

namespace App\Services;

use App\Models\EskizAccount;
use App\Models\Lead;
use App\Models\SmsDailyStat;
use App\Models\SmsMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EskizSmsService
{
    private const API_BASE_URL = 'https://notify.eskiz.uz/api';

    private ?EskizAccount $account = null;

    public function __construct(?EskizAccount $account = null)
    {
        $this->account = $account;
    }

    /**
     * Set the Eskiz account to use
     */
    public function setAccount(EskizAccount $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Authenticate with Eskiz and get access token
     */
    public function authenticate(string $email, string $password): array
    {
        try {
            Log::info('Eskiz auth attempt', ['email' => $email, 'url' => self::API_BASE_URL.'/auth/login']);

            // Try with URL-encoded form data first (most common)
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->asForm()
                ->post(self::API_BASE_URL.'/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);

            Log::info('Eskiz auth response (asForm)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $data = $response->json();

            // If asForm fails, try with multipart form-data
            if (! $response->successful() || (isset($data['status']) && $data['status'] === 'error')) {
                Log::info('Eskiz: asForm failed, trying multipart');

                $response = Http::timeout(30)
                    ->withoutVerifying()
                    ->asMultipart()
                    ->post(self::API_BASE_URL.'/auth/login', [
                        [
                            'name' => 'email',
                            'contents' => $email,
                        ],
                        [
                            'name' => 'password',
                            'contents' => $password,
                        ],
                    ]);

                Log::info('Eskiz auth response (multipart)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $data = $response->json();
            }

            // Check for error in response
            if (isset($data['status']) && $data['status'] === 'error') {
                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Noto\'g\'ri email yoki parol',
                ];
            }

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Autentifikatsiya xatosi: '.($data['message'] ?? 'Noto\'g\'ri email yoki parol'),
                ];
            }

            // Token can be in different locations
            $token = $data['data']['token'] ?? $data['token'] ?? null;

            if (! $token) {
                Log::warning('Eskiz: Token not found in response', ['data' => $data]);

                return [
                    'success' => false,
                    'error' => 'Token javobda topilmadi',
                ];
            }

            Log::info('Eskiz auth successful', ['token_length' => strlen($token)]);

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => Carbon::now()->addDays(30),
            ];
        } catch (\Exception $e) {
            Log::error('Eskiz auth exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'error' => 'Tarmoq xatosi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Refresh token if expired
     */
    public function refreshTokenIfNeeded(): bool
    {
        if (! $this->account) {
            return false;
        }

        if ($this->account->isTokenValid()) {
            return true;
        }

        // Re-authenticate to get new token
        $result = $this->authenticate(
            $this->account->email,
            $this->account->getDecryptedPassword()
        );

        if (! $result['success']) {
            $this->account->update([
                'last_error' => $result['error'],
            ]);

            return false;
        }

        $this->account->update([
            'access_token' => $result['token'],
            'token_expires_at' => $result['expires_at'],
            'last_error' => null,
        ]);

        return true;
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
        if (! $this->account) {
            return ['success' => false, 'error' => 'Eskiz hisobi sozlanmagan'];
        }

        if (! $this->refreshTokenIfNeeded()) {
            return ['success' => false, 'error' => 'Token yangilab bo\'lmadi'];
        }

        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhone($phone);

            if (! $normalizedPhone) {
                return ['success' => false, 'error' => 'Noto\'g\'ri telefon raqami formati'];
            }

            // Calculate SMS parts
            $partsCount = $this->calculateSmsParts($message);

            $response = Http::timeout(30)
                ->withToken($this->account->access_token)
                ->asForm()
                ->post(self::API_BASE_URL.'/message/sms/send', [
                    'mobile_phone' => $normalizedPhone,
                    'message' => $message,
                    'from' => $this->account->sender_name,
                ]);

            $data = $response->json();

            // Create message log
            $smsMessage = SmsMessage::create([
                'business_id' => $this->account->business_id,
                'eskiz_account_id' => $this->account->id,
                'lead_id' => $lead?->id,
                'sent_by' => auth()->id(),
                'template_id' => $templateId,
                'phone' => $normalizedPhone,
                'message' => $message,
                'eskiz_message_id' => $data['id'] ?? null,
                'status' => $response->successful() ? SmsMessage::STATUS_SENT : SmsMessage::STATUS_FAILED,
                'parts_count' => $partsCount,
                'error_message' => ! $response->successful() ? ($data['message'] ?? 'Noma\'lum xato') : null,
                'sent_at' => $response->successful() ? now() : null,
            ]);

            // Update daily stats
            $this->updateDailyStats($response->successful(), $partsCount);

            if (! $response->successful()) {
                Log::error('Eskiz SMS send failed', [
                    'phone' => $normalizedPhone,
                    'response' => $data,
                ]);

                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'SMS yuborib bo\'lmadi',
                    'message_id' => $smsMessage->id,
                ];
            }

            Log::info('SMS sent successfully', [
                'message_id' => $smsMessage->id,
                'phone' => $normalizedPhone,
            ]);

            return [
                'success' => true,
                'message_id' => $smsMessage->id,
                'eskiz_id' => $data['id'] ?? null,
                'parts_count' => $partsCount,
            ];

        } catch (\Exception $e) {
            Log::error('Eskiz SMS exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
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
            return '998'.$normalized;
        }

        if (strlen($normalized) === 12 && str_starts_with($normalized, '998')) {
            // Format: 998XXXXXXXXX
            return $normalized;
        }

        if (strlen($normalized) === 13 && str_starts_with($normalized, '+998')) {
            // Format: +998XXXXXXXXX (already has +)
            return substr($normalized, 1);
        }

        // Invalid format
        return null;
    }

    /**
     * Get account balance
     */
    public function getBalance(): ?int
    {
        if (! $this->account || ! $this->refreshTokenIfNeeded()) {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withToken($this->account->access_token)
                ->get(self::API_BASE_URL.'/auth/user');

            if ($response->successful()) {
                $data = $response->json();
                $balance = (int) ($data['data']['balance'] ?? 0);

                $this->account->update([
                    'balance' => $balance,
                    'last_sync_at' => now(),
                    'last_error' => null,
                ]);

                return $balance;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get Eskiz balance', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get message status from Eskiz
     */
    public function getMessageStatus(string $eskizMessageId): ?string
    {
        if (! $this->account || ! $this->refreshTokenIfNeeded()) {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withToken($this->account->access_token)
                ->get(self::API_BASE_URL.'/message/sms/status/'.$eskizMessageId);

            if ($response->successful()) {
                return $response->json()['status'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get SMS status', ['error' => $e->getMessage()]);
        }

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
            if ($length <= 70) {
                return 1;
            }

            return (int) ceil($length / 67);
        } else {
            // GSM-7: 160 chars per part, 153 for multipart
            if ($length <= 160) {
                return 1;
            }

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

    /**
     * Get daily statistics for charting
     */
    public function getDailyStatistics(string $businessId, int $days = 30): array
    {
        return SmsDailyStat::where('business_id', $businessId)
            ->where('stat_date', '>=', Carbon::now()->subDays($days))
            ->orderBy('stat_date')
            ->get()
            ->map(function ($stat) {
                return [
                    'date' => $stat->stat_date->format('d.m'),
                    'sent' => $stat->total_sent,
                    'delivered' => $stat->delivered,
                    'failed' => $stat->failed,
                ];
            })
            ->toArray();
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(string $email, string $password): array
    {
        return $this->authenticate($email, $password);
    }
}
