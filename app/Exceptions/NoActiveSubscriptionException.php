<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoActiveSubscriptionException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? "Aktiv obuna topilmadi. Iltimos, tarifni tanlang."
        );
    }

    /**
     * Render the exception as an HTTP response.
     * Inertia so'rovlari uchun subscription sahifasiga redirect qiladi.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        // Inertia yoki oddiy web so'rov — subscription sahifasiga yo'naltirish
        if ($request->header('X-Inertia') || !$request->expectsJson()) {
            return redirect()->route('business.subscription.index')
                ->with('error', $this->getMessage());
        }

        // API so'rovlari uchun JSON javob
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'NO_ACTIVE_SUBSCRIPTION',
            'upgrade_required' => true,
        ], 402);
    }
}
