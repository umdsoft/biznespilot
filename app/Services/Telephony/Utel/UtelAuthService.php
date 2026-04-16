<?php

namespace App\Services\Telephony\Utel;

use App\Models\UtelAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UTEL Authentication Service — toza modul.
 *
 * Faqat: login, token management, refresh.
 * Eski UtelService bilan parallel ishlaydi (uni buzmaydi).
 */
class UtelAuthService
{
    private const DEFAULT_API_URL = 'https://api.utel.uz/api';

    /**
     * Login qilish va token olish
     */
    public function login(string $email, string $password, ?string $apiUrl = null): array
    {
        $email = trim($email);
        $password = trim($password);
        $url = ($apiUrl ?? self::DEFAULT_API_URL) . '/v1/auth/login';

        try {
            $response = Http::timeout(15)
                ->accept('application/json')
                ->post($url, [
                    'email' => $email,
                    'password' => $password,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'API javob bermadi: ' . $response->status(),
                    'body' => mb_substr($response->body(), 0, 200),
                ];
            }

            $data = $response->json();
            $token = $data['result']['access_token']
                ?? $data['access_token']
                ?? $data['token']
                ?? null;

            if (!$token) {
                return ['success' => false, 'error' => 'Token topilmadi javobda'];
            }

            return [
                'success' => true,
                'token' => $token,
                'expires_in' => $data['result']['expires_in'] ?? $data['expires_in'] ?? 3600,
            ];
        } catch (\Exception $e) {
            Log::error('UtelAuth login xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Account tokenini tekshirish (muddati o'tdimi?)
     */
    public function isTokenValid(UtelAccount $account): bool
    {
        if (!$account->access_token || !$account->token_expires_at) {
            return false;
        }
        return $account->token_expires_at->isFuture();
    }

    /**
     * Tokenni yangilash
     */
    public function refreshToken(UtelAccount $account): bool
    {
        try {
            $password = Crypt::decryptString($account->password);
            $result = $this->login($account->email, $password, $account->getApiBaseUrl());

            if (!$result['success']) {
                Log::warning('Token refresh xato', [
                    'account_id' => $account->id,
                    'error' => $result['error'] ?? 'noma\'lum',
                ]);
                return false;
            }

            $account->update([
                'access_token' => $result['token'],
                'token_expires_at' => now()->addSeconds($result['expires_in']),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('UtelAuth refresh xato', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Token olish (avto-refresh bilan)
     */
    public function getValidToken(UtelAccount $account): ?string
    {
        if (!$this->isTokenValid($account)) {
            if (!$this->refreshToken($account)) {
                return null;
            }
            $account->refresh();
        }
        return $account->access_token;
    }
}
