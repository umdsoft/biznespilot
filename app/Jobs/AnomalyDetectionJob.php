<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Algorithm\AnomalyDetectionAlgorithm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Anomaly Detection Job
 *
 * Real-time anomaly detection - har soatda ishga tushadi.
 * Metric'larda g'ayritabiiy o'zgarishlarni aniqlaydi.
 *
 * Schedule: Har soatda
 */
class AnomalyDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Business $business;
    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(Business $business)
    {
        $this->business = $business;
        $this->onQueue('monitoring');
    }

    public function handle(AnomalyDetectionAlgorithm $anomalyDetector): void
    {
        Log::debug('Anomaly detection started', ['business_id' => $this->business->id]);

        try {
            $result = $anomalyDetector->analyze($this->business);

            if (!$result['success']) {
                return;
            }

            $anomalyScore = $result['anomaly_score'];
            $status = $result['status'];
            $alerts = $result['alerts'] ?? [];

            // Cache last anomaly score for trending
            $cacheKey = "anomaly_score:{$this->business->id}";
            $previousScore = Cache::get($cacheKey, 0);
            Cache::put($cacheKey, $anomalyScore, now()->addDays(7));

            // Log if anomaly score increased significantly
            if ($anomalyScore > $previousScore + 20) {
                Log::warning('Anomaly score spiked', [
                    'business_id' => $this->business->id,
                    'previous_score' => $previousScore,
                    'current_score' => $anomalyScore,
                    'increase' => $anomalyScore - $previousScore,
                ]);
            }

            // Send alerts for critical/warning status
            if (in_array($status, ['critical', 'warning']) && !empty($alerts)) {
                $this->handleAnomalyAlerts($alerts, $result);
            }

            // Store anomaly event in database
            if ($status !== 'normal') {
                $this->storeAnomalyEvent($result);
            }

        } catch (\Exception $e) {
            Log::error('Anomaly detection failed', [
                'business_id' => $this->business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleAnomalyAlerts(array $alerts, array $result): void
    {
        foreach ($alerts as $alert) {
            Log::warning('Anomaly detected', [
                'business_id' => $this->business->id,
                'metric' => $alert['metric'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
            ]);

            // TODO: Send real-time notification
            // - Telegram bot message
            // - Email alert
            // - SMS for critical
        }
    }

    protected function storeAnomalyEvent(array $result): void
    {
        // TODO: Store in anomaly_events table
        /*
        AnomalyEvent::create([
            'business_id' => $this->business->id,
            'anomaly_score' => $result['anomaly_score'],
            'status' => $result['status'],
            'anomalies' => json_encode($result['anomalies']),
            'alerts' => json_encode($result['alerts']),
            'detected_at' => now(),
        ]);
        */
    }
}
