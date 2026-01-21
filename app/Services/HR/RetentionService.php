<?php

namespace App\Services\HR;

use App\Models\Business;
use App\Models\FlightRisk;
use App\Models\TurnoverRecord;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * RetentionService - Hodimlarni saqlab qolish va ketish xavfini boshqarish
 *
 * Vazifalar:
 * 1. Flight Risk (ketish xavfi) hisoblash
 * 2. Turnover statistikasi
 * 3. Stay interview boshqarish
 * 4. Exit tahlili
 */
class RetentionService
{
    // Flight risk omillari va og'irliklari
    protected array $riskFactors = [
        'engagement_score' => 0.25,      // Engagement balli
        'tenure' => 0.15,                // Ish staji
        'salary_competitiveness' => 0.15, // Maosh raqobatbardoshligi
        'promotion_history' => 0.10,     // Lavozim o'sishi
        'manager_relationship' => 0.10,  // Rahbar bilan munosabat
        'attendance_pattern' => 0.10,    // Davomat naqshi
        'training_investment' => 0.05,   // O'qitish investitsiyasi
        'workload' => 0.10,              // Ish yuki
    ];

    /**
     * Hodim flight risk ballini olish
     */
    public function getFlightRisk(User $employee, Business $business): ?FlightRisk
    {
        return FlightRisk::where('business_id', $business->id)
            ->where('user_id', $employee->id)
            ->first();
    }

    /**
     * Flight risk ni yangilash
     */
    public function calculateFlightRisk(User $employee, Business $business): FlightRisk
    {
        $flightRisk = FlightRisk::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'risk_score' => 25.0,
                'risk_level' => 'low',
            ]
        );

        // Har bir omilni hisoblash
        $factors = $this->calculateRiskFactors($employee, $business);

        // Umumiy ballni hisoblash
        $riskScore = 0;
        foreach ($factors as $factor => $data) {
            $weight = $this->riskFactors[$factor] ?? 0;
            $riskScore += $data['score'] * $weight;
        }

        $riskScore = min(100, max(0, $riskScore));
        $riskLevel = $this->getRiskLevel($riskScore);

        $flightRisk->update([
            'risk_score' => round($riskScore, 2),
            'risk_level' => $riskLevel,
            'risk_factors' => $factors,
            'last_calculated_at' => now(),
        ]);

        Log::info('RetentionService: Flight risk hisoblandi', [
            'employee_id' => $employee->id,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
        ]);

        return $flightRisk;
    }

    /**
     * Engagement asosida flight risk yangilash
     */
    public function updateFlightRiskFromEngagement(User $employee, Business $business, float $engagementScore): void
    {
        $flightRisk = FlightRisk::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'risk_score' => 25.0,
                'risk_level' => 'low',
                'engagement_factor' => 50.0,
                'tenure_factor' => 30.0,
                'compensation_factor' => 30.0,
                'growth_factor' => 40.0,
                'workload_factor' => 30.0,
                'recognition_factor' => 30.0,
            ]
        );

        // Engagement past bo'lsa - risk yuqori
        // Engagement 100 = Risk 0, Engagement 0 = Risk 100
        $engagementRisk = 100 - $engagementScore;

        // Engagement factor ustunini ham yangilash
        $flightRisk->engagement_factor = round($engagementRisk, 2);

        $factors = $flightRisk->risk_factors ?? [];
        $factors['engagement_score'] = [
            'score' => $engagementRisk,
            'impact' => $this->riskFactors['engagement_score'],
            'label' => 'Engagement darajasi',
        ];

        // Qayta hisoblash
        $this->recalculateRisk($flightRisk, $factors);
    }

    /**
     * Flight risk ni oshirish
     */
    public function increaseFlightRisk(User $employee, Business $business, string $reason, float $amount): void
    {
        $flightRisk = FlightRisk::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'risk_score' => 25.0,
                'risk_level' => 'low',
            ]
        );

        $oldScore = $flightRisk->risk_score;
        $newScore = min(100, $oldScore + $amount);
        $newLevel = $this->getRiskLevel($newScore);

        $flightRisk->update([
            'risk_score' => $newScore,
            'risk_level' => $newLevel,
            'last_change_reason' => $reason,
            'last_change_at' => now(),
        ]);

        Log::info('RetentionService: Flight risk oshirildi', [
            'employee_id' => $employee->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'reason' => $reason,
        ]);
    }

    /**
     * Flight risk ni kamaytirish
     */
    public function decreaseFlightRisk(User $employee, Business $business, string $reason, float $amount): void
    {
        $flightRisk = FlightRisk::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'risk_score' => 25.0,
                'risk_level' => 'low',
            ]
        );

        $oldScore = $flightRisk->risk_score;
        $newScore = max(0, $oldScore - $amount);
        $newLevel = $this->getRiskLevel($newScore);

        $flightRisk->update([
            'risk_score' => $newScore,
            'risk_level' => $newLevel,
            'last_change_reason' => $reason,
            'last_change_at' => now(),
        ]);

        Log::info('RetentionService: Flight risk kamaytirildi', [
            'employee_id' => $employee->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'reason' => $reason,
        ]);
    }

    /**
     * Ishdan ketishni qayd qilish
     */
    public function recordTermination(User $employee, Business $business, string $reason, array $data = []): TurnoverRecord
    {
        return TurnoverRecord::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'termination_type' => $reason,
            'termination_date' => $data['last_working_day'] ?? now()->toDateString(),
            'tenure_months' => $this->calculateTenure($employee),
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'exit_interview_completed' => false,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Exit survey tahlili
     */
    public function analyzeExitSurvey(User $employee, Business $business, array $responses): void
    {
        $turnover = TurnoverRecord::where('business_id', $business->id)
            ->where('user_id', $employee->id)
            ->latest()
            ->first();

        if ($turnover) {
            $turnover->update([
                'exit_interview_completed' => true,
                'exit_survey_responses' => $responses,
                'exit_survey_date' => now(),
            ]);
        }

        // Turnover tahlilini yangilash
        $this->updateTurnoverAnalytics($business);

        Log::info('RetentionService: Exit survey tahlil qilindi', [
            'employee_id' => $employee->id,
        ]);
    }

    /**
     * Risk darajasini aniqlash
     */
    protected function getRiskLevel(float $score): string
    {
        return match(true) {
            $score >= 76 => 'critical',
            $score >= 51 => 'high',
            $score >= 26 => 'moderate',
            default => 'low',
        };
    }

    /**
     * Risk omillarini hisoblash
     */
    protected function calculateRiskFactors(User $employee, Business $business): array
    {
        $factors = [];

        // Tenure risk (kam tajriba = yuqori risk)
        $tenureMonths = $this->calculateTenure($employee);
        $tenureRisk = match(true) {
            $tenureMonths < 6 => 60,   // 6 oydan kam - yuqori
            $tenureMonths < 12 => 45,  // 1 yildan kam
            $tenureMonths < 24 => 30,  // 2 yildan kam
            $tenureMonths < 36 => 20,  // 3 yildan kam
            default => 10,              // 3 yildan ko'p - past risk
        };

        $factors['tenure'] = [
            'score' => $tenureRisk,
            'impact' => $this->riskFactors['tenure'],
            'label' => 'Ish staji',
            'tenure_months' => $tenureMonths,
        ];

        // Boshqa omillar default qiymatlar bilan
        $factors['salary_competitiveness'] = [
            'score' => 30,
            'impact' => $this->riskFactors['salary_competitiveness'],
            'label' => 'Maosh raqobatbardoshligi',
        ];

        $factors['manager_relationship'] = [
            'score' => 25,
            'impact' => $this->riskFactors['manager_relationship'],
            'label' => 'Rahbar bilan munosabat',
        ];

        return $factors;
    }

    /**
     * Risk ni qayta hisoblash
     */
    protected function recalculateRisk(FlightRisk $flightRisk, array $factors): void
    {
        $riskScore = 0;
        foreach ($factors as $factor => $data) {
            $weight = $this->riskFactors[$factor] ?? 0;
            $riskScore += ($data['score'] ?? 0) * $weight;
        }

        $riskScore = min(100, max(0, $riskScore));
        $riskLevel = $this->getRiskLevel($riskScore);

        $flightRisk->update([
            'risk_score' => round($riskScore, 2),
            'risk_level' => $riskLevel,
            'risk_factors' => $factors,
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Ish stajini hisoblash
     */
    protected function calculateTenure(User $employee): int
    {
        $hireDate = $employee->created_at; // Yoki hire_date maydonidan
        return (int)$hireDate->diffInMonths(now());
    }

    /**
     * Turnover analytics yangilash
     */
    protected function updateTurnoverAnalytics(Business $business): void
    {
        // Bu yerda turnover statistikasi hisoblanadi
        Log::debug('RetentionService: Turnover analytics yangilanishi kerak', [
            'business_id' => $business->id,
        ]);
    }

    /**
     * Yuqori riskli hodimlar ro'yxati
     */
    public function getHighRiskEmployees(Business $business, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return FlightRisk::where('business_id', $business->id)
            ->whereIn('risk_level', ['high', 'critical'])
            ->orderBy('risk_score', 'desc')
            ->with('user:id,name,email')
            ->limit($limit)
            ->get();
    }

    /**
     * Retention statistikasi
     */
    public function getRetentionStats(Business $business, int $months = 12): array
    {
        $endDate = now();
        $startDate = now()->subMonths($months);

        $terminations = TurnoverRecord::where('business_id', $business->id)
            ->whereBetween('termination_date', [$startDate, $endDate])
            ->get();

        $totalEmployees = $business->users()->count();
        $voluntaryTerminations = $terminations->where('termination_type', 'voluntary')->count();
        $involuntaryTerminations = $terminations->where('termination_type', 'involuntary')->count();

        $turnoverRate = $totalEmployees > 0
            ? round(($terminations->count() / $totalEmployees) * 100, 2)
            : 0;

        return [
            'total_terminations' => $terminations->count(),
            'voluntary' => $voluntaryTerminations,
            'involuntary' => $involuntaryTerminations,
            'turnover_rate' => $turnoverRate,
            'average_tenure_months' => round($terminations->avg('tenure_months') ?? 0, 1),
            'retention_rate' => 100 - $turnoverRate,
        ];
    }
}
