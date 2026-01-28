<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotaExceededException extends Exception
{
    protected string $limitKey;
    protected string $limitLabel;
    protected int $limit;
    protected int $currentUsage;

    public function __construct(
        string $limitKey,
        string $limitLabel,
        int $limit,
        int $currentUsage,
        ?string $message = null
    ) {
        $this->limitKey = $limitKey;
        $this->limitLabel = $limitLabel;
        $this->limit = $limit;
        $this->currentUsage = $currentUsage;

        parent::__construct(
            $message ?? "{$limitLabel} limiti tugadi ({$currentUsage}/{$limit}). Tarifingizni yangilang."
        );
    }

    public function getLimitKey(): string
    {
        return $this->limitKey;
    }

    public function getLimitLabel(): string
    {
        return $this->limitLabel;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getCurrentUsage(): int
    {
        return $this->currentUsage;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'QUOTA_EXCEEDED',
            'limit_key' => $this->limitKey,
            'limit_label' => $this->limitLabel,
            'limit' => $this->limit,
            'current_usage' => $this->currentUsage,
            'upgrade_required' => true,
        ], 403);
    }
}
