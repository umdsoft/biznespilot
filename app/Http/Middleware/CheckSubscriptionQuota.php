<?php

namespace App\Http\Middleware;

use App\Exceptions\NoActiveSubscriptionException;
use App\Exceptions\QuotaExceededException;
use App\Models\Business;
use App\Services\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSubscriptionQuota Middleware
 *
 * Resurs yaratishda limit tekshirish uchun ishlatiladi.
 *
 * Ishlatilishi:
 * - Route::middleware('quota:users')          - 1 ta qo'shish (default)
 * - Route::middleware('quota:monthly_leads')  - Lid yaratishda
 * - Route::middleware('quota:instagram_accounts') - IG ulashda
 */
class CheckSubscriptionQuota
{
    protected SubscriptionGate $gate;

    public function __construct(SubscriptionGate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $limitKey  Limit kaliti (masalan: 'users', 'monthly_leads')
     * @param  int  $addCount  Qo'shilmoqchi bo'lgan miqdor (default: 1)
     */
    public function handle(Request $request, Closure $next, string $limitKey, int $addCount = 1): Response
    {
        // Business kontekstini olish
        $businessId = session('current_business_id');

        if (!$businessId) {
            return $this->handleError($request, 'Business konteksti kerak', 400);
        }

        $business = Business::find($businessId);

        if (!$business) {
            return $this->handleError($request, 'Business topilmadi', 404);
        }

        try {
            $this->gate->checkQuota($business, $limitKey, null, $addCount);
            return $next($request);
        } catch (NoActiveSubscriptionException $e) {
            return $this->handleSubscriptionError($request, $e);
        } catch (QuotaExceededException $e) {
            return $this->handleQuotaError($request, $e);
        }
    }

    /**
     * Umumiy xatolik
     */
    protected function handleError(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }

    /**
     * Obuna xatoligi
     */
    protected function handleSubscriptionError(Request $request, NoActiveSubscriptionException $e): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $e->render($request);
        }

        return redirect()->route('pricing')
            ->with('error', $e->getMessage())
            ->with('upgrade_required', true);
    }

    /**
     * Kvota oshib ketdi xatoligi
     */
    protected function handleQuotaError(Request $request, QuotaExceededException $e): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $e->render($request);
        }

        // Web uchun upgrade modal ko'rsatish
        return Inertia::render('Errors/QuotaExceeded', [
            'limitKey' => $e->getLimitKey(),
            'limitLabel' => $e->getLimitLabel(),
            'limit' => $e->getLimit(),
            'currentUsage' => $e->getCurrentUsage(),
            'message' => $e->getMessage(),
        ]);
    }
}
