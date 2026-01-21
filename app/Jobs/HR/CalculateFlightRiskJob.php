<?php

namespace App\Jobs\HR;

use App\Models\Business;
use App\Models\User;
use App\Models\EmployeeEngagement;
use App\Models\FlightRisk;
use App\Services\HR\RetentionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CalculateFlightRiskJob - Haftalik ketish xavfini hisoblash
 *
 * Bu job har hafta ishga tushadi va barcha hodimlar uchun
 * flight risk ballini avtomatik hisoblaydi.
 *
 * Risk faktorlari:
 * - Engagement score (30%)
 * - Tenure (ish staji) (15%)
 * - Compensation (maosh) (15%)
 * - Growth opportunities (20%)
 * - Workload (ish yuki) (10%)
 * - Recognition frequency (10%)
 */
class CalculateFlightRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 900; // 15 daqiqa

    public function __construct(
        public ?string $businessId = null,
        public ?string $userId = null
    ) {}

    public function handle(RetentionService $retentionService): void
    {
        Log::info('CalculateFlightRiskJob boshlandi', [
            'business_id' => $this->businessId,
            'user_id' => $this->userId,
        ]);

        if ($this->userId) {
            $this->calculateForUser($retentionService, $this->userId);
        } elseif ($this->businessId) {
            $this->calculateForBusiness($retentionService, $this->businessId);
        } else {
            $this->calculateForAllBusinesses($retentionService);
        }

        Log::info('CalculateFlightRiskJob yakunlandi');
    }

    protected function calculateForUser(RetentionService $retentionService, string $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        foreach ($user->businesses as $business) {
            try {
                $this->calculateUserFlightRisk($retentionService, $user, $business);
            } catch (\Exception $e) {
                Log::error('Hodim flight risk hisoblashda xato', [
                    'user_id' => $userId,
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function calculateForBusiness(RetentionService $retentionService, string $businessId): void
    {
        $business = Business::find($businessId);
        if (!$business) {
            return;
        }

        $employees = $business->users()
            ->whereNotNull('business_user.accepted_at')
            ->get();

        $successCount = 0;
        $errorCount = 0;
        $highRiskCount = 0;

        foreach ($employees as $employee) {
            try {
                $flightRisk = $this->calculateUserFlightRisk($retentionService, $employee, $business);
                $successCount++;

                if ($flightRisk && in_array($flightRisk->risk_level, [FlightRisk::LEVEL_HIGH, FlightRisk::LEVEL_CRITICAL])) {
                    $highRiskCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::warning('Hodim flight risk hisoblashda xato', [
                    'user_id' => $employee->id,
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Biznes flight risk hisoblash yakunlandi', [
            'business_id' => $businessId,
            'employees_count' => $employees->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'high_risk_count' => $highRiskCount,
        ]);
    }

    protected function calculateForAllBusinesses(RetentionService $retentionService): void
    {
        $businesses = Business::where('status', 'active')->pluck('id');

        $processedCount = 0;
        $errorCount = 0;

        foreach ($businesses as $businessId) {
            try {
                $this->calculateForBusiness($retentionService, $businessId);
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Biznes flight risk hisoblashda xato', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Barcha bizneslar flight risk hisoblash yakunlandi', [
            'businesses_processed' => $processedCount,
            'businesses_failed' => $errorCount,
        ]);
    }

    protected function calculateUserFlightRisk(
        RetentionService $retentionService,
        User $user,
        Business $business
    ): ?FlightRisk {
        // Mavjud yoki yangi flight risk record
        $flightRisk = FlightRisk::firstOrCreate([
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);

        $oldLevel = $flightRisk->risk_level;

        // Risk faktorlarini hisoblash
        $factors = $this->calculateRiskFactors($user, $business);

        // Umumiy risk ballni hisoblash
        $riskScore = $this->calculateOverallRiskScore($factors);

        // Risk levelni aniqlash
        $riskLevel = match(true) {
            $riskScore >= 76 => FlightRisk::LEVEL_CRITICAL,
            $riskScore >= 51 => FlightRisk::LEVEL_HIGH,
            $riskScore >= 26 => FlightRisk::LEVEL_MODERATE,
            default => FlightRisk::LEVEL_LOW,
        };

        // Tavsiya etiladigan harakatlar
        $recommendedActions = $this->getRecommendedActions($riskLevel, $factors);

        // Yangilash
        $updateData = [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_factors' => $factors,
            'engagement_factor' => $factors['engagement']['score'],
            'tenure_factor' => $factors['tenure']['score'],
            'compensation_factor' => $factors['compensation']['score'],
            'growth_factor' => $factors['growth']['score'],
            'workload_factor' => $factors['workload']['score'],
            'recognition_factor' => $factors['recognition']['score'],
            'recommended_actions' => $recommendedActions,
        ];

        // Level o'zgargan bo'lsa - tarixga qo'shish
        if ($oldLevel !== $riskLevel) {
            $updateData['previous_level'] = $oldLevel;
            $updateData['level_changed_at'] = now();

            $history = $flightRisk->level_history ?? [];
            $history[] = [
                'date' => now()->toISOString(),
                'old_level' => $oldLevel,
                'new_level' => $riskLevel,
                'score' => $riskScore,
            ];

            // Oxirgi 20 ta yozuvni saqlash
            if (count($history) > 20) {
                $history = array_slice($history, -20);
            }

            $updateData['level_history'] = $history;
        }

        $flightRisk->update($updateData);

        return $flightRisk;
    }

    protected function calculateRiskFactors(User $user, Business $business): array
    {
        $period = Carbon::now()->format('Y-m');

        return [
            'engagement' => $this->calculateEngagementFactor($user, $business, $period),
            'tenure' => $this->calculateTenureFactor($user, $business),
            'compensation' => $this->calculateCompensationFactor($user, $business),
            'growth' => $this->calculateGrowthFactor($user, $business),
            'workload' => $this->calculateWorkloadFactor($user, $business),
            'recognition' => $this->calculateRecognitionFactor($user, $business),
        ];
    }

    protected function calculateEngagementFactor(User $user, Business $business, string $period): array
    {
        $engagement = EmployeeEngagement::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('period', $period)
            ->first();

        $engagementScore = $engagement?->overall_score ?? 50;

        // Past engagement = yuqori risk
        // Engagement 100 bo'lsa risk 0, engagement 0 bo'lsa risk 100
        $riskScore = max(0, 100 - $engagementScore);

        return [
            'score' => $riskScore,
            'weight' => 0.30,
            'label' => 'Engagement darajasi',
            'details' => [
                'engagement_score' => $engagementScore,
                'trend' => $engagement?->trend ?? 'unknown',
            ],
        ];
    }

    protected function calculateTenureFactor(User $user, Business $business): array
    {
        $businessUser = DB::table('business_user')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->first();

        $startDate = $businessUser?->accepted_at ?? $businessUser?->created_at ?? now();
        $tenureMonths = Carbon::parse($startDate)->diffInMonths(now());

        // Tenure risk curve:
        // 0-3 oy: yuqori risk (80)
        // 3-6 oy: o'rtacha risk (60)
        // 6-12 oy: past risk (30)
        // 12-24 oy: eng past risk (10)
        // 24+ oy: o'rtacha risk (40) - "comfort zone" risk
        $riskScore = match(true) {
            $tenureMonths < 3 => 80,
            $tenureMonths < 6 => 60,
            $tenureMonths < 12 => 30,
            $tenureMonths < 24 => 10,
            $tenureMonths < 36 => 30,
            default => 40, // Uzoq muddatli hodimlar ham ketishi mumkin
        };

        return [
            'score' => $riskScore,
            'weight' => 0.15,
            'label' => 'Ish staji',
            'details' => [
                'tenure_months' => $tenureMonths,
                'tenure_label' => $this->getTenureLabel($tenureMonths),
            ],
        ];
    }

    protected function calculateCompensationFactor(User $user, Business $business): array
    {
        // Maosh ma'lumotlari payroll jadvalidan olinadi
        $salary = DB::table('payroll_records')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderBy('pay_period_end', 'desc')
            ->value('base_salary') ?? 0;

        // O'rtacha maoshni olish
        $avgSalary = DB::table('payroll_records')
            ->where('business_id', $business->id)
            ->avg('base_salary') ?? 1;

        // Maosh nisbati
        $salaryRatio = $avgSalary > 0 ? $salary / $avgSalary : 1;

        // Past maosh = yuqori risk
        $riskScore = match(true) {
            $salaryRatio < 0.7 => 80, // 30% dan kam = juda yuqori risk
            $salaryRatio < 0.85 => 60, // 15% dan kam
            $salaryRatio < 1.0 => 40, // O'rtachadan past
            $salaryRatio < 1.2 => 20, // O'rtachadan yuqori
            default => 10, // 20%+ yuqori = past risk
        };

        return [
            'score' => $riskScore,
            'weight' => 0.15,
            'label' => 'Maosh darajasi',
            'details' => [
                'salary_ratio' => round($salaryRatio, 2),
                'below_average' => $salaryRatio < 1,
            ],
        ];
    }

    protected function calculateGrowthFactor(User $user, Business $business): array
    {
        // Oxirgi 12 oyda lavozim o'zgarishi
        $promotions = DB::table('hr_employee_goals')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subYear())
            ->count();

        // Treninglar va rivojlanish
        $trainings = DB::table('employee_onboarding_tasks')
            ->join('employee_onboarding_plans', 'employee_onboarding_tasks.plan_id', '=', 'employee_onboarding_plans.id')
            ->where('employee_onboarding_plans.business_id', $business->id)
            ->where('employee_onboarding_plans.user_id', $user->id)
            ->where('employee_onboarding_tasks.category', 'training')
            ->where('employee_onboarding_tasks.status', 'completed')
            ->where('employee_onboarding_tasks.completed_at', '>=', now()->subYear())
            ->count();

        // O'sish yo'qligi = yuqori risk
        $growthScore = min(($promotions * 30) + ($trainings * 10), 100);
        $riskScore = max(0, 100 - $growthScore);

        return [
            'score' => $riskScore,
            'weight' => 0.20,
            'label' => "O'sish imkoniyatlari",
            'details' => [
                'promotions_count' => $promotions,
                'trainings_count' => $trainings,
                'growth_score' => $growthScore,
            ],
        ];
    }

    protected function calculateWorkloadFactor(User $user, Business $business): array
    {
        // Oxirgi 30 kundagi ish yuki
        $startDate = now()->subDays(30);

        // Overtime kunlar
        $overtimeDays = $user->attendanceRecords()
            ->where('business_id', $business->id)
            ->where('date', '>=', $startDate)
            ->where('work_hours', '>', 9)
            ->count();

        // Overdue tasklar
        $overdueTasks = $user->tasks()
            ->where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        // Yuqori workload = yuqori risk (burnout)
        $workloadRisk = min(($overtimeDays * 5) + ($overdueTasks * 10), 100);

        return [
            'score' => $workloadRisk,
            'weight' => 0.10,
            'label' => 'Ish yuki',
            'details' => [
                'overtime_days' => $overtimeDays,
                'overdue_tasks' => $overdueTasks,
            ],
        ];
    }

    protected function calculateRecognitionFactor(User $user, Business $business): array
    {
        // Oxirgi 90 kundagi tan olishlar
        $recognitions = DB::table('hr_recognitions')
            ->where('business_id', $business->id)
            ->where('to_user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        // Tan olish yo'qligi = yuqori risk
        $recognitionScore = min($recognitions * 25, 100);
        $riskScore = max(0, 100 - $recognitionScore);

        return [
            'score' => $riskScore,
            'weight' => 0.10,
            'label' => "Tan olish chastotasi",
            'details' => [
                'recognitions_count' => $recognitions,
                'recognition_score' => $recognitionScore,
            ],
        ];
    }

    protected function calculateOverallRiskScore(array $factors): float
    {
        $total = 0;

        foreach ($factors as $factor) {
            $total += $factor['score'] * $factor['weight'];
        }

        return round($total, 2);
    }

    protected function getRecommendedActions(string $riskLevel, array $factors): array
    {
        $actions = [];

        // Eng yuqori risk faktorlarini aniqlash
        $topFactors = collect($factors)
            ->sortByDesc('score')
            ->take(3)
            ->keys()
            ->toArray();

        foreach ($topFactors as $factor) {
            switch ($factor) {
                case 'engagement':
                    $actions[] = [
                        'action' => 'engagement_survey',
                        'label' => "Engagement so'rovnoma o'tkazish",
                        'priority' => $factors[$factor]['score'] > 70 ? 'high' : 'medium',
                    ];
                    break;
                case 'growth':
                    $actions[] = [
                        'action' => 'career_discussion',
                        'label' => 'Karyera rivojlanish suhbati',
                        'priority' => 'high',
                    ];
                    break;
                case 'compensation':
                    $actions[] = [
                        'action' => 'salary_review',
                        'label' => "Maoshni ko'rib chiqish",
                        'priority' => 'medium',
                    ];
                    break;
                case 'recognition':
                    $actions[] = [
                        'action' => 'recognition',
                        'label' => 'Minnatdorchilik bildirish',
                        'priority' => 'low',
                    ];
                    break;
                case 'workload':
                    $actions[] = [
                        'action' => 'workload_review',
                        'label' => "Ish yukini qayta ko'rib chiqish",
                        'priority' => 'high',
                    ];
                    break;
            }
        }

        // Risk levelga qarab qo'shimcha harakatlar
        if ($riskLevel === FlightRisk::LEVEL_CRITICAL) {
            array_unshift($actions, [
                'action' => 'stay_interview',
                'label' => "Zudlik bilan stay interview o'tkazish",
                'priority' => 'urgent',
            ]);
        } elseif ($riskLevel === FlightRisk::LEVEL_HIGH) {
            array_unshift($actions, [
                'action' => 'one_on_one',
                'label' => '1-on-1 suhbat tayinlash',
                'priority' => 'high',
            ]);
        }

        return $actions;
    }

    protected function getTenureLabel(int $months): string
    {
        return match(true) {
            $months < 3 => "3 oydan kam",
            $months < 6 => "3-6 oy",
            $months < 12 => "6-12 oy",
            $months < 24 => "1-2 yil",
            $months < 36 => "2-3 yil",
            $months < 60 => "3-5 yil",
            default => "5 yildan ko'p",
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateFlightRiskJob muvaffaqiyatsiz', [
            'business_id' => $this->businessId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
