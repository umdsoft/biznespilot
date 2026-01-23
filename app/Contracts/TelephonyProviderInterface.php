<?php

namespace App\Contracts;

use App\Models\CallLog;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Unified interface for all telephony providers (Utel, OnlinePBX, SipUni, etc.)
 * This interface provides provider-agnostic methods for telephony operations.
 */
interface TelephonyProviderInterface
{
    /**
     * Get provider identifier name
     * Used for storing in database and logs
     *
     * @return string Provider name (e.g., 'utel', 'onlinepbx', 'sipuni')
     */
    public function getName(): string;

    /**
     * Get display name for UI
     *
     * @return string Human-readable provider name
     */
    public function getDisplayName(): string;

    /**
     * Check if provider is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Authenticate with the provider API
     *
     * @return array{success: bool, token?: string, error?: string}
     */
    public function authenticate(): array;

    /**
     * Test connection with given credentials
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function testConnection(array $credentials): array;

    /**
     * Parse incoming webhook data into a standardized format
     *
     * @return array{
     *   event: string,
     *   call_id: string|null,
     *   direction: string,
     *   from_number: string,
     *   to_number: string,
     *   status: string,
     *   duration: int,
     *   recording_url: string|null,
     *   metadata: array
     * }
     */
    public function parseWebhook(Request $request): array;

    /**
     * Verify webhook signature/authenticity
     *
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(Request $request, ?string $secret = null): bool;

    /**
     * Handle incoming webhook - process and store call data
     */
    public function handleWebhook(array $data): void;

    /**
     * Make an outbound call
     *
     * @return array{success: bool, call_id?: string, provider_call_id?: string, message?: string, error?: string}
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array;

    /**
     * Hang up an active call
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function hangupCall(string $callId): array;

    /**
     * Get call status
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getCallStatus(string $callId): array;

    /**
     * Get call history from provider API
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array;

    /**
     * Sync call history from provider API to local database
     *
     * @return array{success: bool, synced?: int, created?: int, updated?: int, error?: string}
     */
    public function syncCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array;

    /**
     * Get account balance
     *
     * @return array{success: bool, data?: array{balance: float, currency: string}, error?: string}
     */
    public function getBalance(): array;

    /**
     * Get call recording URL
     *
     * @return string|null Recording URL or null if not available
     */
    public function getRecordingUrl(string $callId): ?string;

    /**
     * Get call recording as stream/content
     *
     * @return array{success: bool, content?: string, mime_type?: string, error?: string}
     */
    public function getRecordingContent(string $callId): array;

    /**
     * Get SIP users/extensions from provider
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getUsers(): array;

    /**
     * Configure webhook URL in provider system
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function configureWebhook(string $webhookUrl): array;

    /**
     * Get statistics for a business
     */
    public function getStatistics(string $businessId, ?Carbon $startDate = null, ?Carbon $endDate = null): array;

    /**
     * Normalize phone number to standard format
     *
     * @return string Normalized phone number (e.g., 998901234567)
     */
    public function normalizePhoneNumber(string $phone): string;

    /**
     * Link orphan call logs to leads based on phone number matching
     *
     * @return array{success: bool, linked: int, failed: int, total: int}
     */
    public function linkOrphanCallLogs(string $businessId): array;

    /**
     * Get webhook URL for this provider
     */
    public function getWebhookUrl(?string $businessId = null): string;
}
