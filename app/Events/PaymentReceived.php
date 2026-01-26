<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Business;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PaymentReceived Event
 *
 * Fired when a payment is successfully received via Payme, Click, etc.
 * Triggers real-time Telegram notifications to business owners.
 */
class PaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Business $business,
        public float $amount,
        public string $provider,
        public ?string $clientName = null,
        public ?Order $order = null,
        public ?string $transactionId = null
    ) {}
}
