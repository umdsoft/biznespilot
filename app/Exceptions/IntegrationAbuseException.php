<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationAbuseException extends Exception
{
    protected string $accountIdentifier;
    protected ?string $previousBusinessName;
    protected string $abuseType;

    public function __construct(
        string $accountIdentifier,
        ?string $previousBusinessName = null,
        string $abuseType = 'already_connected',
        ?string $message = null
    ) {
        $this->accountIdentifier = $accountIdentifier;
        $this->previousBusinessName = $previousBusinessName;
        $this->abuseType = $abuseType;

        $defaultMessage = match ($abuseType) {
            'already_connected' => "Bu akkaunt ({$accountIdentifier}) boshqa biznesda allaqachon ulangan.",
            'trial_abuse' => "Bu akkaunt ({$accountIdentifier}) avval sinov davrida ishlatilgan. Pullik tarifga o'ting.",
            default => "Integratsiya suiiste'molchiligi aniqlandi: {$accountIdentifier}",
        };

        parent::__construct($message ?? $defaultMessage);
    }

    public function getAccountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function getPreviousBusinessName(): ?string
    {
        return $this->previousBusinessName;
    }

    public function getAbuseType(): string
    {
        return $this->abuseType;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'INTEGRATION_ABUSE',
            'abuse_type' => $this->abuseType,
            'account_identifier' => $this->accountIdentifier,
            'upgrade_required' => $this->abuseType === 'trial_abuse',
        ], 403);
    }
}
