<?php

namespace App\Contracts;

use App\Models\Lead;
use App\Models\PbxAccount;
use Carbon\Carbon;

/**
 * Unified interface for PBX service integrations
 * All PBX services (OnlinePBX, SipUni, etc.) should implement this interface
 * to ensure consistent behavior and data structure
 */
interface PbxServiceInterface
{
    /**
     * Set the PBX account to use for this service
     */
    public function setAccount(PbxAccount $account): self;

    /**
     * Test connection to the PBX service
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function testConnection(string $apiUrl, string $apiKey, ?string $keyId = null): array;

    /**
     * Handle incoming webhook from PBX service
     * This should:
     * 1. Parse the webhook data
     * 2. Create or find the associated lead
     * 3. Create call log entry
     * 4. Update call status as needed
     */
    public function handleWebhook(array $data): void;

    /**
     * Make an outbound call
     *
     * @return array{success: bool, call_id?: string, provider_call_id?: string, error?: string}
     */
    public function makeCall(string $toNumber, ?Lead $lead = null, ?string $fromNumber = null): array;

    /**
     * Hang up an active call
     *
     * @return array{success: bool, error?: string}
     */
    public function hangupCall(string $callId): array;

    /**
     * Get call status from PBX service
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getCallStatus(string $callId): array;

    /**
     * Get call history from PBX API
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getCallHistory(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array;

    /**
     * Sync call history from PBX API
     * Fetches calls and creates/updates local records
     *
     * @return array{success: bool, synced?: int, created?: int, updated?: int, error?: string}
     */
    public function syncCallHistory(?Carbon $dateFrom = null): array;

    /**
     * Get account balance from PBX service
     *
     * @return array{success: bool, data?: array{balance: float, currency: string}, error?: string}
     */
    public function getBalance(): array;

    /**
     * Get recording URL for a call
     */
    public function getRecordingUrl(string $callId): ?string;

    /**
     * Link orphan call logs to leads based on phone number matching
     *
     * @return array{success: bool, linked: int, failed: int, total: int}
     */
    public function linkOrphanCallLogs(string $businessId): array;

    /**
     * Normalize phone number to a standard format
     * Should handle various input formats and return a consistent format
     */
    public function normalizePhoneNumber(string $phone): string;
}
