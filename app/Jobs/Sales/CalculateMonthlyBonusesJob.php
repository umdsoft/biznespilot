<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\SalesBonusCalculation;
use App\Models\SalesBonusSetting;
use App\Models\SalesKpiPeriodSummary;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Oylik bonuslarni hisoblash
 * Har oyning 1-kuni soat 06:00 da ishga tushadi
 */
class CalculateMonthlyBonusesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 3;

    /**
     * Job timeout (30 daqiqa)
     */
    public int $timeout = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null,
        public ?Carbon $periodStart = null
    ) {
        // O'tgan oy uchun hisoblash
        $this->periodStart = $periodStart ?? Carbon::now()->subMonth()->startOfMonth();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CalculateMonthlyBonusesJob started', [
            'business_id' => $this->businessId,
            'period_start' => $this->periodStart->format('Y-m-d'),
        ]);

        if ($this->businessId) {
            $this->processBusinessBonuses($this->businessId);
        } else {
            $this->processAllBusinesses();
        }

        Log::info('CalculateMonthlyBonusesJob completed');
    }

    /**
     * Bitta biznes uchun bonuslarni hisoblash
     */
    protected function processBusinessBonuses(string $businessId): void
    {
        try {
            // Bonus sozlamalarini olish
            $bonusSettings = SalesBonusSetting::where('business_id', $businessId)
                ->where('is_active', true)
                ->get();

            if ($bonusSettings->isEmpty()) {
                Log::info('No active bonus settings for business', [
                    'business_id' => $businessId,
                ]);
                return;
            }

            // Sotuv operatorlarini olish
            $operators = BusinessUser::where('business_id', $businessId)
                ->whereIn('department', ['sales_operator', 'sales_head'])
                ->whereNotNull('accepted_at')
                ->get();

            foreach ($operators as $operator) {
                $this->calculateUserBonus($businessId, $operator->user_id, $bonusSettings);
            }

            Log::info('Business bonuses calculated', [
                'business_id' => $businessId,
                'operators_count' => $operators->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process business bonuses', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Foydalanuvchi uchun bonus hisoblash
     */
    protected function calculateUserBonus(string $businessId, string $userId, $bonusSettings): void
    {
        try {
            DB::transaction(function () use ($businessId, $userId, $bonusSettings) {
                // Oylik KPI summary ni olish
                $periodSummary = SalesKpiPeriodSummary::forBusiness($businessId)
                    ->forUser($userId)
                    ->forPeriodType('monthly')
                    ->forPeriodStart($this->periodStart)
                    ->first();

                if (!$periodSummary) {
                    Log::warning('No period summary found for user', [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'period_start' => $this->periodStart->format('Y-m-d'),
                    ]);
                    return;
                }

                $overallScore = $periodSummary->overall_score;
                $totalBonus = 0;
                $bonusDetails = [];

                foreach ($bonusSettings as $setting) {
                    $bonusAmount = $this->calculateBonusAmount($setting, $periodSummary);

                    if ($bonusAmount > 0) {
                        $bonusDetails[] = [
                            'setting_id' => $setting->id,
                            'setting_name' => $setting->name,
                            'base_amount' => $setting->base_amount,
                            'calculated_amount' => $bonusAmount,
                            'multiplier' => $this->getMultiplier($overallScore),
                        ];
                        $totalBonus += $bonusAmount;
                    }
                }

                if ($totalBonus > 0) {
                    // Bonus yozuvini yaratish
                    SalesBonusCalculation::updateOrCreate(
                        [
                            'business_id' => $businessId,
                            'user_id' => $userId,
                            'period_type' => 'monthly',
                            'period_start' => $this->periodStart->format('Y-m-d'),
                        ],
                        [
                            'period_end' => $this->periodStart->copy()->endOfMonth()->format('Y-m-d'),
                            'overall_score' => $overallScore,
                            'base_amount' => array_sum(array_column($bonusDetails, 'base_amount')),
                            'multiplier' => $this->getMultiplier($overallScore),
                            'final_amount' => $totalBonus,
                            'details' => $bonusDetails,
                            'status' => 'calculated',
                            'calculated_at' => now(),
                        ]
                    );

                    Log::info('User bonus calculated', [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'overall_score' => $overallScore,
                        'total_bonus' => $totalBonus,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to calculate user bonus', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bonus summasini hisoblash
     */
    protected function calculateBonusAmount(SalesBonusSetting $setting, SalesKpiPeriodSummary $summary): float
    {
        $overallScore = $summary->overall_score;

        // Minimal ballni tekshirish
        if ($overallScore < ($setting->min_score ?? 80)) {
            return 0;
        }

        $baseAmount = $setting->base_amount;
        $multiplier = $this->getMultiplier($overallScore);

        return $baseAmount * $multiplier;
    }

    /**
     * KPI ballga qarab multiplier olish
     */
    protected function getMultiplier(int $score): float
    {
        return match (true) {
            $score >= 120 => 1.5,    // Accelerator
            $score >= 100 => 1.2,    // Excellent
            $score >= 80 => 1.0,     // Standard
            default => 0,            // No bonus
        };
    }

    /**
     * Barcha bizneslar uchun bonuslarni hisoblash
     */
    protected function processAllBusinesses(): void
    {
        $businesses = Business::where('status', 'active')
            ->whereHas('bonusSettings', fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        foreach ($businesses as $businessId) {
            try {
                $this->processBusinessBonuses($businessId);
            } catch (\Exception $e) {
                Log::error('Failed to process business bonuses', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Job muvaffaqiyatsiz tugadi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateMonthlyBonusesJob failed', [
            'business_id' => $this->businessId,
            'period_start' => $this->periodStart?->format('Y-m-d'),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
