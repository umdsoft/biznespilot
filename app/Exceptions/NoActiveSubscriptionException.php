<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
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
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'NO_ACTIVE_SUBSCRIPTION',
            'upgrade_required' => true,
        ], 402);
    }
}
