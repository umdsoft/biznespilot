<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Algorithm\ChurnPredictionAlgorithm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Churn Prevention Job
 *
 * Churn riskida bo'lgan mijozlarni aniqlaydi va
 * avtomatik retention campaigns boshlaydi.
 *
 * Schedule: Har kuni 10:00
 */
class ChurnPreventionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Business $business;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(Business $business)
    {
        $this->business = $business;
        $this->onQueue('retention');
    }

    public function handle(ChurnPredictionAlgorithm $churnPredictor): void
    {
        Log::info('Churn prevention started', ['business_id' => $this->business->id]);

        try {
            $result = $churnPredictor->calculate($this->business);

            if (! $result['success']) {
                throw new \Exception('Churn prediction failed');
            }

            $riskSegments = $result['risk_segments'];

            // Handle critical risk customers
            $criticalCustomers = $riskSegments['critical']['customers'] ?? [];
            if (! empty($criticalCustomers)) {
                $this->handleCriticalChurnRisk($criticalCustomers);
            }

            // Handle high risk customers
            $highRiskCustomers = $riskSegments['high']['customers'] ?? [];
            if (! empty($highRiskCustomers)) {
                $this->handleHighChurnRisk($highRiskCustomers);
            }

            // Log churn statistics
            Log::info('Churn prevention completed', [
                'business_id' => $this->business->id,
                'critical_count' => count($criticalCustomers),
                'high_risk_count' => count($highRiskCustomers),
                'average_churn_probability' => $result['statistics']['average_churn_probability'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Churn prevention failed', [
                'business_id' => $this->business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function handleCriticalChurnRisk(array $customers): void
    {
        Log::warning('Critical churn risk detected', [
            'business_id' => $this->business->id,
            'count' => count($customers),
        ]);

        foreach ($customers as $customer) {
            // TODO: Trigger urgent retention actions
            /*
            - Personal phone call task yaratish
            - Special discount email yuborish
            - Account manager assign qilish
            */

            Log::info('Critical churn customer identified', [
                'customer_id' => $customer['customer_id'],
                'churn_probability' => $customer['churn_probability'] ?? 'unknown',
            ]);

            // Dispatch personalized retention job
            // dispatch(new PersonalizedRetentionJob($this->business, $customer));
        }

        // Send alert to business owner
        $this->sendChurnAlert('critical', count($customers));
    }

    protected function handleHighChurnRisk(array $customers): void
    {
        Log::info('High churn risk detected', [
            'business_id' => $this->business->id,
            'count' => count($customers),
        ]);

        // TODO: Trigger automated win-back campaign
        // - Email series
        // - SMS reminders
        // - Special offers

        foreach ($customers as $customer) {
            Log::debug('High risk customer', [
                'customer_id' => $customer['customer_id'],
                'churn_probability' => $customer['churn_probability'] ?? 'unknown',
            ]);
        }

        // Dispatch batch win-back campaign
        // dispatch(new WinBackCampaignJob($this->business, $customers));
    }

    protected function sendChurnAlert(string $severity, int $count): void
    {
        Log::warning('Churn alert', [
            'business_id' => $this->business->id,
            'severity' => $severity,
            'count' => $count,
        ]);

        // TODO: Send notification to business owner
        // - Email: "🚨 {$count} ta mijoz yo'qotish xavfida!"
        // - Telegram bot: Critical alert
        // - Dashboard notification badge
    }
}
