<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingTransaction;
use App\Models\Business;
use App\Models\Plan;
use Illuminate\Support\Str;

/**
 * PaymentRedirectService - To'lov URL Generatori
 *
 * Bu servis foydalanuvchini Payme yoki Click to'lov sahifasiga
 * yo'naltirish uchun URL yaratadi.
 *
 * Usage:
 * ```php
 * $service = app(PaymentRedirectService::class);
 * $result = $service->createPaymentUrl($business, $plan, 'payme');
 *
 * // Natija:
 * // [
 * //     'transaction' => BillingTransaction,
 * //     'payment_url' => 'https://checkout.payme.uz/...',
 * //     'order_id' => 'BP2601281234ABCD',
 * // ]
 * ```
 */
class PaymentRedirectService
{
    /**
     * Create payment URL for a plan purchase
     *
     * @param Business $business - To'lov qilayotgan biznes
     * @param Plan $plan - Sotib olinayotgan tarif
     * @param string $provider - 'payme' yoki 'click'
     * @param string|null $subscriptionId - Mavjud subscription (yangilash uchun)
     * @return array
     */
    public function createPaymentUrl(
        Business $business,
        Plan $plan,
        string $provider = 'payme',
        ?string $subscriptionId = null,
        string $billingCycle = 'monthly'
    ): array {
        // Summani cycle bo'yicha aniqlash
        $amount = $billingCycle === 'yearly'
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        // Tranzaksiya yaratish
        $transaction = $this->createTransaction($business, $plan, $provider, $amount, $subscriptionId, $billingCycle);

        // Provider bo'yicha URL generatsiya
        $paymentUrl = match ($provider) {
            'payme' => $this->generatePaymeUrl($transaction),
            'click' => $this->generateClickUrl($transaction),
            default => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };

        return [
            'transaction' => $transaction,
            'payment_url' => $paymentUrl,
            'order_id' => $transaction->order_id,
            'amount' => $amount,
            'provider' => $provider,
        ];
    }

    /**
     * Create billing transaction
     */
    protected function createTransaction(
        Business $business,
        Plan $plan,
        string $provider,
        float $amount,
        ?string $subscriptionId,
        string $billingCycle = 'monthly'
    ): BillingTransaction {
        return BillingTransaction::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscriptionId,
            'provider' => $provider,
            'order_id' => BillingTransaction::generateOrderId(),
            'amount' => $amount,
            'currency' => 'UZS',
            'status' => BillingTransaction::STATUS_CREATED,
            'metadata' => [
                'plan_name' => $plan->name,
                'plan_slug' => $plan->slug,
                'business_name' => $business->name,
                'billing_cycle' => $billingCycle,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Generate Payme checkout URL
     *
     * Payme URL format:
     * https://checkout.payme.uz/BASE64_ENCODED_PARAMS
     *
     * Params:
     * - m: Merchant ID
     * - ac.order_id: Order ID
     * - a: Amount in tiyin (so'm * 100)
     * - l: Language (uz/ru/en)
     * - c: Return URL (optional)
     */
    public function generatePaymeUrl(BillingTransaction $transaction): string
    {
        $merchantId = config('billing.payme.merchant_id');
        $isTestMode = config('billing.payme.test_mode', true);

        // Base URL
        $baseUrl = $isTestMode
            ? config('billing.payme.test_checkout_url', 'https://test.payme.uz')
            : config('billing.payme.checkout_url', 'https://checkout.payme.uz');

        // Parametrlarni tayyorlash
        $params = [
            'm' => $merchantId,
            'ac.order_id' => $transaction->order_id,
            'a' => $transaction->getAmountInTiyin(), // Tiyinda!
            'l' => 'uz',
        ];

        // Return URL qo'shish (agar sozlangan bo'lsa)
        $successUrl = config('billing.urls.success');
        if ($successUrl) {
            $params['c'] = url($successUrl) . '?order_id=' . $transaction->order_id;
        }

        // Parametrlarni encode qilish
        $paramsString = http_build_query($params, '', ';');
        $encodedParams = base64_encode($paramsString);

        return "{$baseUrl}/{$encodedParams}";
    }

    /**
     * Generate Click checkout URL
     *
     * Click URL format:
     * https://my.click.uz/services/pay?service_id=X&merchant_id=Y&amount=Z&transaction_param=ORDER_ID
     */
    public function generateClickUrl(BillingTransaction $transaction): string
    {
        $serviceId = config('billing.click.service_id');
        $merchantId = config('billing.click.merchant_id');
        $isTestMode = config('billing.click.test_mode', true);

        // Base URL
        $baseUrl = $isTestMode
            ? config('billing.click.test_checkout_url', 'https://test.click.uz/services/pay')
            : config('billing.click.checkout_url', 'https://my.click.uz/services/pay');

        // Parametrlar
        $params = [
            'service_id' => $serviceId,
            'merchant_id' => $merchantId,
            'amount' => $transaction->amount, // So'mda
            'transaction_param' => $transaction->order_id,
        ];

        // Return URL - business billing success sahifasiga
        $params['return_url'] = url('/business/billing/success') . '?order_id=' . $transaction->order_id;

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Get existing pending transaction or create new
     *
     * Bu metod mavjud kutilayotgan tranzaksiyani qaytaradi yoki yangi yaratadi.
     * Bu foydalanuvchi bir necha marta to'lov tugmasini bosganida
     * ortiqcha tranzaksiya yaratilishini oldini oladi.
     */
    public function getOrCreatePaymentUrl(
        Business $business,
        Plan $plan,
        string $provider = 'payme',
        string $billingCycle = 'monthly'
    ): array {
        // Mavjud pending tranzaksiyani tekshirish (billing_cycle bo'yicha ham filter)
        $existingTransaction = BillingTransaction::where('business_id', $business->id)
            ->where('plan_id', $plan->id)
            ->where('provider', $provider)
            ->where('status', BillingTransaction::STATUS_CREATED)
            ->where('expires_at', '>', now())
            ->where('metadata->billing_cycle', $billingCycle)
            ->first();

        if ($existingTransaction) {
            // Mavjud URL ni qaytarish
            $paymentUrl = match ($provider) {
                'payme' => $this->generatePaymeUrl($existingTransaction),
                'click' => $this->generateClickUrl($existingTransaction),
                default => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
            };

            return [
                'transaction' => $existingTransaction,
                'payment_url' => $paymentUrl,
                'order_id' => $existingTransaction->order_id,
                'amount' => $existingTransaction->amount,
                'provider' => $provider,
                'is_existing' => true,
            ];
        }

        // Yangi yaratish
        $result = $this->createPaymentUrl($business, $plan, $provider, null, $billingCycle);
        $result['is_existing'] = false;

        return $result;
    }

    /**
     * Generate checkout page data for frontend
     *
     * Bu metod frontend uchun to'liq ma'lumot qaytaradi.
     */
    public function getCheckoutData(Business $business, Plan $plan): array
    {
        // Payme URL
        $payme = $this->getOrCreatePaymentUrl($business, $plan, 'payme');

        // Click URL
        $click = $this->getOrCreatePaymentUrl($business, $plan, 'click');

        return [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'monthly_price' => $plan->price_monthly,
                'yearly_price' => $plan->price_yearly,
            ],
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'providers' => [
                'payme' => [
                    'enabled' => !empty(config('billing.payme.merchant_id')),
                    'payment_url' => $payme['payment_url'],
                    'order_id' => $payme['order_id'],
                    'transaction_id' => $payme['transaction']->id,
                ],
                'click' => [
                    'enabled' => !empty(config('billing.click.service_id')),
                    'payment_url' => $click['payment_url'],
                    'order_id' => $click['order_id'],
                    'transaction_id' => $click['transaction']->id,
                ],
            ],
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
        ];
    }
}
