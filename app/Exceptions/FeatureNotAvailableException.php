<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureNotAvailableException extends Exception
{
    protected string $featureKey;
    protected string $featureLabel;

    public function __construct(string $featureKey, string $featureLabel, ?string $message = null)
    {
        $this->featureKey = $featureKey;
        $this->featureLabel = $featureLabel;

        parent::__construct(
            $message ?? "{$featureLabel} xususiyati sizning tarifingizda mavjud emas. Tarifingizni yangilang."
        );
    }

    public function getFeatureKey(): string
    {
        return $this->featureKey;
    }

    public function getFeatureLabel(): string
    {
        return $this->featureLabel;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'FEATURE_NOT_AVAILABLE',
            'feature_key' => $this->featureKey,
            'feature_label' => $this->featureLabel,
            'upgrade_required' => true,
        ], 403);
    }
}
