<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\Customer;
use App\Services\Marketing\CrossModuleAttributionService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Barcha customerlar uchun churn risk hisoblash
 *
 * Har kuni ishga tushiriladi va churn xavfi
 * yuqori bo'lgan customerlarni aniqlaydi.
 */
class CalculateChurnRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600; // 10 daqiqa

    public function __construct(
        protected ?string $businessId = null
    ) {}

    public function handle(
        CrossModuleAttributionService $attributionService,
        NotificationService $notificationService
    ): void {
        Log::info('CalculateChurnRiskJob: Starting churn risk calculation');

        $query = Business::where('status', 'active');

        if ($this->businessId) {
            $query->where('id', $this->businessId);
        }

        $businesses = $query->get();
        $totalUpdated = 0;
        $highRiskCount = 0;

        foreach ($businesses as $business) {
            $result = $this->processBusinessCustomers($business, $attributionService);
            $totalUpdated += $result['updated'];
            $highRiskCount += $result['high_risk'];

            // High risk customers haqida notification
            if ($result['high_risk'] > 0) {
                $this->sendHighRiskAlert($business, $result['high_risk'], $notificationService);
            }
        }

        Log::info('CalculateChurnRiskJob: Completed', [
            'businesses_processed' => $businesses->count(),
            'total_updated' => $totalUpdated,
            'high_risk_count' => $highRiskCount,
        ]);
    }

    /**
     * Bitta biznes uchun customerlarni qayta ishlash
     */
    protected function processBusinessCustomers(
        Business $business,
        CrossModuleAttributionService $attributionService
    ): array {
        $updated = 0;
        $highRisk = 0;
        $criticalCustomers = [];

        Customer::where('business_id', $business->id)
            ->whereNull('churned_at') // Churn bo'lmaganlar
            ->chunk(100, function ($customers) use ($attributionService, &$updated, &$highRisk, &$criticalCustomers) {
                foreach ($customers as $customer) {
                    $churn = $attributionService->calculateChurnRisk($customer);
                    $ltv = $attributionService->calculateCustomerLtv($customer);

                    $customer->update([
                        'churn_risk_score' => $churn['score'],
                        'churn_risk_level' => $churn['level'],
                        'days_since_last_purchase' => $churn['days_since_last_purchase'],
                        'lifetime_value' => $ltv,
                    ]);

                    $updated++;

                    // High/Critical risk
                    if (in_array($churn['level'], ['high', 'critical'])) {
                        $highRisk++;

                        // Critical bo'lsa va LTV yuqori bo'lsa - alohida track
                        if ($churn['level'] === 'critical' && $ltv > 1000000) {
                            $criticalCustomers[] = [
                                'name' => $customer->name,
                                'ltv' => $ltv,
                                'days' => $churn['days_since_last_purchase'],
                            ];
                        }
                    }

                    // Agar 365 kundan ko'p xarid qilmagan bo'lsa - churn deb belgilash
                    if ($churn['days_since_last_purchase'] > 365) {
                        $customer->update([
                            'churned_at' => now(),
                            'churn_reason' => '365 kundan ko\'p xaridsiz',
                        ]);
                    }
                }
            });

        return [
            'updated' => $updated,
            'high_risk' => $highRisk,
            'critical_customers' => $criticalCustomers,
        ];
    }

    /**
     * High risk alert yuborish
     */
    protected function sendHighRiskAlert(
        Business $business,
        int $count,
        NotificationService $notificationService
    ): void {
        try {
            $notificationService->sendInsight(
                $business->id,
                '⚠️ Churn xavfi yuqori mijozlar aniqlandi',
                "{$count} ta mijoz churn xavfi yuqori darajada. Ularni qayta jalb qilish uchun choralar ko'ring.",
                'warning',
                [
                    'count' => $count,
                    'action_url' => '/customers?churn_risk=high',
                ]
            );
        } catch (\Exception $e) {
            Log::error('CalculateChurnRiskJob: Failed to send alert', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function tags(): array
    {
        return [
            'marketing',
            'churn-risk',
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
