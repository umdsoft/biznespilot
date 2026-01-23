<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\PbxAccount;
use App\Models\UtelAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CallRecordingService - Unified service for getting call recording URLs
 *
 * Ikkala provider ham to'g'ridan-to'g'ri URL qaytaradi:
 * - UTEL: recorded_file_url (call-history response)
 * - OnlinePBX: download_url, record (history response) yoki /record/get.json
 *
 * Streaming/proxy kerak emas - URL ni to'g'ridan-to'g'ri frontend ga beramiz
 */
class CallRecordingService
{
    /**
     * Get recording URL for a call
     * Avval database dan tekshiradi, keyin provider API dan oladi
     */
    public function getRecordingUrl(CallLog $call): ?string
    {
        // 1. Agar allaqachon URL mavjud bo'lsa - qaytaramiz
        if ($call->recording_url) {
            return $call->recording_url;
        }

        // 2. Provider call ID bo'lmasa - recording yo'q
        if (!$call->provider_call_id) {
            return null;
        }

        // 3. Provider bo'yicha URL olish
        $url = match ($call->provider) {
            'utel' => $this->getUtelRecordingUrl($call),
            'onlinepbx', 'pbx' => $this->getOnlinePbxRecordingUrl($call),
            default => null,
        };

        // 4. Topilgan URL ni saqlash (keyingi safar tezroq olish uchun)
        if ($url) {
            $call->update(['recording_url' => $url]);
        }

        return $url;
    }

    /**
     * Get recording URL from UTEL API
     *
     * UTEL call-history response format:
     * {
     *   "data": [{
     *     "id": "...",
     *     "recorded_file_url": "https://..."  // <-- Bu bizga kerak
     *   }]
     * }
     */
    protected function getUtelRecordingUrl(CallLog $call): ?string
    {
        try {
            $account = UtelAccount::where('business_id', $call->business_id)
                ->where('is_active', true)
                ->first();

            if (!$account || !$account->isConfigured()) {
                return null;
            }

            // Authenticate
            $token = $this->getUtelToken($account);
            if (!$token) {
                return null;
            }

            $baseUrl = $account->getApiBaseUrl();

            // UTEL da alohida recording endpoint yo'q
            // call-history dan olinadi yoki metadata da bo'ladi

            // Metadata dan tekshirish
            $metadata = $call->metadata ?? [];
            if (!empty($metadata['recorded_file_url'])) {
                return $metadata['recorded_file_url'];
            }
            if (!empty($metadata['record_url'])) {
                return $metadata['record_url'];
            }

            // API dan olishga urinish - call detail
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($baseUrl . '/v1/call-history', [
                    'filter[id]' => $call->provider_call_id,
                    'per_page' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $calls = $data['data'] ?? [];

                if (!empty($calls[0]['recorded_file_url'])) {
                    return $calls[0]['recorded_file_url'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('UTEL get recording URL failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get recording URL from OnlinePBX API
     *
     * OnlinePBX endpoint: /record/get.json
     * Response: { "status": 1, "data": { "url": "https://..." } }
     */
    protected function getOnlinePbxRecordingUrl(CallLog $call): ?string
    {
        try {
            $account = PbxAccount::where('business_id', $call->business_id)
                ->where('is_active', true)
                ->first();

            if (!$account || !$account->isConfigured()) {
                return null;
            }

            // Metadata dan tekshirish (history sync da kelgan bo'lishi mumkin)
            $metadata = $call->metadata ?? [];
            if (!empty($metadata['download_url'])) {
                return $metadata['download_url'];
            }
            if (!empty($metadata['record'])) {
                return $metadata['record'];
            }
            if (!empty($metadata['recording_url'])) {
                return $metadata['recording_url'];
            }

            // OnlinePBX API dan olish
            $sessionKey = $this->getOnlinePbxSessionKey($account);
            if (!$sessionKey) {
                return null;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $account->api_key . ':' . $sessionKey,
                ])
                ->get(rtrim($account->api_url, '/') . '/record/get.json', [
                    'call_id' => $call->provider_call_id,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    return $data['data']['url'] ?? $data['data']['record_url'] ?? null;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('OnlinePBX get recording URL failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get UTEL access token
     */
    protected function getUtelToken(UtelAccount $account): ?string
    {
        // Mavjud token hali yaroqli bo'lsa
        if ($account->hasValidToken()) {
            return $account->access_token;
        }

        try {
            $password = Crypt::decryptString($account->password);
            $baseUrl = $account->getApiBaseUrl();

            $response = Http::timeout(15)
                ->post($baseUrl . '/v1/auth/login', [
                    'email' => $account->email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? $data['token'] ?? null;

                if ($token) {
                    $account->update([
                        'access_token' => $token,
                        'token_expires_at' => now()->addHours(24),
                    ]);
                    return $token;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('UTEL auth failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get OnlinePBX session key
     */
    protected function getOnlinePbxSessionKey(PbxAccount $account): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-pbx-authentication' => $account->api_key,
                ])
                ->get(rtrim($account->api_url, '/') . '/auth.json');

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? 0) == 1) {
                    return $data['data']['key'] ?? null;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('OnlinePBX auth failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if call has recording available
     */
    public function hasRecording(CallLog $call): bool
    {
        // Agar URL allaqachon bor bo'lsa
        if ($call->recording_url) {
            return true;
        }

        // Answered/completed bo'lgan qo'ng'iroqlarda odatda recording bo'ladi
        if (in_array($call->status, [CallLog::STATUS_COMPLETED, CallLog::STATUS_ANSWERED])) {
            // Metadata da bor-yo'qligini tekshirish
            $metadata = $call->metadata ?? [];
            return !empty($metadata['recorded_file_url'])
                || !empty($metadata['record_url'])
                || !empty($metadata['download_url'])
                || !empty($metadata['record']);
        }

        return false;
    }

    /**
     * Batch get recording URLs for multiple calls
     * Frontend uchun list ko'rsatganda
     */
    public function getRecordingUrls(array $callIds): array
    {
        $result = [];

        $calls = CallLog::whereIn('id', $callIds)->get();

        foreach ($calls as $call) {
            $result[$call->id] = $this->getRecordingUrl($call);
        }

        return $result;
    }
}
