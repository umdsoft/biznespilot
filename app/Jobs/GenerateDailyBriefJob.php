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

class GenerateDailyBriefJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 600;

    public function __construct(
        public ?Business $business = null,
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::yesterday();
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
            $report = $reportingService->generateDailyBrief($business, $this->date);

            // Send notification
            $notificationService->sendReport($report);

            Log::info('Daily brief generated', [
                'business_id' => $business->id,
                'report_id' => $report->id,
                'date' => $this->date->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate daily brief', [
                'business_id' => $business->id,
                'date' => $this->date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function generateForScheduledReports(
        ReportingService $reportingService,
        NotificationService $notificationService
    ): void {
        // Get all scheduled daily reports that are due
        $scheduledReports = ScheduledReport::where('report_type', 'daily_brief')
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
                    $notificationService->sendReport($report);
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate scheduled daily brief', [
                    'scheduled_report_id' => $scheduledReport->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        Log::info('Daily briefs generated for scheduled reports', [
            'count' => $scheduledReports->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateDailyBriefJob failed', [
            'business_id' => $this->business?->id,
            'date' => $this->date?->format('Y-m-d'),
            'error' => $exception->getMessage(),
        ]);
    }
}
