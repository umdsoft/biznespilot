<?php

namespace App\Services\Store;

use App\Models\PaymentAccount;
use App\Models\Store\StoreOrder;
use App\Models\Store\StorePaymentTransaction;
use App\Models\Store\TelegramStore;
use Illuminate\Support\Facades\Log;

class StorePaymentService
{
    /**
     * Create payment URL for an order
     */
    public function createPaymentUrl(StoreOrder $order, string $provider): array
    {
        $store = $order->store;
        $business = $store->business;

        // Get merchant credentials from PaymentAccount
        $account = PaymentAccount::where('business_id', $business->id)
            ->where('provider', $provider)
            ->active()
            ->first();

        if (! $account || ! $account->isConfigured()) {
            return ['success' => false, 'error' => "To'lov tizimi ({$provider}) sozlanmagan."];
        }

        // Create payment transaction
        $transaction = StorePaymentTransaction::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'provider' => $provider,
            'amount' => $order->total,
            'status' => StorePaymentTransaction::STATUS_PENDING,
        ]);

        $paymentUrl = match ($provider) {
            'payme' => $this->generatePaymeUrl($order, $account),
            'click' => $this->generateClickUrl($order, $account),
            default => null,
        };

        if (! $paymentUrl) {
            return ['success' => false, 'error' => "Noto'g'ri to'lov tizimi"];
        }

        Log::info('Store payment URL created', [
            'order_id' => $order->id,
            'provider' => $provider,
            'amount' => $order->total,
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction->id,
        ];
    }

    /**
     * Generate Payme checkout URL
     */
    protected function generatePaymeUrl(StoreOrder $order, PaymentAccount $account): string
    {
        $amountInTiyin = (int) ($order->total * 100);
        $merchantId = $account->merchant_id;

        $params = "m={$merchantId};ac.order_id={$order->id};a={$amountInTiyin}";
        $encoded = base64_encode($params);

        $baseUrl = $account->is_test_mode
            ? 'https://test.payme.uz'
            : 'https://checkout.payme.uz';

        return "{$baseUrl}/{$encoded}";
    }

    /**
     * Generate Click checkout URL
     */
    protected function generateClickUrl(StoreOrder $order, PaymentAccount $account): string
    {
        $baseUrl = $account->is_test_mode
            ? 'https://test.click.uz/services/pay'
            : 'https://my.click.uz/services/pay';

        $params = http_build_query([
            'service_id' => $account->service_id,
            'merchant_id' => $account->merchant_user_id,
            'amount' => $order->total,
            'transaction_param' => $order->id,
            'return_url' => $order->store->getMiniAppUrl() . '/#/orders/' . $order->order_number,
        ]);

        return "{$baseUrl}?{$params}";
    }

    /**
     * Handle cash payment (marked by admin)
     */
    public function markCashPayment(StoreOrder $order): StorePaymentTransaction
    {
        $transaction = StorePaymentTransaction::create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'provider' => StorePaymentTransaction::PROVIDER_CASH,
            'amount' => $order->total,
            'status' => StorePaymentTransaction::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);

        $order->markPaid('cash');

        return $transaction;
    }
}
