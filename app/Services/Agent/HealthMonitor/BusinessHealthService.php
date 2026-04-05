<?php

namespace App\Services\Agent\HealthMonitor;

use App\Models\BusinessHealthScore;
use App\Services\Agent\HealthMonitor\Calculators\CustomerHealthCalculator;
use App\Services\Agent\HealthMonitor\Calculators\FinanceHealthCalculator;
use App\Services\Agent\HealthMonitor\Calculators\MarketingHealthCalculator;
use App\Services\Agent\HealthMonitor\Calculators\SalesHealthCalculator;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Biznes sog'ligi monitori — haftalik 0-100 ball.
 * 4 soha: Marketing (25%) + Sotuv (30%) + Moliya (25%) + Mijoz (20%)
 *
 * Gibrid: bazadan hisoblash (bepul), 70 dan past bo'lsa Haiku tavsiya
 */
class BusinessHealthService
{
    public function __construct(
        private AIService $aiService,
        private MarketingHealthCalculator $marketingCalc,
        private SalesHealthCalculator $salesCalc,
        private FinanceHealthCalculator $financeCalc,
        private CustomerHealthCalculator $customerCalc,
    ) {}

    /**
     * Biznes sog'ligini hisoblash
     */
    public function calculate(string $businessId): array
    {
        try {
            // 1. Barcha sohalarni hisoblash (bazadan, bepul)
            $marketing = $this->marketingCalc->calculate($businessId);
            $sales = $this->salesCalc->calculate($businessId);
            $finance = $this->financeCalc->calculate($businessId);
            $customer = $this->customerCalc->calculate($businessId);

            // 2. Umumiy ball
            $overall = (int) round(
                $marketing['score'] * 0.25
                + $sales['score'] * 0.30
                + $finance['score'] * 0.25
                + $customer['score'] * 0.20
            );

            // 3. Oldingi hafta bilan solishtirish
            $previous = BusinessHealthScore::where('business_id', $businessId)
                ->orderByDesc('period_end')
                ->first();

            $previousScore = $previous ? $previous->overall_score : null;
            $change = $previousScore !== null ? $overall - $previousScore : null;

            // 4. Muammolarni aniqlash
            $issues = $this->detectIssues($marketing, $sales, $finance, $customer);

            // 5. Agar ball 70 dan past — AI tavsiya
            $recommendations = null;
            $aiTokens = 0;
            if ($overall < 70 && !empty($issues)) {
                $aiResult = $this->getAIRecommendations($businessId, $overall, $issues);
                $recommendations = $aiResult['recommendations'];
                $aiTokens = $aiResult['tokens'];
            }

            // 6. Saqlash
            $record = BusinessHealthScore::create([
                'business_id' => $businessId,
                'period_start' => now()->subDays(7)->toDateString(),
                'period_end' => now()->toDateString(),
                'overall_score' => $overall,
                'marketing_score' => $marketing['score'],
                'marketing_details' => $marketing['details'],
                'sales_score' => $sales['score'],
                'sales_details' => $sales['details'],
                'finance_score' => $finance['score'],
                'finance_details' => $finance['details'],
                'customer_score' => $customer['score'],
                'customer_details' => $customer['details'],
                'previous_overall_score' => $previousScore,
                'change_from_previous' => $change,
                'top_issues' => $issues,
                'recommendations' => $recommendations,
                'ai_tokens_used' => $aiTokens,
            ]);

            return [
                'success' => true,
                'overall_score' => $overall,
                'grade' => $this->getGrade($overall),
                'marketing' => $marketing,
                'sales' => $sales,
                'finance' => $finance,
                'customer' => $customer,
                'change' => $change,
                'issues' => $issues,
                'recommendations' => $recommendations,
            ];

        } catch (\Exception $e) {
            Log::error('BusinessHealth: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Muammolarni aniqlash (past ballar)
     */
    private function detectIssues(array $marketing, array $sales, array $finance, array $customer): array
    {
        $issues = [];

        foreach ($marketing['details'] as $key => $value) {
            if ($value < 40) $issues[] = "Marketing: {$key} past ({$value})";
        }
        foreach ($sales['details'] as $key => $value) {
            if ($value < 40) $issues[] = "Sotuv: {$key} past ({$value})";
        }
        foreach ($finance['details'] as $key => $value) {
            if ($value < 40) $issues[] = "Moliya: {$key} past ({$value})";
        }
        foreach ($customer['details'] as $key => $value) {
            if ($value < 40) $issues[] = "Mijoz: {$key} past ({$value})";
        }

        return $issues;
    }

    /**
     * AI tavsiya olish (faqat ball 70 dan past bo'lganda)
     */
    private function getAIRecommendations(string $businessId, int $score, array $issues): array
    {
        $issuesText = implode("\n", $issues);
        $response = $this->aiService->ask(
            prompt: "Biznes sog'lik bali: {$score}/100\nMuammolar:\n{$issuesText}\n\nOddiy tilda 3 ta aniq maslahat ber.",
            systemPrompt: "Sen BiznesPilot biznes maslahatchisisan. Biznes sog'ligi bo'yicha oddiy tilda, biznes egasi tushunadi qilib maslahat ber. O'zbek tilida.",
            preferredModel: 'haiku',
            maxTokens: 400,
            businessId: $businessId,
            agentType: 'health_monitor',
        );

        return [
            'recommendations' => $response->success ? $response->content : null,
            'tokens' => $response->tokensInput + $response->tokensOutput,
        ];
    }

    /**
     * Ball bo'yicha daraja
     */
    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => "A'lo",
            $score >= 70 => 'Yaxshi',
            $score >= 50 => "O'rtacha",
            $score >= 30 => 'Xavfli',
            default => 'Tanazzul',
        };
    }
}
