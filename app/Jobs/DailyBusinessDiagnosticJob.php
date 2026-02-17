<?php

namespace App\Jobs;

use App\Models\AiDiagnostic;
use App\Models\Business;
use App\Services\Algorithm\DiagnosticAlgorithmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Daily Business Diagnostic Job
 *
 * Har kuni bizneslarning to'liq diagnostikasini avtomatik bajaradi.
 * Critical muammolar topilsa notification yuboradi.
 *
 * Schedule: Har kuni ertalab 6:00
 */
class DailyBusinessDiagnosticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Business $business;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Business $business)
    {
        $this->business = $business;
        $this->onQueue('diagnostics');
    }

    /**
     * Execute the job.
     */
    public function handle(DiagnosticAlgorithmService $diagnosticService): void
    {
        Log::info('Daily diagnostic started', [
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
        ]);

        try {
            // Run full diagnostic
            $result = $diagnosticService->runFullDiagnostic($this->business);

            if (! $result['success']) {
                throw new \Exception('Diagnostic failed: '.($result['error'] ?? 'Unknown error'));
            }

            // Save diagnostic to database (AiDiagnostic v2 schema)
            $diagnostic = AiDiagnostic::create([
                'business_id' => $this->business->id,
                'type' => 'full',
                'status' => 'completed',
                'input_data' => ['source' => 'daily_scheduled'],
                'results' => $result['algorithm_results'] ?? [],
                'overall_score' => $result['overall_score'],
                'recommendations' => $result['prioritized_actions'] ?? [],
                'completed_at' => now(),
            ]);

            // Check for critical issues
            $criticalIssues = $this->extractCriticalIssues($result);

            if (! empty($criticalIssues)) {
                $this->sendCriticalAlert($criticalIssues, $diagnostic);
            }

            // Send daily summary if enabled
            if ($this->business->settings['daily_summary_email'] ?? false) {
                $this->sendDailySummary($result, $diagnostic);
            }

            Log::info('Daily diagnostic completed successfully', [
                'business_id' => $this->business->id,
                'overall_score' => $result['overall_score'],
                'critical_issues' => count($criticalIssues),
            ]);

        } catch (\Exception $e) {
            Log::error('Daily diagnostic failed', [
                'business_id' => $this->business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw for retry mechanism
        }
    }

    /**
     * Count critical issues
     */
    protected function countCriticalIssues(array $result): int
    {
        $count = 0;

        foreach ($result['prioritized_actions'] ?? [] as $action) {
            if ($action['priority'] === 'critical') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Extract critical issues
     */
    protected function extractCriticalIssues(array $result): array
    {
        $issues = [];

        // Check overall score
        if ($result['overall_score'] < 50) {
            $issues[] = [
                'type' => 'low_overall_score',
                'severity' => 'critical',
                'message' => "Overall business health juda past: {$result['overall_score']}/100",
                'score' => $result['overall_score'],
            ];
        }

        // Check money loss
        $moneyLoss = $result['algorithm_results']['money_loss'] ?? [];
        if (($moneyLoss['total_loss'] ?? 0) > 1000000) { // 1M+ loss
            $issues[] = [
                'type' => 'high_money_loss',
                'severity' => 'critical',
                'message' => "Oylik yo'qotish: ".number_format($moneyLoss['total_loss'])." so'm",
                'amount' => $moneyLoss['total_loss'],
            ];
        }

        // Check churn risk
        $churnRisk = $result['algorithm_results']['churn_risk'] ?? [];
        if (in_array($churnRisk['risk_level'] ?? '', ['critical', 'high'])) {
            $issues[] = [
                'type' => 'high_churn_risk',
                'severity' => 'critical',
                'message' => "Yuqori churn riski: {$churnRisk['risk_level']}",
                'risk_level' => $churnRisk['risk_level'],
            ];
        }

        // Check critical actions
        foreach ($result['prioritized_actions'] ?? [] as $action) {
            if ($action['priority'] === 'critical') {
                $issues[] = [
                    'type' => 'critical_action_needed',
                    'severity' => 'critical',
                    'message' => $action['title'],
                    'action' => $action,
                ];
            }
        }

        return $issues;
    }

    /**
     * Send critical alert
     */
    protected function sendCriticalAlert(array $issues, AiDiagnostic $diagnostic): void
    {
        // TODO: Implement notification sending
        Log::warning('Critical issues detected', [
            'business_id' => $this->business->id,
            'issues_count' => count($issues),
            'issues' => $issues,
        ]);

        // Dispatch notification job
        // Notification::send($this->business->users, new CriticalBusinessAlert($issues, $report));
    }

    /**
     * Send daily summary email
     */
    protected function sendDailySummary(array $result, AiDiagnostic $diagnostic): void
    {
        // TODO: Implement daily summary email
        Log::info('Daily summary prepared', [
            'business_id' => $this->business->id,
            'overall_score' => $result['overall_score'],
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Daily diagnostic job failed permanently', [
            'business_id' => $this->business->id,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send failure notification to admin
    }
}
