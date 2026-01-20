<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\SalesAchievementDefinition;
use App\Models\SalesBonusCalculation;
use App\Models\SalesBonusSetting;
use App\Models\SalesKpiPeriodSummary;
use App\Models\SalesKpiSetting;
use App\Models\SalesKpiTemplateSet;
use App\Models\SalesKpiUserTarget;
use App\Models\SalesLeaderboardEntry;
use App\Models\SalesPenalty;
use App\Models\SalesPenaltyRule;
use App\Models\SalesSetupProgress;
use App\Models\SalesUserAchievement;
use App\Models\SalesUserPoints;
use App\Models\SalesUserStreak;
use App\Services\Sales\AchievementService;
use App\Services\Sales\BonusCalculationService;
use App\Services\Sales\KpiCalculationService;
use App\Services\Sales\KpiSettingsService;
use App\Services\Sales\LeaderboardService;
use App\Services\Sales\PenaltyService;
use App\Services\Sales\SetupWizardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesKpiController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected KpiCalculationService $kpiCalculationService,
        protected KpiSettingsService $kpiSettingsService,
        protected BonusCalculationService $bonusService,
        protected PenaltyService $penaltyService,
        protected LeaderboardService $leaderboardService,
        protected AchievementService $achievementService,
        protected SetupWizardService $setupWizardService
    ) {}

    /**
     * KPI Dashboard - Asosiy sahifa
     */
    public function dashboard(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $periodType = $request->get('period', 'monthly');

        // Setup kerakligini tekshirish
        if ($this->setupWizardService->needsSetup($businessId)) {
            return redirect()->route('sales-head.sales-kpi.setup');
        }

        // Jamoa a'zolari uchun KPI
        $teamMembers = $this->getTeamMembersKpi($businessId, $periodType);

        // Leaderboard
        $leaderboard = $this->leaderboardService->getLeaderboard($businessId, $periodType, now(), 5);

        // Umumiy statistika
        $stats = $this->getOverallStats($businessId, $periodType);

        // So'nggi yutuqlar
        $recentAchievements = SalesUserAchievement::forBusiness($businessId)
            ->with(['user:id,name', 'achievement:id,name,icon,tier,points'])
            ->recent(5)
            ->get();

        return Inertia::render('SalesHead/SalesKPI/Dashboard', [
            'teamMembers' => $teamMembers,
            'leaderboard' => $leaderboard,
            'stats' => $stats,
            'recentAchievements' => $recentAchievements,
            'periodType' => $periodType,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * KPI Sozlamalari
     */
    public function settings(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;

        $kpiSettings = SalesKpiSetting::forBusiness($businessId)
            ->ordered()
            ->get();

        $bonusSettings = SalesBonusSetting::forBusiness($businessId)
            ->ordered()
            ->get();

        $penaltyRules = SalesPenaltyRule::forBusiness($businessId)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('SalesHead/SalesKPI/Settings', [
            'kpiSettings' => $kpiSettings,
            'bonusSettings' => $bonusSettings,
            'penaltyRules' => $penaltyRules,
            'kpiTypes' => SalesKpiSetting::KPI_TYPES,
            'bonusTypes' => SalesBonusSetting::BONUS_TYPES,
            'penaltyTriggers' => SalesPenaltyRule::TRIGGER_EVENTS,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * KPI Sozlamasini saqlash
     */
    public function storeKpiSetting(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'kpi_type' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|integer|min:1|max:100',
            'target_min' => 'required|numeric|min:0',
            'target_good' => 'nullable|numeric|min:0',
            'target_excellent' => 'nullable|numeric|min:0',
            'measurement_unit' => 'required|string',
            'period_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        SalesKpiSetting::updateOrCreate(
            [
                'business_id' => $business->id,
                'kpi_type' => $validated['kpi_type'],
            ],
            $validated
        );

        return back()->with('success', 'KPI sozlamasi saqlandi');
    }

    /**
     * KPI Sozlamasini yangilash
     */
    public function updateKpiSetting(Request $request, string $kpiSettingId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $kpiSetting = SalesKpiSetting::forBusiness($business->id)->findOrFail($kpiSettingId);

        $validated = $request->validate([
            'kpi_type' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|integer|min:1|max:100',
            'target_min' => 'required|numeric|min:0',
            'target_good' => 'nullable|numeric|min:0',
            'target_excellent' => 'nullable|numeric|min:0',
            'measurement_unit' => 'required|string',
            'period_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $kpiSetting->update($validated);

        return back()->with('success', 'KPI sozlamasi yangilandi');
    }

    /**
     * KPI Sozlamasini o'chirish
     */
    public function destroyKpiSetting(string $kpiSettingId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $kpiSetting = SalesKpiSetting::forBusiness($business->id)->findOrFail($kpiSettingId);
        $kpiSetting->delete();

        return back()->with('success', 'KPI sozlamasi o\'chirildi');
    }

    /**
     * Bonus sozlamasini saqlash
     */
    public function storeBonusSetting(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bonus_type' => 'required|in:fixed,revenue_percentage,kpi_based,tiered',
            'base_amount' => 'required|numeric|min:0',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'tiers' => 'nullable|array',
            'min_kpi_score' => 'required|integer|min:0|max:100',
            'min_working_days' => 'required|integer|min:0|max:31',
            'calculation_period' => 'required|in:monthly,quarterly',
            'applies_to_roles' => 'nullable|array',
            'requires_approval' => 'boolean',
            'auto_calculate' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['business_id'] = $business->id;
        $validated['sort_order'] = SalesBonusSetting::forBusiness($business->id)->count() + 1;

        SalesBonusSetting::create($validated);

        return back()->with('success', 'Bonus sozlamasi yaratildi');
    }

    /**
     * Bonus sozlamasini yangilash
     */
    public function updateBonusSetting(Request $request, string $bonusSettingId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $bonusSetting = SalesBonusSetting::forBusiness($business->id)->findOrFail($bonusSettingId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bonus_type' => 'required|in:fixed,revenue_percentage,kpi_based,tiered',
            'base_amount' => 'required|numeric|min:0',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'tiers' => 'nullable|array',
            'min_kpi_score' => 'required|integer|min:0|max:100',
            'min_working_days' => 'required|integer|min:0|max:31',
            'calculation_period' => 'required|in:monthly,quarterly',
            'applies_to_roles' => 'nullable|array',
            'requires_approval' => 'boolean',
            'auto_calculate' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $bonusSetting->update($validated);

        return back()->with('success', 'Bonus sozlamasi yangilandi');
    }

    /**
     * Bonus sozlamasini o'chirish
     */
    public function destroyBonusSetting(string $bonusSettingId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $bonusSetting = SalesBonusSetting::forBusiness($business->id)->findOrFail($bonusSettingId);
        $bonusSetting->delete();

        return back()->with('success', 'Bonus sozlamasi o\'chirildi');
    }

    /**
     * Jarima qoidasini saqlash
     */
    public function storePenaltyRule(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:crm_discipline,performance,attendance,customer_service',
            'trigger_type' => 'nullable|in:auto,manual',
            'trigger_event' => 'nullable|string',
            'penalty_type' => 'required|in:fixed,percentage_of_bonus,warning_only',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|numeric|min:0|max:100',
            'warning_before_penalty' => 'boolean',
            'warnings_before_penalty' => 'nullable|integer|min:0|max:5',
            'warning_validity_days' => 'nullable|integer|min:1',
            'max_per_day' => 'nullable|integer|min:1',
            'max_per_month' => 'nullable|integer|min:1',
            'allow_appeal' => 'boolean',
            'appeal_deadline_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Form dan kelgan maydonlarni moslashtirish
        $data = [
            'business_id' => $business->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'trigger_type' => $validated['trigger_event'] ? 'auto' : 'manual',
            'trigger_event' => $validated['trigger_event'] ?? null,
            'penalty_type' => $validated['penalty_type'],
            'penalty_amount' => $validated['penalty_amount'] ?? 0,
            'penalty_percentage' => $validated['penalty_percentage'] ?? 0,
            'warning_before_penalty' => $validated['warning_before_penalty'] ?? false,
            'warnings_before_penalty' => $validated['warnings_before_penalty'] ?? 1,
            'warning_validity_days' => $validated['warning_validity_days'] ?? 30,
            'max_per_day' => $validated['max_per_day'] ?? null,
            'max_per_month' => $validated['max_per_month'] ?? null,
            'allow_appeal' => $validated['allow_appeal'] ?? true,
            'appeal_deadline_days' => $validated['appeal_deadline_days'] ?? 3,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => SalesPenaltyRule::forBusiness($business->id)->count() + 1,
        ];

        SalesPenaltyRule::create($data);

        return back()->with('success', 'Jarima qoidasi yaratildi');
    }

    /**
     * Jarima qoidasini yangilash
     */
    public function updatePenaltyRule(Request $request, string $penaltyRuleId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $penaltyRule = SalesPenaltyRule::forBusiness($business->id)->findOrFail($penaltyRuleId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:crm_discipline,performance,attendance,customer_service',
            'trigger_type' => 'nullable|in:auto,manual',
            'trigger_event' => 'nullable|string',
            'penalty_type' => 'required|in:fixed,percentage_of_bonus,warning_only',
            'penalty_amount' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|numeric|min:0|max:100',
            'warning_before_penalty' => 'boolean',
            'warnings_before_penalty' => 'nullable|integer|min:0|max:5',
            'warning_validity_days' => 'nullable|integer|min:1',
            'max_per_day' => 'nullable|integer|min:1',
            'max_per_month' => 'nullable|integer|min:1',
            'allow_appeal' => 'boolean',
            'appeal_deadline_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Form dan kelgan maydonlarni moslashtirish
        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'trigger_type' => $validated['trigger_event'] ? 'auto' : 'manual',
            'trigger_event' => $validated['trigger_event'] ?? null,
            'penalty_type' => $validated['penalty_type'],
            'penalty_amount' => $validated['penalty_amount'] ?? 0,
            'penalty_percentage' => $validated['penalty_percentage'] ?? 0,
            'warning_before_penalty' => $validated['warning_before_penalty'] ?? false,
            'warnings_before_penalty' => $validated['warnings_before_penalty'] ?? 1,
            'warning_validity_days' => $validated['warning_validity_days'] ?? 30,
            'max_per_day' => $validated['max_per_day'] ?? null,
            'max_per_month' => $validated['max_per_month'] ?? null,
            'allow_appeal' => $validated['allow_appeal'] ?? true,
            'appeal_deadline_days' => $validated['appeal_deadline_days'] ?? 3,
            'is_active' => $validated['is_active'] ?? true,
        ];

        $penaltyRule->update($data);

        return back()->with('success', 'Jarima qoidasi yangilandi');
    }

    /**
     * Jarima qoidasini o'chirish
     */
    public function destroyPenaltyRule(string $penaltyRuleId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $penaltyRule = SalesPenaltyRule::forBusiness($business->id)->findOrFail($penaltyRuleId);
        $penaltyRule->delete();

        return back()->with('success', 'Jarima qoidasi o\'chirildi');
    }

    /**
     * Maqsadlar sahifasi
     */
    public function targets(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $kpiSettings = SalesKpiSetting::forBusiness($businessId)->active()->ordered()->get();
        $teamMembers = $this->getTeamMembers($businessId);

        // Period sanalarini hisoblash
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Mavjud maqsadlar
        $targets = SalesKpiUserTarget::forBusiness($businessId)
            ->where('period_type', 'monthly')
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->get();

        return Inertia::render('SalesHead/SalesKPI/Targets', [
            'kpiSettings' => $kpiSettings,
            'teamMembers' => $teamMembers,
            'targets' => $targets,
            'month' => (int) $month,
            'year' => (int) $year,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Maqsadlarni saqlash
     */
    public function storeTargets(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'targets' => 'required|array',
            'targets.*.user_id' => 'required|uuid',
            'targets.*.kpi_setting_id' => 'required|uuid',
            'targets.*.target_value' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024',
        ]);

        $periodStart = Carbon::create($validated['year'], $validated['month'], 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        DB::transaction(function () use ($business, $validated, $periodStart, $periodEnd) {
            foreach ($validated['targets'] as $target) {
                SalesKpiUserTarget::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'user_id' => $target['user_id'],
                        'kpi_setting_id' => $target['kpi_setting_id'],
                        'period_type' => 'monthly',
                        'period_start' => $periodStart->format('Y-m-d'),
                    ],
                    [
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'target_value' => $target['target_value'],
                        'status' => 'active',
                    ]
                );
            }
        });

        return back()->with('success', 'Maqsadlar saqlandi');
    }

    /**
     * Bonuslar sahifasi
     */
    public function bonuses(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $status = $request->get('status');

        $query = SalesBonusCalculation::forBusiness($businessId)
            ->with(['user:id,name', 'approvedBy:id,name'])
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month);

        if ($status) {
            $query->where('status', $status);
        }

        $bonuses = $query->orderByDesc('final_amount')->get();

        // Umumiy bonus statistikasi
        $stats = [
            'total_calculated' => $bonuses->sum('final_amount'),
            'total_approved' => $bonuses->where('status', 'approved')->sum('final_amount'),
            'pending_count' => $bonuses->where('status', 'pending')->count(),
            'approved_count' => $bonuses->where('status', 'approved')->count(),
        ];

        return Inertia::render('SalesHead/SalesKPI/Bonuses', [
            'bonuses' => $bonuses,
            'stats' => $stats,
            'month' => (int) $month,
            'year' => (int) $year,
            'currentStatus' => $status,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Bonusni tasdiqlash
     */
    public function approveBonus(Request $request, string $bonusId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $bonus = SalesBonusCalculation::forBusiness($business->id)->findOrFail($bonusId);

        $validated = $request->validate([
            'approved_bonus' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $bonus->approve($validated['approved_bonus'], auth()->id(), $validated['notes']);

        return back()->with('success', 'Bonus tasdiqlandi');
    }

    /**
     * Jarimalar sahifasi
     */
    public function penalties(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $status = $request->get('status');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = SalesPenalty::forBusiness($businessId)
            ->with(['user:id,name', 'penaltyRule:id,name,category,penalty_type'])
            ->whereBetween('triggered_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $penalties = $query->orderByDesc('triggered_at')->get();

        // Ogohlantirishlar
        $warnings = \App\Models\SalesPenaltyWarning::forBusiness($businessId)
            ->with(['user:id,name', 'penaltyRule:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get();

        // Jarima qoidalari
        $penaltyRules = SalesPenaltyRule::forBusiness($businessId)
            ->orderBy('sort_order')
            ->get();

        // Jamoa a'zolari
        $teamMembers = $this->getTeamMembers($businessId);

        // Statistika
        $stats = [
            'total_count' => $penalties->count(),
            'total_amount' => $penalties->where('status', 'confirmed')->sum('amount'),
            'pending_count' => $penalties->where('status', 'pending')->count(),
            'appealed_count' => $penalties->where('status', 'appealed')->count(),
            'auto_count' => $penalties->where('is_auto', true)->count(),
        ];

        return Inertia::render('SalesHead/SalesKPI/Penalties', [
            'penalties' => $penalties,
            'warnings' => $warnings,
            'penaltyRules' => $penaltyRules,
            'teamMembers' => $teamMembers,
            'stats' => $stats,
            'month' => (int) $month,
            'year' => (int) $year,
            'currentStatus' => $status,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Jarimani tasdiqlash
     */
    public function confirmPenalty(string $penaltyId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $penalty = SalesPenalty::forBusiness($business->id)->findOrFail($penaltyId);
        $penalty->confirm(auth()->id());

        return back()->with('success', 'Jarima tasdiqlandi');
    }

    /**
     * Jarima shikoyatini ko'rib chiqish
     */
    public function reviewAppeal(Request $request, string $penaltyId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $penalty = SalesPenalty::forBusiness($business->id)->findOrFail($penaltyId);

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'response' => 'nullable|string',
        ]);

        $penalty->reviewAppeal($validated['decision'], auth()->id(), $validated['response']);

        return back()->with('success', 'Shikoyat ko\'rib chiqildi');
    }

    /**
     * Leaderboard sahifasi
     */
    public function leaderboard(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $periodType = $request->get('period', 'monthly');

        $leaderboard = $this->leaderboardService->getLeaderboard($businessId, $periodType, now(), 20);
        $records = $this->leaderboardService->getBusinessRecords($businessId);

        // Oylik tarix
        $history = SalesLeaderboardEntry::forBusiness($businessId)
            ->where('period_type', 'monthly')
            ->where('rank', '<=', 3)
            ->with('user:id,name')
            ->orderByDesc('period_start')
            ->limit(12)
            ->get()
            ->groupBy(fn ($item) => $item->period_start->format('Y-m'));

        return Inertia::render('SalesHead/SalesKPI/Leaderboard', [
            'leaderboard' => $leaderboard,
            'records' => $records,
            'history' => $history,
            'periodType' => $periodType,
            'medals' => SalesLeaderboardEntry::MEDALS,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Yutuqlar sahifasi
     */
    public function achievements(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;

        // Barcha yutuq ta'riflari - awarded_count bilan
        $achievements = SalesAchievementDefinition::forBusiness($businessId)
            ->active()
            ->public()
            ->ordered()
            ->withCount(['userAchievements as awarded_count'])
            ->get();

        // Top achievers (jamoa statistikasi)
        $topAchievers = SalesUserAchievement::forBusiness($businessId)
            ->with(['user:id,name'])
            ->selectRaw('user_id, COUNT(*) as achievements_count, SUM(points_awarded) as total_points')
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit(10)
            ->get();

        // So'nggi olingan yutuqlar
        $recentAchievements = SalesUserAchievement::forBusiness($businessId)
            ->with(['user:id,name', 'achievement:id,name,icon,tier,points,description'])
            ->recent(20)
            ->get();

        // Umumiy statistika
        $stats = [
            'total_definitions' => $achievements->count(),
            'total_awarded' => SalesUserAchievement::forBusiness($businessId)->count(),
            'awarded_this_month' => SalesUserAchievement::forBusiness($businessId)
                ->whereMonth('earned_at', now()->month)
                ->whereYear('earned_at', now()->year)
                ->count(),
            'total_points' => SalesUserAchievement::forBusiness($businessId)->sum('points_awarded'),
        ];

        return Inertia::render('SalesHead/SalesKPI/Achievements', [
            'achievements' => $achievements,
            'topAchievers' => $topAchievers,
            'recentAchievements' => $recentAchievements,
            'stats' => $stats,
            'categories' => SalesAchievementDefinition::CATEGORIES,
            'tiers' => SalesAchievementDefinition::TIERS,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Setup Wizard
     */
    public function setup(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;

        // Mavjud progress
        $progress = $this->setupWizardService->getProgress($businessId);

        // Agar tugallangan bo'lsa, dashboardga yo'naltirish
        if ($progress && $progress->status === 'completed') {
            return redirect()->route('sales-head.sales-kpi.dashboard');
        }

        // Mavjud shablonlar
        $templates = $this->setupWizardService->getAvailableTemplates();

        return Inertia::render('SalesHead/SalesKPI/Setup', [
            'progress' => $progress,
            'templates' => $templates,
            'steps' => SalesSetupProgress::STEPS,
            'industries' => SalesKpiTemplateSet::INDUSTRIES,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Shablonni qo'llash
     */
    public function applyTemplate(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'template_id' => 'required|uuid|exists:sales_kpi_template_sets,id',
        ]);

        $this->setupWizardService->applyTemplate(
            $business->id,
            $validated['template_id'],
            auth()->id()
        );

        return redirect()->route('sales-head.sales-kpi.dashboard')
            ->with('success', 'Shablon muvaffaqiyatli qo\'llandi!');
    }

    /**
     * Operator KPI ko'rish (individual)
     */
    public function operatorKpi(Request $request, string $userId)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $periodType = $request->get('period', 'monthly');
        $date = $request->get('date', now()->format('Y-m-d'));

        // Foydalanuvchi ma'lumotlari
        $user = \App\Models\User::find($userId);

        // KPI hisoblash
        $kpiData = $this->kpiCalculationService->calculateForUser(
            $businessId,
            $userId,
            $periodType
        );

        // Period summary
        $periodStart = match ($periodType) {
            'daily' => Carbon::parse($date)->startOfDay(),
            'weekly' => Carbon::parse($date)->startOfWeek(),
            'monthly' => Carbon::parse($date)->startOfMonth(),
            default => Carbon::parse($date)->startOfMonth(),
        };

        $summary = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriodType($periodType)
            ->forPeriodStart($periodStart)
            ->first();

        // KPI details - har bir KPI uchun
        $kpiSettings = SalesKpiSetting::forBusiness($businessId)->active()->ordered()->get();
        $kpiDetails = [];
        foreach ($kpiSettings as $kpi) {
            $kpiScore = $summary ? $summary->getKpiScore($kpi->id) : null;
            $kpiDetails[] = [
                'kpi_setting_id' => $kpi->id,
                'name' => $kpi->name,
                'description' => $kpi->description,
                'score' => $kpiScore['score'] ?? 0,
                'actual' => $kpiScore['actual_value'] ?? 0,
                'target' => $kpiScore['target_value'] ?? $kpi->target_min,
                'unit' => $kpi->measurement_unit,
                'weight' => $kpi->weight,
            ];
        }

        // User stats (points, streaks, achievements count)
        $points = SalesUserPoints::getOrCreate($businessId, $userId);
        $streaks = SalesUserStreak::forBusiness($businessId)->forUser($userId)->get();
        $userStats = [
            'total_points' => $points->total_points,
            'current_level' => $points->current_level,
            'achievements_count' => $points->achievements_count,
            'current_streak' => $streaks->where('streak_type', 'daily_target')->first()?->current_streak ?? 0,
        ];

        // So'nggi yutuqlar
        $recentAchievements = SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->with('achievement:id,name,icon,tier,points')
            ->recent(5)
            ->get();

        // Bu oylik jarimalar
        $penalties = SalesPenalty::forBusiness($businessId)
            ->where('user_id', $userId)
            ->whereMonth('triggered_at', now()->month)
            ->whereYear('triggered_at', now()->year)
            ->with('penaltyRule:id,name')
            ->orderByDesc('triggered_at')
            ->get();

        // Joriy bonus
        $currentBonus = SalesBonusCalculation::forBusiness($businessId)
            ->where('user_id', $userId)
            ->whereMonth('period_start', now()->month)
            ->whereYear('period_start', now()->year)
            ->first();

        // Tarixiy ma'lumotlar
        $historicalData = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriodType('monthly')
            ->orderByDesc('period_start')
            ->limit(6)
            ->get();

        // Score o'zgarishi
        $previousSummary = $summary?->getPreviousPeriodSummary();
        $scoreChange = $previousSummary ? ($summary->overall_score - $previousSummary->overall_score) : null;

        return Inertia::render('SalesHead/SalesKPI/OperatorKpi', [
            'user' => $user,
            'summary' => $summary,
            'kpiDetails' => $kpiDetails,
            'userStats' => $userStats,
            'recentAchievements' => $recentAchievements,
            'penalties' => $penalties,
            'currentBonus' => $currentBonus,
            'historicalData' => $historicalData,
            'scoreChange' => $scoreChange,
            'period' => $periodType,
            'date' => $date,
            'panelType' => 'saleshead',
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Jamoa a'zolari KPI
     */
    protected function getTeamMembersKpi(string $businessId, string $periodType): array
    {
        $members = $this->getTeamMembers($businessId);
        $result = [];

        foreach ($members as $member) {
            $kpiData = $this->kpiCalculationService->calculateForUser(
                $businessId,
                $member['id'],
                $periodType
            );

            $result[] = [
                'id' => $member['id'],
                'name' => $member['name'],
                'avatar' => strtoupper(substr($member['name'], 0, 1)),
                'overall_score' => $kpiData['overall_score'],
                'performance_tier' => $kpiData['performance_tier'],
                'kpis_count' => $kpiData['kpis_count'],
            ];
        }

        // Ball bo'yicha tartiblash
        usort($result, fn ($a, $b) => $b['overall_score'] <=> $a['overall_score']);

        return $result;
    }

    /**
     * Jamoa a'zolari
     */
    protected function getTeamMembers(string $businessId): array
    {
        return DB::table('business_user')
            ->join('users', 'business_user.user_id', '=', 'users.id')
            ->where('business_user.business_id', $businessId)
            ->where('business_user.department', 'sales_operator')
            ->whereNotNull('business_user.accepted_at')
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->toArray();
    }

    /**
     * Umumiy statistika
     */
    protected function getOverallStats(string $businessId, string $periodType): array
    {
        $now = now();
        $periodStart = match ($periodType) {
            'daily' => $now->copy()->startOfDay(),
            'weekly' => $now->copy()->startOfWeek(),
            'monthly' => $now->copy()->startOfMonth(),
            default => $now->copy()->startOfMonth(),
        };

        $summaries = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forPeriodType($periodType)
            ->forPeriodStart($periodStart)
            ->get();

        return [
            'avg_score' => (int) round($summaries->avg('overall_score') ?? 0),
            'top_performer_score' => (int) ($summaries->max('overall_score') ?? 0),
            'team_size' => $summaries->count(),
            'above_target' => $summaries->where('overall_score', '>=', 80)->count(),
            'below_target' => $summaries->where('overall_score', '<', 50)->count(),
        ];
    }

    /**
     * Operator uchun shaxsiy KPI - o'z hisobini ko'rish
     */
    public function myKpi(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $userId = auth()->id();
        $periodType = $request->get('period', 'monthly');
        $date = $request->get('date', now()->format('Y-m-d'));

        // Foydalanuvchi ma'lumotlari
        $user = auth()->user();

        // KPI hisoblash
        $kpiData = $this->kpiCalculationService->calculateForUser(
            $businessId,
            $userId,
            $periodType
        );

        // Period summary
        $periodStart = match ($periodType) {
            'daily' => Carbon::parse($date)->startOfDay(),
            'weekly' => Carbon::parse($date)->startOfWeek(),
            'monthly' => Carbon::parse($date)->startOfMonth(),
            default => Carbon::parse($date)->startOfMonth(),
        };

        $summary = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriodType($periodType)
            ->forPeriodStart($periodStart)
            ->first();

        // KPI details - har bir KPI uchun
        $kpiSettings = SalesKpiSetting::forBusiness($businessId)->active()->ordered()->get();
        $kpiDetails = [];
        foreach ($kpiSettings as $kpi) {
            $kpiScore = $summary ? $summary->getKpiScore($kpi->id) : null;
            $kpiDetails[] = [
                'kpi_setting_id' => $kpi->id,
                'name' => $kpi->name,
                'description' => $kpi->description,
                'score' => $kpiScore['score'] ?? 0,
                'actual' => $kpiScore['actual_value'] ?? 0,
                'target' => $kpiScore['target_value'] ?? $kpi->target_min,
                'unit' => $kpi->measurement_unit,
                'weight' => $kpi->weight,
            ];
        }

        // User statistikasi
        $userStats = SalesUserPoints::forBusiness($businessId)
            ->forUser($userId)
            ->first();

        // So'nggi yutuqlar
        $recentAchievements = SalesUserAchievement::forBusiness($businessId)
            ->forUser($userId)
            ->with('achievement:id,name,icon,tier,points')
            ->recent(5)
            ->get();

        // Aktiv jarimalar
        $penalties = SalesPenalty::forBusiness($businessId)
            ->forUser($userId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('rule:id,name,penalty_type,penalty_amount')
            ->latest()
            ->limit(5)
            ->get();

        // Joriy davr bonusi
        $currentBonus = SalesBonusCalculation::forBusiness($businessId)
            ->forUser($userId)
            ->whereMonth('period_start', now()->month)
            ->whereYear('period_start', now()->year)
            ->first();

        // Tarixiy ma'lumotlar
        $historicalData = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriodType('monthly')
            ->orderByDesc('period_start')
            ->limit(6)
            ->get();

        // Score o'zgarishi
        $previousSummary = $summary?->getPreviousPeriodSummary();
        $scoreChange = $previousSummary ? ($summary->overall_score - $previousSummary->overall_score) : null;

        // Leaderboard pozitsiyasi
        $leaderboardPosition = SalesLeaderboardEntry::forBusiness($businessId)
            ->forUser($userId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->first();

        return Inertia::render('Operator/KPI/Index', [
            'user' => $user,
            'summary' => $summary,
            'kpiData' => $kpiData,
            'kpiDetails' => $kpiDetails,
            'userStats' => $userStats,
            'recentAchievements' => $recentAchievements,
            'penalties' => $penalties,
            'currentBonus' => $currentBonus,
            'historicalData' => $historicalData,
            'scoreChange' => $scoreChange,
            'leaderboardPosition' => $leaderboardPosition,
            'period' => $periodType,
            'date' => $date,
            'panelType' => 'operator',
        ]);
    }
}
