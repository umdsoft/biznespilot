<?php

namespace App\Services\HR;

use App\Models\Business;
use App\Models\EmployeeEngagement;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * EngagementService - Hodim engagement (ishtirok) darajasini boshqarish
 *
 * Vazifalar:
 * 1. Engagement ballni hisoblash va yangilash
 * 2. So'rovnoma natijalarini qayta ishlash
 * 3. Engagement trendlarni tahlil qilish
 * 4. Team engagement statistikasi
 */
class EngagementService
{
    // Engagement ball hisoblash og'irliklari
    protected array $weights = [
        'survey_score' => 0.35,        // So'rovnoma natijalari
        'activity_score' => 0.25,      // Faollik (login, tasks, etc.)
        'recognition_score' => 0.15,   // Olingan e'tiroflar
        'feedback_score' => 0.10,      // Berilgan va olingan feedback
        'attendance_score' => 0.15,    // Davomat
    ];

    /**
     * Hodim engagement ballini olish
     */
    public function getEmployeeScore(User $employee, Business $business): float
    {
        $engagement = EmployeeEngagement::where('business_id', $business->id)
            ->where('user_id', $employee->id)
            ->first();

        return $engagement?->overall_score ?? 50.0; // Default 50%
    }

    /**
     * So'rovnoma natijasi asosida ballni yangilash
     */
    public function updateFromSurvey(User $employee, Business $business, string $surveyType, float $score, array $responses = []): float
    {
        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'overall_score' => 50.0,
                'survey_score' => 50.0,
                'activity_score' => 50.0,
                'recognition_score' => 50.0,
                'feedback_score' => 50.0,
                'attendance_score' => 50.0,
            ]
        );

        // Survey score ni 1-5 dan 0-100 ga o'girish
        $normalizedScore = ($score / 5) * 100;

        // Weighted average qilish (eski va yangi)
        $newSurveyScore = ($engagement->survey_score * 0.3) + ($normalizedScore * 0.7);

        $engagement->update([
            'survey_score' => round($newSurveyScore, 2),
            'last_survey_date' => now(),
            'last_survey_type' => $surveyType,
        ]);

        // Overall score ni qayta hisoblash
        return $this->recalculateOverallScore($engagement);
    }

    /**
     * Engagement ballni oshirish (recognition, goal completion, etc.)
     */
    public function boostEngagement(User $employee, Business $business, string $reason, int $points): void
    {
        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'overall_score' => 50.0,
                'survey_score' => 50.0,
                'activity_score' => 50.0,
                'recognition_score' => 50.0,
                'feedback_score' => 50.0,
                'attendance_score' => 50.0,
            ]
        );

        // Qaysi komponentni oshirish kerak
        $component = match($reason) {
            'recognition', 'work_anniversary' => 'recognition_score',
            'positive_feedback', 'goal_completed', 'okr_completed' => 'feedback_score',
            'attendance_improved' => 'attendance_score',
            'promotion' => 'overall_score',
            default => 'activity_score',
        };

        $currentScore = $engagement->{$component};
        $newScore = min(100, $currentScore + $points);

        $engagement->update([
            $component => round($newScore, 2),
            'last_boost_date' => now(),
            'last_boost_reason' => $reason,
        ]);

        // Overall score ni qayta hisoblash
        $this->recalculateOverallScore($engagement);

        Log::info('EngagementService: Engagement ball oshirildi', [
            'employee_id' => $employee->id,
            'reason' => $reason,
            'points' => $points,
            'component' => $component,
        ]);
    }

    /**
     * Faollik asosida ballni yangilash
     */
    public function updateActivityScore(User $employee, Business $business, array $activityData): float
    {
        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'overall_score' => 50.0,
                'activity_score' => 50.0,
            ]
        );

        // Faollik ballini hisoblash
        $activityScore = $this->calculateActivityScore($activityData);

        $engagement->update([
            'activity_score' => round($activityScore, 2),
            'last_activity_date' => now(),
        ]);

        return $this->recalculateOverallScore($engagement);
    }

    /**
     * Davomat asosida ballni yangilash
     */
    public function updateAttendanceScore(User $employee, Business $business, array $attendanceData): float
    {
        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
            ],
            [
                'overall_score' => 50.0,
                'attendance_score' => 50.0,
            ]
        );

        // Davomat ballini hisoblash
        $attendanceScore = $this->calculateAttendanceScore($attendanceData);

        $engagement->update([
            'attendance_score' => round($attendanceScore, 2),
        ]);

        return $this->recalculateOverallScore($engagement);
    }

    /**
     * Overall score ni qayta hisoblash
     */
    protected function recalculateOverallScore(EmployeeEngagement $engagement): float
    {
        $overallScore =
            ($engagement->survey_score * $this->weights['survey_score']) +
            ($engagement->activity_score * $this->weights['activity_score']) +
            ($engagement->recognition_score * $this->weights['recognition_score']) +
            ($engagement->feedback_score * $this->weights['feedback_score']) +
            ($engagement->attendance_score * $this->weights['attendance_score']);

        $engagement->update([
            'overall_score' => round($overallScore, 2),
            'updated_at' => now(),
        ]);

        return $overallScore;
    }

    /**
     * Faollik ballini hisoblash
     */
    protected function calculateActivityScore(array $data): float
    {
        $score = 50.0; // Boshlang'ich

        // Login chastotasi (kunlik login = +20)
        if (isset($data['login_days_ratio'])) {
            $score += $data['login_days_ratio'] * 20;
        }

        // Task bajarish (bajarilgan / umumiy * 30)
        if (isset($data['task_completion_rate'])) {
            $score += $data['task_completion_rate'] * 30;
        }

        return min(100, max(0, $score));
    }

    /**
     * Davomat ballini hisoblash
     */
    protected function calculateAttendanceScore(array $data): float
    {
        $score = 100.0; // To'liq davomat

        // Kechikishlar (-5 har bir kechikish uchun)
        if (isset($data['late_count'])) {
            $score -= $data['late_count'] * 5;
        }

        // Yo'qliklar (-10 har bir yo'qlik uchun)
        if (isset($data['absent_count'])) {
            $score -= $data['absent_count'] * 10;
        }

        // Erta ketishlar (-3 har biri uchun)
        if (isset($data['early_leave_count'])) {
            $score -= $data['early_leave_count'] * 3;
        }

        return max(0, $score);
    }

    /**
     * Team engagement statistikasi
     */
    public function getTeamStats(Business $business, ?string $department = null): array
    {
        $query = EmployeeEngagement::where('business_id', $business->id);

        if ($department) {
            $query->whereHas('user', function ($q) use ($department) {
                $q->whereHas('businessUsers', function ($bq) use ($department) {
                    $bq->where('department', $department);
                });
            });
        }

        $engagements = $query->get();

        if ($engagements->isEmpty()) {
            return [
                'average_score' => 0,
                'high_engagement' => 0,
                'low_engagement' => 0,
                'total_employees' => 0,
            ];
        }

        return [
            'average_score' => round($engagements->avg('overall_score'), 2),
            'high_engagement' => $engagements->where('overall_score', '>=', 70)->count(),
            'low_engagement' => $engagements->where('overall_score', '<', 50)->count(),
            'total_employees' => $engagements->count(),
            'score_distribution' => [
                'excellent' => $engagements->where('overall_score', '>=', 85)->count(),
                'good' => $engagements->whereBetween('overall_score', [70, 85])->count(),
                'average' => $engagements->whereBetween('overall_score', [50, 70])->count(),
                'low' => $engagements->whereBetween('overall_score', [30, 50])->count(),
                'critical' => $engagements->where('overall_score', '<', 30)->count(),
            ],
        ];
    }

    /**
     * Engagement trend olish
     */
    public function getEngagementTrend(User $employee, Business $business, int $months = 6): array
    {
        // Bu yerda engagement history jadvalidan ma'lumot olinadi
        // Hozircha mock data
        return [];
    }

    /**
     * Hodim uchun engagement ni hisoblash
     */
    public function calculateForEmployee(Business $business, User $employee): EmployeeEngagement
    {
        $currentPeriod = now()->format('Y-m');

        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $employee->id,
                'period' => $currentPeriod,
            ],
            [
                'overall_score' => 50.0,
                'work_satisfaction' => 50.0,
                'team_collaboration' => 50.0,
                'growth_opportunities' => 50.0,
                'recognition_frequency' => 50.0,
                'manager_support' => 50.0,
                'work_life_balance' => 50.0,
                'purpose_clarity' => 50.0,
                'resources_adequacy' => 50.0,
            ]
        );

        // Overall score ni hisoblash
        $overallScore = $engagement->calculateOverallScore();

        // Engagement level ni aniqlash
        $engagementLevel = match (true) {
            $overallScore >= 80 => 'highly_engaged',
            $overallScore >= 65 => 'engaged',
            $overallScore >= 50 => 'neutral',
            default => 'disengaged',
        };

        $engagement->update([
            'overall_score' => $overallScore,
            'engagement_level' => $engagementLevel,
        ]);

        Log::info('EngagementService: Engagement hisoblandi', [
            'employee_id' => $employee->id,
            'business_id' => $business->id,
            'overall_score' => $overallScore,
            'engagement_level' => $engagementLevel,
        ]);

        return $engagement->fresh();
    }
}
