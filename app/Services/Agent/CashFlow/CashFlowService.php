<?php

namespace App\Services\Agent\CashFlow;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pul oqimi bashoratchi — 30-60 kunlik kirim-chiqim bashorati.
 * Xavfli sanalarni oldindan aniqlaydi va tavsiya beradi.
 *
 * Gibrid: bazadan hisoblash (bepul) + xavf bo'lsa Haiku tavsiya
 */
class CashFlowService
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * 30 kunlik bashorat yaratish
     */
    public function forecast(string $businessId, int $days = 30): array
    {
        try {
            // 1. Sozlamalarni olish
            $settings = DB::table('cash_flow_settings')
                ->where('business_id', $businessId)->first();

            $minBalance = $settings ? (float) $settings->minimum_balance : 1000000;
            $recurringExpenses = $settings ? json_decode($settings->recurring_expenses, true) : [];

            // 2. Oxirgi 90 kun kirim ma'lumotlari (bazadan, bepul)
            $dailyIncome = $this->getDailyIncomeHistory($businessId, 90);

            // 3. Hafta kunlari bo'yicha o'rtacha kirim
            $avgByDayOfWeek = $this->calculateAvgByDayOfWeek($dailyIncome);

            // 4. Hozirgi balans
            $currentBalance = $this->getCurrentBalance($businessId);

            // 5. Kunma-kun bashorat
            $forecasts = [];
            $balance = $currentBalance;
            $dangerDates = [];

            for ($i = 1; $i <= $days; $i++) {
                $date = now()->addDays($i);
                $dayOfWeek = $date->dayOfWeek;
                $dayOfMonth = $date->day;

                // Bashorat kirim
                $predictedIncome = $avgByDayOfWeek[$dayOfWeek] ?? 0;

                // Doimiy xarajatlar
                $predictedExpense = 0;
                foreach ($recurringExpenses as $expense) {
                    if (($expense['day_of_month'] ?? 0) === $dayOfMonth) {
                        $predictedExpense += (float) ($expense['amount'] ?? 0);
                    }
                }

                $balance = $balance + $predictedIncome - $predictedExpense;
                $isDanger = $balance < $minBalance;

                $forecast = [
                    'date' => $date->toDateString(),
                    'predicted_income' => round($predictedIncome, 2),
                    'predicted_expense' => round($predictedExpense, 2),
                    'predicted_balance' => round($balance, 2),
                    'is_danger' => $isDanger,
                    'confidence' => min(0.9, max(0.3, 1 - ($i / ($days * 2)))),
                ];

                $forecasts[] = $forecast;

                if ($isDanger) {
                    $dangerDates[] = $forecast;
                }

                // Bashoratni saqlash
                DB::table('cash_flow_forecasts')->insert([
                    'id' => Str::uuid()->toString(),
                    'business_id' => $businessId,
                    'forecast_date' => $forecast['date'],
                    'predicted_income' => $forecast['predicted_income'],
                    'predicted_expense' => $forecast['predicted_expense'],
                    'predicted_balance' => $forecast['predicted_balance'],
                    'confidence_level' => $forecast['confidence'],
                    'is_danger' => $isDanger,
                    'created_at' => now(),
                ]);
            }

            // 6. Xavfli sanalar uchun AI tavsiya
            $recommendations = null;
            if (!empty($dangerDates)) {
                $recommendations = $this->getAIRecommendations($businessId, $dangerDates, $minBalance);
            }

            return [
                'success' => true,
                'current_balance' => $currentBalance,
                'forecast_days' => $days,
                'danger_dates_count' => count($dangerDates),
                'danger_dates' => array_slice($dangerDates, 0, 5),
                'recommendations' => $recommendations,
                'forecasts' => $forecasts,
            ];

        } catch (\Exception $e) {
            Log::error('CashFlow: bashorat xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kunlik kirim tarixini olish
     */
    private function getDailyIncomeHistory(string $businessId, int $days): array
    {
        return DB::table('sales')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, DAYOFWEEK(created_at) as dow, SUM(amount) as total')
            ->groupByRaw('DATE(created_at), DAYOFWEEK(created_at)')
            ->get()
            ->toArray();
    }

    /**
     * Hafta kunlari bo'yicha o'rtacha kirim
     */
    private function calculateAvgByDayOfWeek(array $dailyIncome): array
    {
        $byDow = [];
        foreach ($dailyIncome as $row) {
            $dow = $row->dow - 1; // MySQL 1-7 → PHP 0-6
            $byDow[$dow][] = (float) $row->total;
        }

        $avgByDow = [];
        foreach ($byDow as $dow => $values) {
            $avgByDow[$dow] = array_sum($values) / count($values);
        }

        return $avgByDow;
    }

    /**
     * Hozirgi balans (oxirgi 30 kun kirim - chiqim)
     */
    private function getCurrentBalance(string $businessId): float
    {
        $income = (float) DB::table('sales')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $expense = (float) DB::table('marketing_spends')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        return $income - $expense;
    }

    /**
     * Xavfli sanalar uchun AI tavsiya
     */
    private function getAIRecommendations(string $businessId, array $dangerDates, float $minBalance): ?string
    {
        $firstDanger = $dangerDates[0];
        $shortfall = $minBalance - $firstDanger['predicted_balance'];

        $response = $this->aiService->ask(
            prompt: "{$firstDanger['date']} sanada balans " . number_format($firstDanger['predicted_balance']) . " so'mga tushadi. "
                . "Minimal qoldiq: " . number_format($minBalance) . " so'm. Kamomad: " . number_format($shortfall) . " so'm. "
                . "Jami {" . count($dangerDates) . "} ta xavfli sana bor. 3 ta amaliy tavsiya ber.",
            systemPrompt: "Sen BiznesPilot moliyaviy maslahatchi agentisan. Pul oqimi xavfi haqida oddiy tilda, biznes egasi tushunadi qilib maslahat ber. O'zbek tilida. Faqat amaliy tavsiya.",
            preferredModel: 'haiku',
            maxTokens: 400,
            businessId: $businessId,
            agentType: 'cash_flow',
        );

        return $response->success ? $response->content : null;
    }
}
