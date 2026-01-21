<?php

namespace App\Jobs\HR;

use App\Models\Business;
use App\Models\TurnoverRecord;
use App\Services\HR\HRAlertService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GenerateTurnoverReportJob - Oylik turnover hisoboti
 *
 * Bu job har oyning boshida ishga tushadi va:
 * - Turnover rate hisoblaydi
 * - Ketish sabablarini tahlil qiladi
 * - Trend va alert yaratadi
 * - Regrettable turnover ni aniqlaydi
 */
class GenerateTurnoverReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null,
        public ?int $year = null,
        public ?int $month = null
    ) {
        $this->year = $year ?? Carbon::now()->year;
        $this->month = $month ?? Carbon::now()->subMonth()->month;
    }

    public function handle(HRAlertService $alertService): void
    {
        Log::info('GenerateTurnoverReportJob boshlandi', [
            'business_id' => $this->businessId,
            'period' => "{$this->year}-{$this->month}",
        ]);

        if ($this->businessId) {
            $this->generateForBusiness($alertService, $this->businessId);
        } else {
            $this->generateForAllBusinesses($alertService);
        }

        Log::info('GenerateTurnoverReportJob yakunlandi');
    }

    protected function generateForAllBusinesses(HRAlertService $alertService): void
    {
        $businesses = Business::where('status', 'active')->pluck('id');

        foreach ($businesses as $businessId) {
            try {
                $this->generateForBusiness($alertService, $businessId);
            } catch (\Exception $e) {
                Log::error('Biznes turnover report xatosi', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function generateForBusiness(HRAlertService $alertService, string $businessId): void
    {
        $business = Business::find($businessId);
        if (!$business) {
            return;
        }

        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 1. Asosiy ko'rsatkichlarni hisoblash
        $metrics = $this->calculateMetrics($business, $startDate, $endDate);

        // 2. Ketish sabablarini tahlil qilish
        $reasonAnalysis = $this->analyzeTerminationReasons($business, $startDate, $endDate);

        // 3. Trend tahlili (oldingi oy bilan solishtirish)
        $trend = $this->calculateTrend($business, $startDate);

        // 4. Alertlar yaratish
        $this->createAlerts($alertService, $business, $metrics, $trend, $reasonAnalysis);

        // 5. Hisobotni saqlash (hr_reports jadvaliga yoki cache)
        $this->saveReport($business, $metrics, $reasonAnalysis, $trend);

        Log::info('Biznes turnover report yakunlandi', [
            'business_id' => $businessId,
            'period' => "{$this->year}-{$this->month}",
            'turnover_rate' => $metrics['turnover_rate'],
            'total_terminations' => $metrics['total_terminations'],
        ]);
    }

    protected function calculateMetrics(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        // Oyning boshidagi hodimlar soni
        $startingHeadcount = DB::table('business_user')
            ->where('business_id', $business->id)
            ->whereNotNull('accepted_at')
            ->where('accepted_at', '<', $startDate)
            ->whereNull('left_at')
            ->orWhere('left_at', '>=', $startDate)
            ->count();

        // Yangi qo'shilganlar
        $newHires = DB::table('business_user')
            ->where('business_id', $business->id)
            ->whereBetween('accepted_at', [$startDate, $endDate])
            ->count();

        // Ketganlar
        $terminations = TurnoverRecord::where('business_id', $business->id)
            ->whereBetween('termination_date', [$startDate, $endDate])
            ->get();

        $totalTerminations = $terminations->count();
        $voluntaryTerminations = $terminations->where('termination_type', TurnoverRecord::TYPE_VOLUNTARY)->count();
        $involuntaryTerminations = $terminations->where('termination_type', TurnoverRecord::TYPE_INVOLUNTARY)->count();
        $regrettableTerminations = $terminations->where('is_regrettable', true)->count();

        // Oyning oxiridagi hodimlar soni
        $endingHeadcount = $startingHeadcount + $newHires - $totalTerminations;

        // O'rtacha hodimlar soni
        $avgHeadcount = ($startingHeadcount + $endingHeadcount) / 2;

        // Turnover rate
        $turnoverRate = $avgHeadcount > 0
            ? round(($totalTerminations / $avgHeadcount) * 100, 2)
            : 0;

        // Voluntary turnover rate
        $voluntaryRate = $avgHeadcount > 0
            ? round(($voluntaryTerminations / $avgHeadcount) * 100, 2)
            : 0;

        // Regrettable turnover rate
        $regrettableRate = $totalTerminations > 0
            ? round(($regrettableTerminations / $totalTerminations) * 100, 2)
            : 0;

        // Tenure distribution
        $tenureDistribution = $this->calculateTenureDistribution($terminations);

        return [
            'period' => "{$this->year}-{$this->month}",
            'starting_headcount' => $startingHeadcount,
            'new_hires' => $newHires,
            'total_terminations' => $totalTerminations,
            'voluntary_terminations' => $voluntaryTerminations,
            'involuntary_terminations' => $involuntaryTerminations,
            'regrettable_terminations' => $regrettableTerminations,
            'ending_headcount' => $endingHeadcount,
            'avg_headcount' => round($avgHeadcount),
            'turnover_rate' => $turnoverRate,
            'voluntary_rate' => $voluntaryRate,
            'regrettable_rate' => $regrettableRate,
            'tenure_distribution' => $tenureDistribution,
        ];
    }

    protected function calculateTenureDistribution($terminations): array
    {
        return [
            'less_than_3_months' => $terminations->where('tenure_months', '<', 3)->count(),
            '3_to_6_months' => $terminations->whereBetween('tenure_months', [3, 6])->count(),
            '6_to_12_months' => $terminations->whereBetween('tenure_months', [6, 12])->count(),
            '1_to_2_years' => $terminations->whereBetween('tenure_months', [12, 24])->count(),
            '2_to_5_years' => $terminations->whereBetween('tenure_months', [24, 60])->count(),
            'over_5_years' => $terminations->where('tenure_months', '>=', 60)->count(),
        ];
    }

    protected function analyzeTerminationReasons(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $reasons = TurnoverRecord::where('business_id', $business->id)
            ->whereBetween('termination_date', [$startDate, $endDate])
            ->whereNotNull('termination_reason')
            ->selectRaw('termination_reason, COUNT(*) as count')
            ->groupBy('termination_reason')
            ->orderByDesc('count')
            ->get();

        $total = $reasons->sum('count');

        return $reasons->map(function ($reason) use ($total) {
            return [
                'reason' => $reason->termination_reason,
                'count' => $reason->count,
                'percentage' => $total > 0 ? round(($reason->count / $total) * 100, 1) : 0,
                'label' => $this->getReasonLabel($reason->termination_reason),
            ];
        })->toArray();
    }

    protected function calculateTrend(Business $business, Carbon $currentMonth): array
    {
        $prevMonth = $currentMonth->copy()->subMonth();

        $currentRate = $this->getTurnoverRate($business, $currentMonth);
        $prevRate = $this->getTurnoverRate($business, $prevMonth);

        $change = $currentRate - $prevRate;

        return [
            'current_rate' => $currentRate,
            'previous_rate' => $prevRate,
            'change' => round($change, 2),
            'trend' => match(true) {
                $change > 2 => 'increasing',
                $change < -2 => 'decreasing',
                default => 'stable',
            },
            'is_concerning' => $change > 5 || $currentRate > 10,
        ];
    }

    protected function getTurnoverRate(Business $business, Carbon $month): float
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $terminations = TurnoverRecord::where('business_id', $business->id)
            ->whereBetween('termination_date', [$startDate, $endDate])
            ->count();

        $avgHeadcount = DB::table('business_user')
            ->where('business_id', $business->id)
            ->whereNotNull('accepted_at')
            ->count();

        return $avgHeadcount > 0 ? round(($terminations / $avgHeadcount) * 100, 2) : 0;
    }

    protected function createAlerts(
        HRAlertService $alertService,
        Business $business,
        array $metrics,
        array $trend,
        array $reasonAnalysis
    ): void {
        $period = Carbon::create($this->year, $this->month, 1)->format('F Y');

        // Yuqori turnover rate ogohlantirishi
        if ($metrics['turnover_rate'] > 10) {
            $alertService->createAlert(
                $business,
                'turnover_high',
                "Yuqori turnover - {$period}",
                "Turnover rate {$metrics['turnover_rate']}% - bu o'rtachadan yuqori",
                [
                    'priority' => 'high',
                    'user_id' => null,
                    'data' => [
                        'period' => $metrics['period'],
                        'turnover_rate' => $metrics['turnover_rate'],
                        'total_terminations' => $metrics['total_terminations'],
                        'top_reason' => $reasonAnalysis[0] ?? null,
                    ],
                ]
            );
        }

        // Regrettable turnover yuqori
        if ($metrics['regrettable_rate'] > 50 && $metrics['total_terminations'] >= 2) {
            $alertService->createAlert(
                $business,
                'turnover_regrettable',
                "Ko'p afsuslanarli yo'qotishlar",
                "Ketganlarning {$metrics['regrettable_rate']}% si yaxshi ishlagan hodimlar edi",
                [
                    'priority' => 'high',
                    'user_id' => null,
                    'data' => [
                        'regrettable_count' => $metrics['regrettable_terminations'],
                        'regrettable_rate' => $metrics['regrettable_rate'],
                    ],
                ]
            );
        }

        // Trend ogohlantirishi
        if ($trend['is_concerning']) {
            $alertService->createAlert(
                $business,
                'turnover_trend_negative',
                'Turnover trendi salbiy',
                "Turnover oldingi oyga nisbatan {$trend['change']}% ga oshdi",
                [
                    'priority' => 'medium',
                    'user_id' => null,
                    'data' => $trend,
                ]
            );
        }

        // Yangi hodimlar turnover (3 oydan kam ishlagan)
        $earlyTurnover = $metrics['tenure_distribution']['less_than_3_months'] ?? 0;
        if ($earlyTurnover >= 2) {
            $alertService->createAlert(
                $business,
                'turnover_early',
                "Erta ketishlar ko'p",
                "{$earlyTurnover} ta hodim 3 oydan kam ishlagan - onboarding jarayonini tekshiring",
                [
                    'priority' => 'medium',
                    'user_id' => null,
                    'data' => [
                        'early_turnover_count' => $earlyTurnover,
                        'tenure_distribution' => $metrics['tenure_distribution'],
                    ],
                ]
            );
        }

        // Oylik hisobot tayyor
        $alertService->createAlert(
            $business,
            'turnover_report_ready',
            "{$period} turnover hisoboti tayyor",
            "Turnover rate: {$metrics['turnover_rate']}%, Ketganlar: {$metrics['total_terminations']}, Yangi qo'shilganlar: {$metrics['new_hires']}",
            [
                'priority' => 'low',
                'user_id' => null,
                'data' => $metrics,
            ]
        );
    }

    protected function saveReport(Business $business, array $metrics, array $reasonAnalysis, array $trend): void
    {
        // Cache yoki DB ga saqlash
        $key = "hr_turnover_report:{$business->id}:{$this->year}-{$this->month}";

        cache()->put($key, [
            'metrics' => $metrics,
            'reason_analysis' => $reasonAnalysis,
            'trend' => $trend,
            'generated_at' => now()->toISOString(),
        ], now()->addMonths(12));
    }

    protected function getReasonLabel(string $reason): string
    {
        return match($reason) {
            'better_opportunity' => 'Yaxshiroq ish imkoniyati',
            'compensation' => 'Maosh qoniqarsiz',
            'management' => 'Boshqaruv muammolari',
            'career_growth' => "O'sish imkoniyati yo'q",
            'work_life_balance' => 'Ish-hayot balansi',
            'relocation' => "Ko'chib ketish",
            'personal' => 'Shaxsiy sabablar',
            'retirement' => 'Pensiya',
            'performance' => "Ish samaradorligi bo'yicha",
            'restructuring' => "Tashkiliy o'zgarishlar",
            default => $reason,
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateTurnoverReportJob muvaffaqiyatsiz', [
            'business_id' => $this->businessId,
            'period' => "{$this->year}-{$this->month}",
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
