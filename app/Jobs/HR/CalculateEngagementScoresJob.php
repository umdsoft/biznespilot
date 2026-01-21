<?php

namespace App\Jobs\HR;

use App\Models\Business;
use App\Models\User;
use App\Models\EmployeeEngagement;
use App\Services\HR\EngagementService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CalculateEngagementScoresJob - Kunlik engagement balllarni hisoblash
 *
 * Bu job har kuni ishga tushadi va barcha hodimlar uchun
 * engagement ballini avtomatik yangilaydi.
 *
 * Engagement komponentlari:
 * - Davomat (attendance_records)
 * - Faollik (tasks, call_logs)
 * - Tan olishlar (hr_recognitions)
 * - 1-on-1 lar (hr_one_on_one_meetings)
 * - So'rovnoma javoblari (hr_survey_responses)
 */
class CalculateEngagementScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 900; // 15 daqiqa

    public function __construct(
        public ?string $businessId = null,
        public ?string $userId = null
    ) {}

    public function handle(EngagementService $engagementService): void
    {
        Log::info('CalculateEngagementScoresJob boshlandi', [
            'business_id' => $this->businessId,
            'user_id' => $this->userId,
        ]);

        $period = Carbon::now()->format('Y-m');

        if ($this->userId) {
            $this->calculateForUser($engagementService, $this->userId, $period);
        } elseif ($this->businessId) {
            $this->calculateForBusiness($engagementService, $this->businessId, $period);
        } else {
            $this->calculateForAllBusinesses($engagementService, $period);
        }

        Log::info('CalculateEngagementScoresJob yakunlandi');
    }

    protected function calculateForUser(EngagementService $engagementService, string $userId, string $period): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        foreach ($user->businesses as $business) {
            try {
                $this->calculateUserEngagement($engagementService, $user, $business, $period);
            } catch (\Exception $e) {
                Log::error('Hodim engagement hisoblashda xato', [
                    'user_id' => $userId,
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function calculateForBusiness(EngagementService $engagementService, string $businessId, string $period): void
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

        foreach ($employees as $employee) {
            try {
                $this->calculateUserEngagement($engagementService, $employee, $business, $period);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::warning('Hodim engagement hisoblashda xato', [
                    'user_id' => $employee->id,
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Biznes engagement hisoblash yakunlandi', [
            'business_id' => $businessId,
            'employees_count' => $employees->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ]);
    }

    protected function calculateForAllBusinesses(EngagementService $engagementService, string $period): void
    {
        $businesses = Business::where('status', 'active')->pluck('id');

        $processedCount = 0;
        $errorCount = 0;

        foreach ($businesses as $businessId) {
            try {
                $this->calculateForBusiness($engagementService, $businessId, $period);
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Biznes engagement hisoblashda xato', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Barcha bizneslar engagement hisoblash yakunlandi', [
            'businesses_processed' => $processedCount,
            'businesses_failed' => $errorCount,
        ]);
    }

    protected function calculateUserEngagement(
        EngagementService $engagementService,
        User $user,
        Business $business,
        string $period
    ): void {
        // Mavjud yoki yangi engagement record
        $engagement = EmployeeEngagement::firstOrCreate([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'period' => $period,
        ]);

        $oldScore = $engagement->overall_score;

        // Komponent balllarini hisoblash
        $scores = $this->calculateComponentScores($user, $business);

        // Umumiy ballni hisoblash (weighted average)
        $overallScore = $this->calculateWeightedScore($scores);

        // Oldingi ball bilan solishtirish
        $scoreChange = $overallScore - $oldScore;

        // Trend aniqlash
        $trend = match(true) {
            $scoreChange >= 5 => 'improving',
            $scoreChange <= -5 => 'declining',
            default => 'stable',
        };

        // Level aniqlash
        $level = match(true) {
            $overallScore >= 86 => 'highly_engaged',
            $overallScore >= 71 => 'engaged',
            $overallScore >= 51 => 'neutral',
            $overallScore >= 31 => 'disengaged',
            default => 'highly_disengaged',
        };

        // Yangilash
        $engagement->update([
            'work_satisfaction' => $scores['work_satisfaction'],
            'team_collaboration' => $scores['team_collaboration'],
            'growth_opportunities' => $scores['growth_opportunities'],
            'recognition_frequency' => $scores['recognition_frequency'],
            'manager_support' => $scores['manager_support'],
            'work_life_balance' => $scores['work_life_balance'],
            'purpose_clarity' => $scores['purpose_clarity'],
            'resources_adequacy' => $scores['resources_adequacy'],
            'overall_score' => $overallScore,
            'previous_score' => $oldScore ?: $overallScore,
            'score_change' => $scoreChange,
            'trend' => $trend,
            'engagement_level' => $level,
        ]);

        // Sezilarli o'zgarish bo'lsa tarixga qo'shish
        if (abs($scoreChange) >= 5) {
            $engagement->recordScoreChange($oldScore, $overallScore, 'daily_calculation');
        }
    }

    protected function calculateComponentScores(User $user, Business $business): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();

        // 1. Davomat asosida ish qoniqishi
        $attendanceScore = $this->calculateAttendanceScore($user, $business, $startDate, $endDate);

        // 2. Task va faollik asosida team collaboration
        $activityScore = $this->calculateActivityScore($user, $business, $startDate, $endDate);

        // 3. Tan olishlar asosida recognition
        $recognitionScore = $this->calculateRecognitionScore($user, $business, $startDate, $endDate);

        // 4. 1-on-1 lar asosida manager support
        $managerSupportScore = $this->calculateManagerSupportScore($user, $business, $startDate, $endDate);

        // 5. Oxirgi so'rovnoma javoblari (Q12)
        $surveyScores = $this->getLastSurveyScores($user, $business);

        return [
            'work_satisfaction' => $surveyScores['work_satisfaction'] ?? $attendanceScore,
            'team_collaboration' => $surveyScores['team_collaboration'] ?? $activityScore,
            'growth_opportunities' => $surveyScores['growth_opportunities'] ?? 50, // Default
            'recognition_frequency' => $recognitionScore,
            'manager_support' => $managerSupportScore,
            'work_life_balance' => $surveyScores['work_life_balance'] ?? $this->calculateWorkLifeBalance($user, $business),
            'purpose_clarity' => $surveyScores['purpose_clarity'] ?? 50, // Default
            'resources_adequacy' => $surveyScores['resources_adequacy'] ?? 50, // Default
        ];
    }

    protected function calculateAttendanceScore(User $user, Business $business, Carbon $startDate, Carbon $endDate): float
    {
        $records = $user->attendanceRecords()
            ->where('business_id', $business->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        if ($records->isEmpty()) {
            return 50; // Default
        }

        $presentDays = $records->whereIn('status', ['present', 'wfh'])->count();
        $totalDays = $records->count();

        $presentRate = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 50;

        // Late kunlarni ham hisobga olish
        $lateDays = $records->where('status', 'late')->count();
        $lateDeduction = min($lateDays * 2, 20); // Max 20 ball chiqariladi

        return max(0, min(100, $presentRate - $lateDeduction));
    }

    protected function calculateActivityScore(User $user, Business $business, Carbon $startDate, Carbon $endDate): float
    {
        // Bajarilgan tasklar
        $completedTasks = $user->tasks()
            ->where('business_id', $business->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();

        // Call loglar
        $calls = $user->callLogs()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Faollik ball
        $taskScore = min($completedTasks * 5, 50); // Max 50 ball taskdan
        $callScore = min($calls * 2, 50); // Max 50 ball calldan

        return $taskScore + $callScore;
    }

    protected function calculateRecognitionScore(User $user, Business $business, Carbon $startDate, Carbon $endDate): float
    {
        // Bu jadval migratsiyada yaratildi
        $recognitions = \DB::table('hr_recognitions')
            ->where('business_id', $business->id)
            ->where('to_user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Har bir tan olish 10 ball
        return min($recognitions * 10, 100);
    }

    protected function calculateManagerSupportScore(User $user, Business $business, Carbon $startDate, Carbon $endDate): float
    {
        $meetings = \DB::table('hr_one_on_one_meetings')
            ->where('business_id', $business->id)
            ->where('employee_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();

        // Har bir 1-on-1 20 ball
        return min($meetings * 20, 100);
    }

    protected function calculateWorkLifeBalance(User $user, Business $business): float
    {
        // Oxirgi 30 kundagi overtime
        $overtimeRecords = $user->attendanceRecords()
            ->where('business_id', $business->id)
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->where('work_hours', '>', 9) // 9 soatdan ko'p = overtime
            ->count();

        // Ko'p overtime = past work-life balance
        return max(0, 100 - ($overtimeRecords * 5));
    }

    protected function getLastSurveyScores(User $user, Business $business): array
    {
        $lastResponse = \DB::table('hr_survey_responses')
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastResponse || !$lastResponse->answers) {
            return [];
        }

        $answers = json_decode($lastResponse->answers, true);

        // Q12 savollariga mos komponentlar
        return $this->mapSurveyAnswersToComponents($answers);
    }

    protected function mapSurveyAnswersToComponents(array $answers): array
    {
        // Q12 savollarining mapping
        // Bu yerda so'rovnoma tuzilishiga qarab mapping qilinadi
        return [
            'work_satisfaction' => $answers['q1_score'] ?? null,
            'team_collaboration' => $answers['q5_score'] ?? null,
            'growth_opportunities' => $answers['q6_score'] ?? null,
            'purpose_clarity' => $answers['q8_score'] ?? null,
            'resources_adequacy' => $answers['q2_score'] ?? null,
            'work_life_balance' => $answers['q11_score'] ?? null,
        ];
    }

    protected function calculateWeightedScore(array $scores): float
    {
        $weights = [
            'work_satisfaction' => 0.15,
            'team_collaboration' => 0.125,
            'growth_opportunities' => 0.15,
            'recognition_frequency' => 0.125,
            'manager_support' => 0.15,
            'work_life_balance' => 0.10,
            'purpose_clarity' => 0.10,
            'resources_adequacy' => 0.10,
        ];

        $total = 0;
        foreach ($scores as $key => $score) {
            $total += ($score ?? 50) * ($weights[$key] ?? 0);
        }

        return round($total, 2);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateEngagementScoresJob muvaffaqiyatsiz', [
            'business_id' => $this->businessId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
