<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BillingWebhookLog - Barcha webhook'lar logi
 *
 * Debugging va audit uchun barcha kiruvchi webhook'larni saqlaydi.
 */
class BillingWebhookLog extends Model
{
    protected $table = 'billing_webhook_logs';

    public $timestamps = false;

    protected $fillable = [
        'provider',
        'method',
        'action',
        'request_headers',
        'request_body',
        'response_body',
        'response_code',
        'ip_address',
        'billing_transaction_id',
        'is_successful',
        'error_message',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'is_successful' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship to billing transaction
     */
    public function billingTransaction(): BelongsTo
    {
        return $this->belongsTo(BillingTransaction::class);
    }

    /**
     * Create log entry from request
     */
    public static function logRequest(
        string $provider,
        ?string $method,
        ?string $action,
        array $headers,
        array $body,
        string $ipAddress
    ): self {
        return self::create([
            'provider' => $provider,
            'method' => $method,
            'action' => $action,
            'request_headers' => $headers,
            'request_body' => $body,
            'ip_address' => $ipAddress,
            'is_successful' => false,
        ]);
    }

    /**
     * Update log with response
     */
    public function logResponse(
        array $response,
        int $responseCode,
        bool $isSuccessful,
        ?int $transactionId = null,
        ?string $errorMessage = null
    ): void {
        $this->update([
            'response_body' => $response,
            'response_code' => $responseCode,
            'is_successful' => $isSuccessful,
            'billing_transaction_id' => $transactionId,
            'error_message' => $errorMessage,
        ]);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePayme($query)
    {
        return $query->where('provider', 'payme');
    }

    public function scopeClick($query)
    {
        return $query->where('provider', 'click');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
