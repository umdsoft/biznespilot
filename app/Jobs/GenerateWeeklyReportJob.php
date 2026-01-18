<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\ScheduledReport;
use App\Services\NotificationService;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 180;

    public int $timeout = 900;

    public function __construct(
        public ?Business $business = null,
        public ?Carbon $endDate = null
    ) {
        $this->endDate = $endDate ?? Carbon::yesterday();
    }

    public function handle(ReportingService $reportingService, NotificationService $notificationService): void
    {
        if ($this->business) {
            $this->generateForBusiness($reportingService, $notificationService, $this->business);
        } else {
            $this->generateForScheduledReports($reportingService, $notificationService);
        }
    }

    protected function generateForBusiness(
        ReportingService $reportingService,
        NotificationService $notificationService,
        Business $business
    ): void {
        try {
            $report = $reportingService->generateWeeklySummary($business, $this->endDate);

            // Generate PDF
            try {
                $reportingService->exportToPDF($report);
            } catch (\Exception $e) {
                Log::warning('PDF generation failed', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send notification
            $notificationService->sendReport($report);

            Log::info('Weekly report generated', [
                'business_id' => $business->id,
                'report_id' => $report->id,
                'period' => $report->getPeriodLabel(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate weekly report', [
                'business_id' => $business->id,
                'end_date' => $this->endDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function generateForScheduledReports(
        ReportingService $reportingService,
        NotificationService $notificationService
    ): void {
        // Get all scheduled weekly reports that are due
        $scheduledReports = ScheduledReport::where('report_type', 'weekly_summary')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('next_scheduled_at')
                    ->orWhere('next_scheduled_at', '<=', now());
            })
            ->get();

        foreach ($scheduledReports as $scheduledReport) {
            try {
                $report = $reportingService->generateReport($scheduledReport);

                if ($report) {
                    // Generate PDF
                    try {
                        $reportingService->exportToPDF($report);
                    } catch (\Exception $e) {
                        Log::warning('PDF generation failed', [
                            'report_id' => $report->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $notificationService->sendReport($report);
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate scheduled weekly report', [
                    'scheduled_report_id' => $scheduledReport->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        Log::info('Weekly reports generated for scheduled reports', [
            'count' => $scheduledReports->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateWeeklyReportJob failed', [
            'business_id' => $this->business?->id,
            'end_date' => $this->endDate?->format('Y-m-d'),
            'error' => $exception->getMessage(),
        ]);
    }
}
