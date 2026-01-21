<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\EmployeeEngagement;
use App\Models\FlightRisk;
use App\Models\HRSurveyResponse;
use App\Services\HR\RetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSurveyEngagement extends Command
{
    protected $signature = 'hr:process-survey-engagement {--response-id= : Process specific response}';
    protected $description = 'Process survey responses to calculate engagement scores';

    // Gallup Q12 mapping - savollarni komponentlarga bog'lash
    protected array $q12Mapping = [
        0 => 'resources_adequacy',      // Q1: Nimalar kutilishini bilaman
        1 => 'resources_adequacy',      // Q2: Ishni bajarish uchun materiallar
        2 => 'work_satisfaction',       // Q3: Har kuni eng yaxshi ishni qilish
        3 => 'recognition_frequency',   // Q4: Oxirgi 7 kunda tan olinish
        4 => 'manager_support',         // Q5: Menejer menga g'amxo'rlik qiladi
        5 => 'growth_opportunities',    // Q6: Rivojlanishimni rag'batlantiruvchi
        6 => 'purpose_clarity',         // Q7: Fikrim inobatga olinadi
        7 => 'purpose_clarity',         // Q8: Kompaniya missiyasi
        8 => 'team_collaboration',      // Q9: Hamkasblar sifatli ish qiladi
        9 => 'team_collaboration',      // Q10: Eng yaxshi do'stim bor
        10 => 'growth_opportunities',   // Q11: 6 oyda progress haqida gaplashdik
        11 => 'growth_opportunities',   // Q12: O'rganish va o'sish imkoniyatlari
    ];

    public function handle(): int
    {
        $responseId = $this->option('response-id');

        $query = HRSurveyResponse::with(['survey', 'user'])
            ->whereHas('survey', function ($q) {
                $q->whereIn('type', ['engagement', 'pulse']);
            });

        if ($responseId) {
            $query->where('id', $responseId);
        }

        $responses = $query->get();

        $this->info("Found {$responses->count()} engagement survey responses to process");

        foreach ($responses as $response) {
            $this->processResponse($response);
        }

        $this->info('Done!');
        return Command::SUCCESS;
    }

    protected function processResponse(HRSurveyResponse $response): void
    {
        $survey = $response->survey;
        $answers = $response->answers ?? [];
        $businessId = $response->business_id;

        // user_id ni aniqlash - response.user_id bo'lmasa, survey'dan qidirish
        $userId = $response->user_id;

        if (!$userId) {
            // Anonim so'rovnoma uchun - bu javobni kim berganini aniqlay olmaymiz
            // Lekin demo uchun birinchi user'ni olamiz
            $businessUser = \App\Models\BusinessUser::where('business_id', $businessId)->first();
            if ($businessUser) {
                $userId = $businessUser->user_id;
                $this->warn("Response {$response->id} is anonymous, using business user: {$userId}");
            }
        }

        if (!$userId) {
            $this->error("Cannot determine user for response {$response->id}");
            return;
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error("User not found: {$userId}");
            return;
        }

        $this->info("Processing response {$response->id} for user {$user->name}");

        // Har bir komponent uchun balllarni yig'ish
        $componentScores = [
            'work_satisfaction' => [],
            'team_collaboration' => [],
            'growth_opportunities' => [],
            'recognition_frequency' => [],
            'manager_support' => [],
            'work_life_balance' => [],
            'purpose_clarity' => [],
            'resources_adequacy' => [],
        ];

        foreach ($answers as $key => $value) {
            // q_0, q_1, ... formatidan indeksni olish
            if (preg_match('/q_(\d+)/', $key, $matches)) {
                $index = (int) $matches[1];

                // Agar scale/rating javob bo'lsa (1-5)
                if (is_numeric($value)) {
                    $component = $this->q12Mapping[$index] ?? null;
                    if ($component && isset($componentScores[$component])) {
                        // 1-5 ni 0-100 ga o'girish
                        $normalizedScore = (($value - 1) / 4) * 100;
                        $componentScores[$component][] = $normalizedScore;
                        $this->line("  Q{$index}: {$value} -> {$component}: {$normalizedScore}");
                    }
                }
            }
        }

        // Har bir komponent uchun o'rtacha ball hisoblash
        $period = now()->format('Y-m');
        $engagement = EmployeeEngagement::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'period' => $period,
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

        $updateData = ['last_survey_at' => now()];

        foreach ($componentScores as $component => $scores) {
            if (!empty($scores)) {
                $avgScore = array_sum($scores) / count($scores);
                $updateData[$component] = round($avgScore, 2);
                $this->line("  {$component}: " . round($avgScore, 2));
            }
        }

        $engagement->update($updateData);

        // Overall score ni hisoblash
        $overallScore = $engagement->fresh()->calculateOverallScore();

        // Engagement level aniqlash
        $engagementLevel = match (true) {
            $overallScore >= 80 => 'highly_engaged',
            $overallScore >= 65 => 'engaged',
            $overallScore >= 50 => 'neutral',
            default => 'disengaged',
        };

        $engagement->update([
            'overall_score' => $overallScore,
            'engagement_level' => $engagementLevel,
            'q12_responses' => $answers,
        ]);

        $this->info("  Overall score: {$overallScore}, Level: {$engagementLevel}");

        // Flight risk ni yangilash
        $business = Business::find($businessId);
        if ($business) {
            try {
                $retentionService = app(RetentionService::class);
                $retentionService->updateFlightRiskFromEngagement($user, $business, $overallScore);
                $this->info("  Flight risk updated");
            } catch (\Exception $e) {
                $this->error("  Flight risk error: " . $e->getMessage());
            }
        }
    }
}
