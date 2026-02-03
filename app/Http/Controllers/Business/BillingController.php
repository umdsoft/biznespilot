<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Billing\BillingTransaction;
use App\Models\Plan;
use App\Services\Billing\PaymentRedirectService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    use HasCurrentBusiness;

    protected SubscriptionService $subscriptionService;
    protected PaymentRedirectService $paymentRedirectService;

    public function __construct(
        SubscriptionService $subscriptionService,
        PaymentRedirectService $paymentRedirectService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->paymentRedirectService = $paymentRedirectService;
    }

    /**
     * Tarif rejalar sahifasi
     */
    public function plans()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $plans = $this->subscriptionService->getAvailablePlans($business);
        $subscriptionStatus = $this->subscriptionService->getStatus($business);

        return Inertia::render('Business/Billing/Plans', [
            'plans' => $plans,
            'subscriptionStatus' => $subscriptionStatus,
        ]);
    }

    /**
     * To'lov checkout - tranzaksiya yaratish va Click ga redirect
     */
    public function checkout(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'provider' => 'required|in:click,payme',
        ]);

        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return back()->with('error', 'Bu tarif reja aktiv emas');
        }

        $result = $this->paymentRedirectService->getOrCreatePaymentUrl(
            $business,
            $plan,
            $request->provider,
            $request->billing_cycle
        );

        return Inertia::location($result['payment_url']);
    }

    /**
     * To'lov muvaffaqiyat sahifasi
     */
    public function success(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $orderId = $request->query('order_id');
        $transaction = null;

        if ($orderId) {
            $transaction = BillingTransaction::where('order_id', $orderId)
                ->where('business_id', $business->id)
                ->with('plan')
                ->first();
        }

        return Inertia::render('Business/Billing/Success', [
            'transaction' => $transaction ? [
                'order_id' => $transaction->order_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'provider' => $transaction->provider,
                'plan_name' => $transaction->plan?->name,
                'billing_cycle' => $transaction->getMetadata('billing_cycle', 'monthly'),
                'created_at' => $transaction->created_at?->format('d.m.Y H:i'),
            ] : null,
        ]);
    }

    /**
     * To'lov tarixi sahifasi
     */
    public function history(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $subscriptionStatus = $this->subscriptionService->getStatus($business);

        $query = BillingTransaction::where('business_id', $business->id)
            ->with('plan')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(15)->through(fn ($t) => [
            'id' => $t->id,
            'order_id' => $t->order_id,
            'plan_name' => $t->plan?->name,
            'provider' => $t->provider,
            'amount' => $t->amount,
            'currency' => $t->currency,
            'status' => $t->status,
            'billing_cycle' => $t->getMetadata('billing_cycle', 'monthly'),
            'created_at' => $t->created_at?->format('d.m.Y H:i'),
            'performed_at' => $t->performed_at?->format('d.m.Y H:i'),
        ]);

        return Inertia::render('Business/Billing/History', [
            'subscriptionStatus' => $subscriptionStatus,
            'transactions' => $transactions,
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }
}
