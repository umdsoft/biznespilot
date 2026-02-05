<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Billing\BillingTransaction;
use App\Models\Plan;
use App\Services\Billing\PaymentRedirectService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Tariflar ro'yxati sahifasi.
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (! $business) {
            return redirect()->route('login');
        }

        $subscriptionService = app(SubscriptionService::class);

        return Inertia::render('Business/Subscription/Index', [
            'plans' => $subscriptionService->getAvailablePlans($business),
            'currentSubscription' => $subscriptionService->getStatus($business),
        ]);
    }

    /**
     * Checkout sahifasi — to'lov provayderini tanlash.
     */
    public function checkout(Request $request, Plan $plan)
    {
        $business = $this->getCurrentBusiness($request);

        if (! $business) {
            return redirect()->route('login');
        }

        // Trial-pack yoki nofaol tarifni sotib olish mumkin emas
        if ($plan->slug === 'trial-pack' || ! $plan->is_active) {
            return redirect()->route('business.subscription.index')
                ->with('error', 'Bu tarif mavjud emas.');
        }

        $paymentService = app(PaymentRedirectService::class);
        $subscriptionService = app(SubscriptionService::class);

        return Inertia::render('Business/Subscription/Checkout', [
            'checkoutData' => $paymentService->getCheckoutData($business, $plan),
            'currentSubscription' => $subscriptionService->getStatus($business),
        ]);
    }

    /**
     * To'lov URL yaratish va tashqi to'lov sahifasiga redirect.
     */
    public function pay(Request $request, Plan $plan)
    {
        $request->validate([
            'provider' => ['required', 'string', 'in:click,payme'],
        ]);

        $business = $this->getCurrentBusiness($request);

        if (! $business) {
            return redirect()->route('login');
        }

        if ($plan->slug === 'trial-pack' || ! $plan->is_active) {
            return redirect()->route('business.subscription.index')
                ->with('error', 'Bu tarif mavjud emas.');
        }

        $paymentService = app(PaymentRedirectService::class);
        $result = $paymentService->getOrCreatePaymentUrl(
            $business,
            $plan,
            $request->input('provider')
        );

        // Tashqi to'lov sahifasiga to'liq redirect (Inertia::location)
        return Inertia::location($result['payment_url']);
    }

    /**
     * To'lov muvaffaqiyatli — Click/Payme dan qaytish sahifasi.
     */
    public function success(Request $request)
    {
        $business = $this->getCurrentBusiness($request);
        $orderId = $request->query('order_id');
        $transaction = null;

        if ($orderId && $business) {
            $transaction = BillingTransaction::where('order_id', $orderId)
                ->where('business_id', $business->id)
                ->first();
        }

        // Subscription cache ni tozalash (yangilangan ma'lumot ko'rinishi uchun)
        if ($business) {
            HandleInertiaRequests::clearSubscriptionCache($business->id);
        }

        return Inertia::render('Business/Subscription/Success', [
            'transaction' => $transaction ? [
                'order_id' => $transaction->order_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'provider' => $transaction->provider,
                'plan_name' => $transaction->metadata['plan_name'] ?? null,
                'is_paid' => $transaction->isPaid(),
                'is_processing' => $transaction->isWaiting() || ($transaction->status === 'processing'),
            ] : null,
        ]);
    }

    /**
     * To'lov bekor qilindi sahifasi.
     */
    public function cancel(Request $request)
    {
        return Inertia::render('Business/Subscription/Cancel', [
            'orderId' => $request->query('order_id'),
        ]);
    }
}
