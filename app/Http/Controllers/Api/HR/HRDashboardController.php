<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\EmployeeEngagement;
use App\Models\EmployeeOnboardingPlan;
use App\Models\FlightRisk;
use App\Models\HRAlert;
use App\Models\TurnoverRecord;
use App\Services\HR\EngagementService;
use App\Services\HR\FlightRiskService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * HR Dashboard API Controller
 *
 * Ushbu controller HR boshqaruv paneli uchun API endpointlarni taqdim etadi.
 * Asosiy metrikalar, alertlar va umumiy HR holatini qaytaradi.
 */
class HRDashboardController extends Controller
{
    public function __construct(
        protected EngagementService $engagementService,
        protected FlightRiskService $flightRiskService
    ) {}

    /**
     * Asosiy HR dashboard ma'lumotlari
     */
    public function index(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $metrics = $this->getDashboardMetrics($business);
        $alerts = $this->getRecentAlerts($business);
        $trends = $this->getTrends($business);
        $quickStats = $this->getQuickStats($business);

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'alerts' => $alerts,
                'trends' => $trends,
                'quick_stats' => $quickStats,
                'last_updated' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Asosiy HR metrikalari
     */
    protected function getDashboardMetrics(Business $business): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // Jami hodimlar soni
        $totalEmployees = DB::table('business_user')
            ->where('business_id', $business->id)
            ->whereNotNull('accepted_at')
            ->whereNull('left_at')
            ->count();

        // O'rtacha engagement ball
        $avgEngagement = EmployeeEngagement::where('business_id', $business->id)
            ->where('period_type', 'monthly')
            ->where('period_start', '>=', $monthStart)
            ->avg('overall_score') ?? 0;

        // Yuqori xavfli hodimlar soni
        $highRiskCount = FlightRisk::where('business_id', $business->id)
            ->whereIn('risk_level', [FlightRisk::LEVEL_HIGH, FlightRisk::LEVEL_CRITICAL])
            ->count();

        // Faol onboarding rejalar
        $activeOnboarding = EmployeeOnboardingPlan::where('business_id', $business->id)
            ->where('status', EmployeeOnboardingPlan::STATUS_ACTIVE)
            ->count();

        // Shu oydagi turnover
        $monthlyTurnover = TurnoverRecord::where('business_id', $business->id)
            ->whereBetween('termination_date', [$monthStart, $today])
            ->count();

        // Turnover rate hisoblash
        $turnoverRate = $totalEmployees > 0
            ? round(($monthlyTurnover / $totalEmployees) * 100, 2)
            : 0;

        // Ko'rilmagan alertlar soni
        $unreadAlerts = HRAlert::where('business_id', $business->id)
            ->where('status', HRAlert::STATUS_NEW)
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'avg_engagement_score' => round($avgEngagement, 1),
            'engagement_status' => $this->getEngagementStatus($avgEngagement),
            'high_risk_employees' => $highRiskCount,
            'high_risk_status' => $this->getRiskStatus($highRiskCount, $totalEmployees),
            'active_onboarding' => $activeOnboarding,
            'monthly_turnover' => $monthlyTurnover,
            'turnover_rate' => $turnoverRate,
            'turnover_status' => $this->getTurnoverStatus($turnoverRate),
            'unread_alerts' => $unreadAlerts,
        ];
    }

    /**
     * So'nggi alertlar
     */
    protected function getRecentAlerts(Business $business): array
    {
        return HRAlert::where('business_id', $business->id)
            ->whereIn('status', [HRAlert::STATUS_NEW, HRAlert::STATUS_SEEN])
            ->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($alert) => [
                'id' => $alert->id,
                'type' => $alert->alert_type,
                'title' => $alert->title,
                'message' => $alert->message,
                'priority' => $alert->priority,
                'priority_label' => $this->getPriorityLabel($alert->priority),
                'status' => $alert->status,
                'created_at' => $alert->created_at->format('d.m.Y H:i'),
                'created_ago' => $alert->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Trend ma'lumotlari
     */
    protected function getTrends(Business $business): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $prevMonth = Carbon::now()->subMonth()->startOfMonth();

        // Engagement trendi
        $currentEngagement = EmployeeEngagement::where('business_id', $business->id)
            ->where('period_type', 'monthly')
            ->where('period_start', '>=', $currentMonth)
            ->avg('overall_score') ?? 0;

        $prevEngagement = EmployeeEngagement::where('business_id', $business->id)
            ->where('period_type', 'monthly')
            ->whereBetween('period_start', [$prevMonth, $currentMonth])
            ->avg('overall_score') ?? 0;

        $engagementChange = $prevEngagement > 0
            ? round((($currentEngagement - $prevEngagement) / $prevEngagement) * 100, 1)
            : 0;

        // Risk trendi
        $currentHighRisk = FlightRisk::where('business_id', $business->id)
            ->whereIn('risk_level', [FlightRisk::LEVEL_HIGH, FlightRisk::LEVEL_CRITICAL])
            ->count();

        return [
            'engagement' => [
                'current' => round($currentEngagement, 1),
                'previous' => round($prevEngagement, 1),
                'change' => $engagementChange,
                'trend' => $engagementChange > 2 ? 'up' : ($engagementChange < -2 ? 'down' : 'stable'),
            ],
            'high_risk_count' => $currentHighRisk,
        ];
    }

    /**
     * Tezkor statistika
     */
    protected function getQuickStats(Business $business): array
    {
        $today = Carbon::today();

        // Bugungi ish yilliklari
        $todayAnniversaries = DB::table('business_user')
            ->where('business_id', $business->id)
            ->whereNotNull('accepted_at')
            ->whereNull('left_at')
            ->whereRaw('DATE_FORMAT(accepted_at, "%m-%d") = ?', [$today->format('m-d')])
            ->count();

        // Bugungi onboarding vazifalari
        $todayOnboardingTasks = DB::table('employee_onboarding_tasks')
            ->join('employee_onboarding_plans', 'employee_onboarding_tasks.plan_id', '=', 'employee_onboarding_plans.id')
            ->where('employee_onboarding_plans.business_id', $business->id)
            ->where('employee_onboarding_tasks.status', 'pending')
            ->whereDate('employee_onboarding_tasks.due_date', $today)
            ->count();

        // Kechikkan onboarding vazifalari
        $overdueOnboardingTasks = DB::table('employee_onboarding_tasks')
            ->join('employee_onboarding_plans', 'employee_onboarding_tasks.plan_id', '=', 'employee_onboarding_plans.id')
            ->where('employee_onboarding_plans.business_id', $business->id)
            ->where('employee_onboarding_tasks.status', 'pending')
            ->whereDate('employee_onboarding_tasks.due_date', '<', $today)
            ->count();

        // Engagement past hodimlar (60 dan kam ball)
        $lowEngagementCount = EmployeeEngagement::where('business_id', $business->id)
            ->where('period_type', 'monthly')
            ->where('period_start', '>=', Carbon::now()->startOfMonth())
            ->where('overall_score', '<', 60)
            ->count();

        return [
            'today_anniversaries' => $todayAnniversaries,
            'today_onboarding_tasks' => $todayOnboardingTasks,
            'overdue_onboarding_tasks' => $overdueOnboardingTasks,
            'low_engagement_employees' => $lowEngagementCount,
        ];
    }

    /**
     * Engagement status helper
     */
    protected function getEngagementStatus(float $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 65 => 'good',
            $score >= 50 => 'average',
            default => 'needs_attention',
        };
    }

    /**
     * Risk status helper
     */
    protected function getRiskStatus(int $highRiskCount, int $totalEmployees): string
    {
        if ($totalEmployees === 0) {
            return 'unknown';
        }

        $percentage = ($highRiskCount / $totalEmployees) * 100;

        return match (true) {
            $percentage >= 15 => 'critical',
            $percentage >= 10 => 'warning',
            $percentage >= 5 => 'elevated',
            default => 'normal',
        };
    }

    /**
     * Turnover status helper
     */
    protected function getTurnoverStatus(float $rate): string
    {
        return match (true) {
            $rate >= 10 => 'critical',
            $rate >= 5 => 'warning',
            $rate >= 2 => 'elevated',
            default => 'normal',
        };
    }

    /**
     * Priority label helper
     */
    protected function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'critical' => 'Juda muhim',
            'high' => 'Yuqori',
            'medium' => "O'rtacha",
            'low' => 'Past',
            default => $priority,
        };
    }

    /**
     * Hodimlar ro'yxati bilan engagement va risk ma'lumotlari
     */
    public function employeeOverview(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $filter = $request->input('filter', 'all'); // all, high_risk, low_engagement

        $query = DB::table('business_user')
            ->join('users', 'business_user.user_id', '=', 'users.id')
            ->leftJoin('employee_engagements', function ($join) {
                $join->on('business_user.user_id', '=', 'employee_engagements.user_id')
                    ->on('business_user.business_id', '=', 'employee_engagements.business_id')
                    ->where('employee_engagements.period_type', '=', 'monthly')
                    ->where('employee_engagements.period_start', '>=', Carbon::now()->startOfMonth());
            })
            ->leftJoin('flight_risks', function ($join) {
                $join->on('business_user.user_id', '=', 'flight_risks.user_id')
                    ->on('business_user.business_id', '=', 'flight_risks.business_id');
            })
            ->where('business_user.business_id', $businessId)
            ->whereNotNull('business_user.accepted_at')
            ->whereNull('business_user.left_at')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'business_user.department',
                'business_user.position',
                'business_user.accepted_at as start_date',
                'employee_engagements.overall_score as engagement_score',
                'employee_engagements.engagement_level',
                'flight_risks.risk_score',
                'flight_risks.risk_level',
            ]);

        // Filterlar
        if ($filter === 'high_risk') {
            $query->whereIn('flight_risks.risk_level', ['high', 'critical']);
        } elseif ($filter === 'low_engagement') {
            $query->where('employee_engagements.overall_score', '<', 60);
        }

        // Sorting
        $query->orderBy($sortBy, $sortDir);

        $employees = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    /**
     * Department bo'yicha statistika
     */
    public function departmentStats(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $departments = DB::table('business_user')
            ->where('business_id', $businessId)
            ->whereNotNull('accepted_at')
            ->whereNull('left_at')
            ->whereNotNull('department')
            ->select('department')
            ->distinct()
            ->pluck('department');

        $stats = [];

        foreach ($departments as $dept) {
            // Department hodimlar soni
            $employeeCount = DB::table('business_user')
                ->where('business_id', $businessId)
                ->where('department', $dept)
                ->whereNotNull('accepted_at')
                ->whereNull('left_at')
                ->count();

            // O'rtacha engagement
            $avgEngagement = EmployeeEngagement::where('business_id', $businessId)
                ->whereHas('user', function ($q) use ($businessId, $dept) {
                    $q->whereHas('businessUsers', function ($q2) use ($businessId, $dept) {
                        $q2->where('business_id', $businessId)
                            ->where('department', $dept);
                    });
                })
                ->where('period_type', 'monthly')
                ->where('period_start', '>=', Carbon::now()->startOfMonth())
                ->avg('overall_score') ?? 0;

            // High risk count
            $highRiskCount = FlightRisk::where('business_id', $businessId)
                ->whereHas('user', function ($q) use ($businessId, $dept) {
                    $q->whereHas('businessUsers', function ($q2) use ($businessId, $dept) {
                        $q2->where('business_id', $businessId)
                            ->where('department', $dept);
                    });
                })
                ->whereIn('risk_level', ['high', 'critical'])
                ->count();

            $stats[] = [
                'department' => $dept,
                'department_label' => $this->getDepartmentLabel($dept),
                'employee_count' => $employeeCount,
                'avg_engagement' => round($avgEngagement, 1),
                'high_risk_count' => $highRiskCount,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Department label helper
     */
    protected function getDepartmentLabel(string $dept): string
    {
        return match ($dept) {
            'sales_head' => 'Savdo boshqaruvi',
            'sales_operator' => 'Savdo operatori',
            'operator' => 'Operator',
            'marketing' => 'Marketing',
            'finance' => 'Moliya',
            'hr' => 'HR',
            default => ucfirst($dept),
        };
    }
}
