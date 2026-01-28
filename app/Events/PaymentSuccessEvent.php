<?php

namespace App\Events;

use App\Models\Billing\BillingTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PaymentSuccessEvent - To'lov muvaffaqiyatli bo'lganda dispatch qilinadi
 *
 * Bu event Payme yoki Click orqali to'lov muvaffaqiyatli
 * amalga oshganda PaymeService/ClickService tomonidan dispatch qilinadi.
 *
 * Listener vazifasi:
 * 1. Obunani aktivlashtirish (SubscriptionService::activate)
 * 2. Foydalanuvchiga xabar yuborish (Email/Telegram)
 * 3. Admin ga xabar yuborish (agar sozlangan bo'lsa)
 *
 * Usage:
 * ```php
 * event(new PaymentSuccessEvent($transaction));
 * ```
 */
class PaymentSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The billing transaction that was paid
     */
    public BillingTransaction $transaction;

    /**
     * Create a new event instance
     */
    public function __construct(BillingTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the transaction
     */
    public function getTransaction(): BillingTransaction
    {
        return $this->transaction;
    }

    /**
     * Get the business
     */
    public function getBusiness()
    {
        return $this->transaction->business;
    }

    /**
     * Get the plan
     */
    public function getPlan()
    {
        return $this->transaction->plan;
    }

    /**
     * Get the amount paid
     */
    public function getAmount(): float
    {
        return (float) $this->transaction->amount;
    }

    /**
     * Get the payment provider
     */
    public function getProvider(): string
    {
        return $this->transaction->provider;
    }

    /**
     * Get order ID
     */
    public function getOrderId(): string
    {
        return $this->transaction->order_id;
    }
}
