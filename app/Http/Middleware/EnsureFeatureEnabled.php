<?php

namespace App\Http\Middleware;

use App\Exceptions\FeatureNotAvailableException;
use App\Exceptions\NoActiveSubscriptionException;
use App\Models\Business;
use App\Services\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureFeatureEnabled Middleware
 *
 * Route'larda feature mavjudligini tekshirish uchun ishlatiladi.
 *
 * Ishlatilishi:
 * - Route::middleware('feature:hr_tasks')
 * - Route::middleware('feature:hr_bot')
 * - Route::middleware('feature:anti_fraud')
 */
class EnsureFeatureEnabled
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
     * @param  string  $featureKey  Feature kaliti (masalan: 'hr_tasks', 'hr_bot')
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
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
            $this->gate->checkFeature($business, $featureKey);
            return $next($request);
        } catch (NoActiveSubscriptionException $e) {
            return $this->handleSubscriptionError($request, $e);
        } catch (FeatureNotAvailableException $e) {
            return $this->handleFeatureError($request, $e);
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

        // Web uchun pricing sahifasiga yo'naltirish
        return redirect()->route('pricing')
            ->with('error', $e->getMessage())
            ->with('upgrade_required', true);
    }

    /**
     * Feature mavjud emas xatoligi
     */
    protected function handleFeatureError(Request $request, FeatureNotAvailableException $e): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $e->render($request);
        }

        // Web uchun upgrade modal ko'rsatish
        return Inertia::render('Errors/FeatureNotAvailable', [
            'featureKey' => $e->getFeatureKey(),
            'featureLabel' => $e->getFeatureLabel(),
            'message' => $e->getMessage(),
        ]);
    }
}
