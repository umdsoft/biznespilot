<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\GeneratedReport;
use App\Models\ScheduledReport;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected ReportingService $reportingService
    ) {}

    public function index(Request $request): Response
    {
        $business = $this->getCurrentBusiness();

        $reports = GeneratedReport::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $scheduledReports = ScheduledReport::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Dashboard/Reports/Index', [
            'reports' => $reports,
            'scheduledReports' => $scheduledReports,
        ]);
    }

    public function show(string $id): Response
    {
        $business = $this->getCurrentBusiness();
        $report = GeneratedReport::where('business_id', $business->id)->findOrFail($id);

        return Inertia::render('Dashboard/Reports/Show', [
            'report' => $report,
        ]);
    }

    public function download(string $id)
    {
        $business = $this->getCurrentBusiness();
        $report = GeneratedReport::where('business_id', $business->id)->findOrFail($id);

        if (! $report->hasPdf()) {
            // Generate PDF if not exists
            $this->reportingService->exportToPDF($report);
            $report->refresh();
        }

        if (! $report->hasPdf()) {
            return back()->with('error', 'PDF fayl topilmadi');
        }

        $report->incrementDownloadCount();

        return Storage::download($report->pdf_path, $report->title.'.pdf');
    }

    public function generateDaily(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $business = $this->getCurrentBusiness();
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $report = $this->reportingService->generateDailyBrief($business, $date);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function generateWeekly(Request $request)
    {
        $request->validate([
            'end_date' => 'nullable|date',
        ]);

        $business = $this->getCurrentBusiness();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::today();

        $report = $this->reportingService->generateWeeklySummary($business, $endDate);

        // Generate PDF
        try {
            $this->reportingService->exportToPDF($report);
        } catch (\Exception $e) {
            // PDF generation is optional
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function generateMonthly(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date',
        ]);

        $business = $this->getCurrentBusiness();
        $month = $request->month ? Carbon::parse($request->month) : Carbon::today()->startOfMonth();

        $report = $this->reportingService->generateMonthlyReport($business, $month);

        // Generate PDF
        try {
            $this->reportingService->exportToPDF($report);
        } catch (\Exception $e) {
            // PDF generation is optional
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function generateQuarterly(Request $request)
    {
        $request->validate([
            'quarter' => 'nullable|integer|min:1|max:4',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        $business = $this->getCurrentBusiness();
        $quarter = $request->quarter ?? Carbon::today()->quarter;
        $year = $request->year ?? Carbon::today()->year;

        $report = $this->reportingService->generateQuarterlyReview($business, $quarter, $year);

        // Generate PDF
        try {
            $this->reportingService->exportToPDF($report);
        } catch (\Exception $e) {
            // PDF generation is optional
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    // Scheduled Reports Management
    public function schedules(): Response
    {
        $business = $this->getCurrentBusiness();

        $schedules = ScheduledReport::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Dashboard/Reports/Schedules', [
            'schedules' => $schedules,
        ]);
    }

    public function createSchedule(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string|in:daily_brief,weekly_summary,monthly_report,quarterly_review',
            'frequency' => 'required|string|in:daily,weekly,monthly,quarterly',
            'schedule_time' => 'required|string',
            'schedule_day' => 'nullable|integer|min:0|max:6',
            'schedule_date' => 'nullable|integer|min:1|max:28',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
        ]);

        $business = $this->getCurrentBusiness();

        $schedule = ScheduledReport::create([
            'business_id' => $business->id,
            'name' => $request->name,
            'report_type' => $request->report_type,
            'frequency' => $request->frequency,
            'schedule_time' => $request->schedule_time,
            'schedule_day' => $request->schedule_day,
            'schedule_date' => $request->schedule_date,
            'recipients' => $request->recipients ?? [],
            'is_active' => true,
            'next_scheduled_at' => $this->calculateNextSchedule($request),
        ]);

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
        ]);
    }

    public function updateSchedule(Request $request, string $id)
    {
        $request->validate([
            'name' => 'string|max:255',
            'schedule_time' => 'string',
            'schedule_day' => 'nullable|integer|min:0|max:6',
            'schedule_date' => 'nullable|integer|min:1|max:28',
            'recipients' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $business = $this->getCurrentBusiness();
        $schedule = ScheduledReport::where('business_id', $business->id)->findOrFail($id);

        $schedule->update($request->only([
            'name', 'schedule_time', 'schedule_day', 'schedule_date', 'recipients', 'is_active',
        ]));

        if ($schedule->is_active) {
            $schedule->update([
                'next_scheduled_at' => $schedule->calculateNextScheduledAt(),
            ]);
        }

        return response()->json([
            'success' => true,
            'schedule' => $schedule->fresh(),
        ]);
    }

    public function deleteSchedule(string $id)
    {
        $business = $this->getCurrentBusiness();
        $schedule = ScheduledReport::where('business_id', $business->id)->findOrFail($id);

        $schedule->delete();

        return response()->json(['success' => true]);
    }

    public function runSchedule(string $id)
    {
        $business = $this->getCurrentBusiness();
        $schedule = ScheduledReport::where('business_id', $business->id)->findOrFail($id);

        $report = $this->reportingService->generateReport($schedule);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    protected function calculateNextSchedule(Request $request): Carbon
    {
        $time = Carbon::parse($request->schedule_time);
        $now = Carbon::now();

        return match ($request->frequency) {
            'daily' => $now->copy()->setTime($time->hour, $time->minute)->addDay(),
            'weekly' => $now->copy()->next($request->schedule_day)->setTime($time->hour, $time->minute),
            'monthly' => $now->copy()->startOfMonth()->addMonth()->addDays($request->schedule_date - 1)->setTime($time->hour, $time->minute),
            'quarterly' => $now->copy()->startOfQuarter()->addQuarter()->setTime($time->hour, $time->minute),
            default => $now->addDay(),
        };
    }
}
