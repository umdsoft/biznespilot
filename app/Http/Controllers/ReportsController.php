<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\GeneratedReport;
use App\Models\MarketingChannel;
use App\Models\MarketingContent;
use App\Models\Offer;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\Sale;
use App\Services\Reports\ReportGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    use HasCurrentBusiness;

    protected ReportGeneratorService $reportGenerator;

    public function __construct(ReportGeneratorService $reportGenerator)
    {
        $this->reportGenerator = $reportGenerator;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        // Date range filter
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Overview Stats
        $stats = [
            'total_sales' => Sale::where('business_id', $currentBusiness->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'total_revenue' => Sale::where('business_id', $currentBusiness->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'dream_buyers' => DreamBuyer::where('business_id', $currentBusiness->id)->count(),
            'active_offers' => Offer::where('business_id', $currentBusiness->id)
                ->where('status', 'active')
                ->count(),
            'marketing_channels' => MarketingChannel::where('business_id', $currentBusiness->id)
                ->where('is_active', true)
                ->count(),
            'competitors_tracked' => Competitor::where('business_id', $currentBusiness->id)
                ->where('is_active', true)
                ->count(),
        ];

        // Sales Trend (last 7 days)
        $salesTrend = Sale::where('business_id', $currentBusiness->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Sales by Status - sales table doesn't have status column, return empty
        // In future, could aggregate by customer_id or other available fields
        $salesByStatus = collect([
            ['status' => 'completed', 'count' => $stats['total_sales']],
        ]);

        // Top Marketing Channels
        $topChannels = MarketingChannel::where('business_id', $currentBusiness->id)
            ->select('name', 'platform', 'monthly_budget')
            ->orderBy('monthly_budget', 'desc')
            ->take(5)
            ->get()
            ->map(function ($channel) {
                return [
                    'name' => $channel->name,
                    'platform' => $channel->platform,
                    'monthly_budget' => (float) $channel->monthly_budget,
                ];
            });

        // Marketing Content Performance
        // Note: MarketingContent model doesn't exist in this project, using empty collection
        $contentStats = collect([]);

        // Offers Performance
        $offersPerformance = Offer::where('business_id', $currentBusiness->id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                    'avg_conversion' => 0, // Conversion rate calculation will be added later
                ];
            });

        // Competitor Analysis
        $competitorStats = Competitor::where('business_id', $currentBusiness->id)
            ->select(
                DB::raw('COUNT(*) as total')
            )
            ->first();

        // Generate real-time algorithmic report data automatically
        $realtimeData = $this->reportGenerator->generateRealtime($currentBusiness);

        // Get recent generated reports
        $recentReports = GeneratedReport::where('business_id', $currentBusiness->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($r) => $r->getSummary());

        return Inertia::render('Business/Reports/Index', [
            'stats' => $stats,
            'salesTrend' => $salesTrend,
            'salesByStatus' => $salesByStatus,
            'topChannels' => $topChannels,
            'contentStats' => $contentStats,
            'offersPerformance' => $offersPerformance,
            'competitorStats' => [
                'total' => $competitorStats->total ?? 0,
            ],
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            // Algorithmic report data
            'realtimeData' => $realtimeData,
            'recentReports' => $recentReports,
        ]);
    }

    /**
     * Algorithmic Reports Dashboard
     */
    public function algorithmicReports(Request $request)
    {
        $business = $this->getCurrentBusiness();

        // Get recent generated reports
        $reports = GeneratedReport::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($r) => $r->getSummary());

        // Get report schedules
        $schedules = ReportSchedule::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'frequency' => $s->frequency,
                    'frequency_label' => $s->frequency_label,
                    'is_active' => $s->is_active,
                    'last_sent_at' => $s->last_sent_at?->format('d.m.Y H:i'),
                    'next_scheduled_at' => $s->next_scheduled_at?->format('d.m.Y H:i'),
                ];
            });

        // Get report templates
        $templates = ReportTemplate::where(function ($q) use ($business) {
            $q->whereNull('business_id')
              ->orWhere('business_id', $business->id);
        })
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'code' => $t->code,
                    'description' => $t->description,
                    'type' => $t->type,
                    'category' => $t->category,
                    'category_label' => $t->category_label,
                    'is_default' => $t->is_default,
                ];
            });

        // Get real-time summary
        $realtimeSummary = $this->reportGenerator->generateRealtime($business);

        return Inertia::render('Business/Reports/Algorithmic', [
            'reports' => $reports,
            'schedules' => $schedules,
            'templates' => $templates,
            'realtimeSummary' => $realtimeSummary,
        ]);
    }

    /**
     * Generate a new report
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'template_id' => 'nullable|exists:report_templates,id',
        ]);

        $business = $this->getCurrentBusiness();

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $template = $request->template_id
                ? ReportTemplate::find($request->template_id)
                : null;

            $report = $this->reportGenerator->generate(
                $business,
                $startDate,
                $endDate,
                GeneratedReport::TYPE_MANUAL,
                $template
            );

            return response()->json([
                'success' => true,
                'report' => $report->getSummary(),
                'message' => 'Hisobot muvaffaqiyatli yaratildi',
            ]);

        } catch (\Exception $e) {
            \Log::error('Report generation failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hisobot yaratishda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single report details
     */
    public function showReport($id)
    {
        $business = $this->getCurrentBusiness();

        $report = GeneratedReport::where('business_id', $business->id)
            ->findOrFail($id);

        $report->recordView();

        return Inertia::render('Business/Reports/Show', [
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'type' => $report->type,
                'category' => $report->category,
                'period_start' => $report->period_start->format('Y-m-d'),
                'period_end' => $report->period_end->format('Y-m-d'),
                'period_label' => $report->getPeriodLabel(),
                'health_score' => $report->health_score,
                'health_score_label' => $report->health_score_label,
                'health_score_color' => $report->health_score_color,
                'health_breakdown' => $report->health_breakdown,
                'metrics_data' => $report->metrics_data,
                'trends_data' => $report->trends_data,
                'insights' => $report->insights,
                'recommendations' => $report->recommendations,
                'comparisons' => $report->comparisons,
                'anomalies' => $report->anomalies,
                'content_text' => $report->content_text,
                'content_html' => $report->content_html,
                'status' => $report->status,
                'status_label' => $report->status_label,
                'created_at' => $report->created_at->format('d.m.Y H:i'),
                'generation_time_ms' => $report->generation_time_ms,
                'view_count' => $report->view_count,
                'has_pdf' => $report->hasPdf(),
                'pdf_url' => $report->getPdfUrl(),
                'excel_url' => $report->getExcelUrl(),
            ],
        ]);
    }

    /**
     * Get report content as JSON (for API)
     */
    public function getReportData($id)
    {
        $business = $this->getCurrentBusiness();

        $report = GeneratedReport::where('business_id', $business->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'report' => $report->getSummary(),
            'metrics' => $report->metrics_data,
            'trends' => $report->trends_data,
            'insights' => $report->insights,
            'recommendations' => $report->recommendations,
        ]);
    }

    /**
     * Get real-time report data
     */
    public function getRealtime()
    {
        $business = $this->getCurrentBusiness();

        try {
            $data = $this->reportGenerator->generateRealtime($business);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update report schedule
     */
    public function saveSchedule(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:report_schedules,id',
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'send_time' => 'required|date_format:H:i',
            'period' => 'required|in:daily,weekly,monthly,quarterly',
            'delivery_channels' => 'required|array',
            'telegram_chat_id' => 'nullable|string',
            'email' => 'nullable|email',
            'is_active' => 'boolean',
        ]);

        $business = $this->getCurrentBusiness();

        $data = [
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'name' => $request->name,
            'frequency' => $request->frequency,
            'day_of_week' => $request->day_of_week,
            'day_of_month' => $request->day_of_month,
            'send_time' => $request->send_time . ':00',
            'period' => $request->period,
            'delivery_channels' => $request->delivery_channels,
            'telegram_chat_id' => $request->telegram_chat_id,
            'email' => $request->email,
            'is_active' => $request->is_active ?? true,
        ];

        if ($request->id) {
            $schedule = ReportSchedule::where('business_id', $business->id)
                ->findOrFail($request->id);
            $schedule->update($data);
        } else {
            $schedule = ReportSchedule::create($data);
        }

        // Calculate next scheduled time
        $schedule->calculateNextScheduledAt();

        return response()->json([
            'success' => true,
            'schedule' => [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'frequency_label' => $schedule->frequency_label,
                'next_scheduled_at' => $schedule->next_scheduled_at?->format('d.m.Y H:i'),
            ],
            'message' => 'Jadval saqlandi',
        ]);
    }

    /**
     * Delete report schedule
     */
    public function deleteSchedule($id)
    {
        $business = $this->getCurrentBusiness();

        $schedule = ReportSchedule::where('business_id', $business->id)
            ->findOrFail($id);

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadval o\'chirildi',
        ]);
    }

    /**
     * Toggle schedule active status
     */
    public function toggleSchedule($id)
    {
        $business = $this->getCurrentBusiness();

        $schedule = ReportSchedule::where('business_id', $business->id)
            ->findOrFail($id);

        $schedule->is_active = !$schedule->is_active;
        $schedule->save();

        if ($schedule->is_active) {
            $schedule->calculateNextScheduledAt();
        }

        return response()->json([
            'success' => true,
            'is_active' => $schedule->is_active,
            'message' => $schedule->is_active ? 'Jadval faollashtirildi' : 'Jadval to\'xtatildi',
        ]);
    }

    /**
     * Delete a generated report
     */
    public function deleteReport($id)
    {
        $business = $this->getCurrentBusiness();

        $report = GeneratedReport::where('business_id', $business->id)
            ->findOrFail($id);

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hisobot o\'chirildi',
        ]);
    }

    /**
     * Get reports list with pagination
     */
    public function getReports(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $query = GeneratedReport::where('business_id', $business->id)
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('period_start', [$request->start_date, $request->end_date]);
        }

        $reports = $query->paginate(15);

        return response()->json([
            'success' => true,
            'reports' => $reports->map(fn($r) => $r->getSummary()),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }
}
