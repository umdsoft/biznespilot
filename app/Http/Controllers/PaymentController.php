<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Lead;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Services\ClickService;
use App\Services\PaymeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected PaymeService $paymeService,
        protected ClickService $clickService
    ) {}

    // ==================== Settings ====================

    /**
     * Payment settings page
     */
    public function settings()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        $paymeAccount = PaymentAccount::where('business_id', $business->id)
            ->where('provider', PaymentAccount::PROVIDER_PAYME)
            ->first();

        $clickAccount = PaymentAccount::where('business_id', $business->id)
            ->where('provider', PaymentAccount::PROVIDER_CLICK)
            ->first();

        // Get recent transactions
        $recentTransactions = PaymentTransaction::where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'paymentAccount:id,provider,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'order_id' => $t->order_id,
                'amount' => $t->amount,
                'formatted_amount' => $t->formatted_amount,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'status_color' => $t->status_color,
                'provider' => $t->provider,
                'lead' => $t->lead ? ['id' => $t->lead->id, 'name' => $t->lead->name] : null,
                'created_at' => $t->created_at->format('d.m.Y H:i'),
                'paid_at' => $t->paid_at?->format('d.m.Y H:i'),
            ]);

        // Get statistics
        $stats = [
            'total_transactions' => PaymentTransaction::where('business_id', $business->id)->count(),
            'completed_transactions' => PaymentTransaction::where('business_id', $business->id)
                ->where('status', PaymentTransaction::STATUS_COMPLETED)->count(),
            'total_revenue' => PaymentTransaction::where('business_id', $business->id)
                ->where('status', PaymentTransaction::STATUS_COMPLETED)
                ->sum('amount'),
            'pending_amount' => PaymentTransaction::where('business_id', $business->id)
                ->where('status', PaymentTransaction::STATUS_PENDING)
                ->sum('amount'),
        ];

        return Inertia::render('Business/Settings/Payments', [
            'paymeAccount' => $paymeAccount ? [
                'id' => $paymeAccount->id,
                'name' => $paymeAccount->name,
                'merchant_id' => $paymeAccount->merchant_id,
                'is_active' => $paymeAccount->is_active,
                'is_test_mode' => $paymeAccount->is_test_mode,
                'last_transaction_at' => $paymeAccount->last_transaction_at?->format('d.m.Y H:i'),
            ] : null,
            'clickAccount' => $clickAccount ? [
                'id' => $clickAccount->id,
                'name' => $clickAccount->name,
                'service_id' => $clickAccount->service_id,
                'merchant_user_id' => $clickAccount->merchant_user_id,
                'is_active' => $clickAccount->is_active,
                'is_test_mode' => $clickAccount->is_test_mode,
                'last_transaction_at' => $clickAccount->last_transaction_at?->format('d.m.Y H:i'),
            ] : null,
            'recentTransactions' => $recentTransactions,
            'stats' => $stats,
            'webhookUrls' => [
                'payme' => url('/webhooks/payme'),
                'click' => url('/webhooks/click'),
            ],
        ]);
    }

    /**
     * Connect Payme account
     */
    public function connectPayme(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|string',
            'merchant_key' => 'required|string',
            'is_test_mode' => 'boolean',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Create or update Payme account
        PaymentAccount::updateOrCreate(
            [
                'business_id' => $business->id,
                'provider' => PaymentAccount::PROVIDER_PAYME,
            ],
            [
                'name' => 'Payme',
                'merchant_id' => $validated['merchant_id'],
                'merchant_key' => $validated['merchant_key'],
                'is_active' => true,
                'is_test_mode' => $validated['is_test_mode'] ?? false,
            ]
        );

        return back()->with('success', 'Payme muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect Payme account
     */
    public function disconnectPayme()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        PaymentAccount::where('business_id', $business->id)
            ->where('provider', PaymentAccount::PROVIDER_PAYME)
            ->update(['is_active' => false]);

        return back()->with('success', 'Payme uzildi');
    }

    /**
     * Connect Click account
     */
    public function connectClick(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|string',
            'merchant_user_id' => 'required|string',
            'secret_key' => 'required|string',
            'is_test_mode' => 'boolean',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Create or update Click account
        PaymentAccount::updateOrCreate(
            [
                'business_id' => $business->id,
                'provider' => PaymentAccount::PROVIDER_CLICK,
            ],
            [
                'name' => 'Click',
                'service_id' => $validated['service_id'],
                'merchant_user_id' => $validated['merchant_user_id'],
                'secret_key' => $validated['secret_key'],
                'is_active' => true,
                'is_test_mode' => $validated['is_test_mode'] ?? false,
            ]
        );

        return back()->with('success', 'Click muvaffaqiyatli ulandi!');
    }

    /**
     * Disconnect Click account
     */
    public function disconnectClick()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        PaymentAccount::where('business_id', $business->id)
            ->where('provider', PaymentAccount::PROVIDER_CLICK)
            ->update(['is_active' => false]);

        return back()->with('success', 'Click uzildi');
    }

    // ==================== Payment Links ====================

    /**
     * Create payment link for a lead
     */
    public function createPaymentLink(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
            'provider' => 'required|in:payme,click',
            'description' => 'nullable|string|max:255',
        ]);

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        if ((string) $lead->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        // Get payment account
        $account = PaymentAccount::where('business_id', $business->id)
            ->where('provider', $validated['provider'])
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return response()->json([
                'error' => ucfirst($validated['provider']) . ' tizimi sozlanmagan',
            ], 400);
        }

        // Create transaction
        $transaction = PaymentTransaction::create([
            'business_id' => $business->id,
            'lead_id' => $lead->id,
            'payment_account_id' => $account->id,
            'created_by' => Auth::id(),
            'provider' => $validated['provider'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? "To'lov: {$lead->name}",
            'return_url' => route('business.sales.show', $lead->id),
            'expires_at' => now()->addDays(3),
        ]);

        // Generate payment URL
        $paymentUrl = $validated['provider'] === PaymentAccount::PROVIDER_PAYME
            ? $this->paymeService->setAccount($account)->generatePaymentUrl($transaction, $transaction->return_url)
            : $this->clickService->setAccount($account)->generatePaymentUrl($transaction, $transaction->return_url);

        $transaction->update(['payment_url' => $paymentUrl]);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'order_id' => $transaction->order_id,
                'amount' => $transaction->amount,
                'formatted_amount' => $transaction->formatted_amount,
                'payment_url' => $paymentUrl,
                'provider' => $transaction->provider,
                'status' => $transaction->status,
                'expires_at' => $transaction->expires_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Get available payment providers for a business
     */
    public function getProviders()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $paymeAccount = PaymentAccount::where('business_id', $business->id)
            ->where('provider', PaymentAccount::PROVIDER_PAYME)
            ->where('is_active', true)
            ->first();

        $clickAccount = PaymentAccount::where('business_id', $business->id)
            ->where('provider', PaymentAccount::PROVIDER_CLICK)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'providers' => [
                'payme' => $paymeAccount && $paymeAccount->isConfigured(),
                'click' => $clickAccount && $clickAccount->isConfigured(),
            ],
        ]);
    }

    /**
     * Get payment transactions for a lead
     */
    public function getLeadTransactions(Lead $lead)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        if ((string) $lead->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        $transactions = PaymentTransaction::where('lead_id', $lead->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'order_id' => $t->order_id,
                'amount' => $t->amount,
                'formatted_amount' => $t->formatted_amount,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'status_color' => $t->status_color,
                'provider' => $t->provider,
                'payment_url' => $t->payment_url,
                'created_at' => $t->created_at->format('d.m.Y H:i'),
                'paid_at' => $t->paid_at?->format('d.m.Y H:i'),
            ]);

        return response()->json($transactions);
    }

    /**
     * Cancel a pending payment
     */
    public function cancelTransaction(PaymentTransaction $transaction)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        if ((string) $transaction->business_id !== (string) $business->id) {
            return response()->json(['error' => 'Ruxsat berilmagan'], 403);
        }

        if (!$transaction->canBeCancelled()) {
            return response()->json(['error' => 'Bu to\'lovni bekor qilib bo\'lmaydi'], 400);
        }

        $transaction->markAsCancelled('Foydalanuvchi tomonidan bekor qilindi');

        return response()->json([
            'success' => true,
            'message' => 'To\'lov bekor qilindi',
        ]);
    }

    // ==================== Transactions History ====================

    /**
     * All transactions page
     */
    public function transactions(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $perPage = $request->input('per_page', 25);
        $status = $request->input('status');
        $provider = $request->input('provider');
        $search = $request->input('search');

        $query = PaymentTransaction::where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'creator:id,name']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($provider && $provider !== 'all') {
            $query->where('provider', $provider);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $transactions->getCollection()->transform(fn($t) => [
            'id' => $t->id,
            'order_id' => $t->order_id,
            'amount' => $t->amount,
            'formatted_amount' => $t->formatted_amount,
            'status' => $t->status,
            'status_label' => $t->status_label,
            'status_color' => $t->status_color,
            'provider' => $t->provider,
            'payment_url' => $t->payment_url,
            'lead' => $t->lead ? [
                'id' => $t->lead->id,
                'name' => $t->lead->name,
            ] : null,
            'creator' => $t->creator?->name,
            'created_at' => $t->created_at->format('d.m.Y H:i'),
            'paid_at' => $t->paid_at?->format('d.m.Y H:i'),
        ]);

        return Inertia::render('Business/Payments/Transactions', [
            'transactions' => $transactions,
            'filters' => [
                'status' => $status,
                'provider' => $provider,
                'search' => $search,
            ],
            'statuses' => PaymentTransaction::STATUSES,
            'providers' => PaymentAccount::PROVIDERS,
        ]);
    }

    // ==================== Webhooks ====================

    /**
     * Handle Payme webhook
     */
    public function paymeWebhook(Request $request)
    {
        $response = $this->paymeService->handleWebhook($request);

        return response()->json($response);
    }

    /**
     * Handle Click webhook
     */
    public function clickWebhook(Request $request)
    {
        $response = $this->clickService->handleWebhook($request);

        return response()->json($response);
    }

    /**
     * Payment success page
     */
    public function success(Request $request)
    {
        $orderId = $request->query('order_id');

        $transaction = PaymentTransaction::where('order_id', $orderId)->first();

        if (!$transaction) {
            return redirect()->route('business.index')
                ->with('error', 'To\'lov topilmadi');
        }

        return Inertia::render('Business/Payments/Success', [
            'transaction' => [
                'order_id' => $transaction->order_id,
                'amount' => $transaction->formatted_amount,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'lead' => $transaction->lead ? [
                    'id' => $transaction->lead->id,
                    'name' => $transaction->lead->name,
                ] : null,
            ],
        ]);
    }
}
