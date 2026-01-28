<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * PaymeBasicAuth - Payme Basic Authentication Middleware
 *
 * Payme barcha so'rovlarni Basic Auth bilan yuboradi.
 * Format: Authorization: Basic base64(login:password)
 *
 * login = "Paycom"
 * password = PAYME_MERCHANT_KEY (.env dan)
 *
 * Bu middleware:
 * 1. Authorization header mavjudligini tekshiradi
 * 2. Base64 decode qiladi
 * 3. Login va Keyni tekshiradi
 * 4. IP manzilni tekshiradi (agar sozlangan bo'lsa)
 */
class PaymeBasicAuth
{
    protected string $logChannel = 'billing';

    public function handle(Request $request, Closure $next): Response
    {
        // Authorization header tekshiruvi
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            $this->log('Auth failed: Missing or invalid Authorization header', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(-32504, 'Authentication required');
        }

        // Base64 decode
        $credentials = base64_decode(substr($authHeader, 6));

        if (!$credentials || !str_contains($credentials, ':')) {
            $this->log('Auth failed: Invalid Base64 credentials', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(-32504, 'Invalid credentials format');
        }

        [$login, $key] = explode(':', $credentials, 2);

        // Payme login tekshiruvi (har doim "Paycom")
        if ($login !== 'Paycom') {
            $this->log('Auth failed: Invalid login', [
                'ip' => $request->ip(),
                'login' => $login,
            ]);
            return $this->errorResponse(-32504, 'Invalid login');
        }

        // Merchant key tekshiruvi
        $merchantKey = config('billing.payme.merchant_key');

        if (!$merchantKey) {
            $this->log('Auth failed: PAYME_MERCHANT_KEY not configured', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(-32504, 'Merchant key not configured');
        }

        if ($key !== $merchantKey) {
            $this->log('Auth failed: Invalid merchant key', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(-32504, 'Invalid key');
        }

        // IP tekshiruvi (agar sozlangan bo'lsa)
        if (config('billing.webhook.verify_ip', false)) {
            $allowedIps = config('billing.payme.allowed_ips', []);

            if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
                $this->log('Auth failed: IP not allowed', [
                    'ip' => $request->ip(),
                    'allowed' => $allowedIps,
                ]);
                return $this->errorResponse(-32504, 'IP not allowed');
            }
        }

        $this->log('Auth success', [
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Payme JSON-RPC error response
     */
    protected function errorResponse(int $code, string $message): Response
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => [
                    'ru' => $message,
                    'uz' => $message,
                    'en' => $message,
                ],
            ],
        ], 200); // Payme har doim 200 kutadi
    }

    /**
     * Log
     */
    protected function log(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->info("[PaymeAuth] {$message}", $context);
    }
}
