<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\Lead;
use App\Models\MarketingExpense;
use App\Models\MarketingTarget;
use App\Models\MarketingUserKpi;
use App\Models\User;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UpdateMarketingUserKpisJob - Marketing foydalanuvchi KPIlarini yangilash
 * Har kuni ishga tushiriladi
 *
 * DRY: HasPeriodCalculation va HasKpiCalculation traitlardan foydalanadi
 */
class UpdateMarketingUserKpisJob implements ShouldQueue
{
    use HasPeriodCalculation;
    use HasKpiCalculation;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null,
        public string $periodType = 'daily',
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? now();
    }

    public function handle(): void
    {
        Log::info('UpdateMarketingUserKpisJob: Starting', [
            'business_id' => $this->businessId,
            'period_type' => $this->periodType,
            'date' => $this->date->toDateString(),
        ]);

        $businesses = $this->businessId
            ? Business::where('id', $this->businessId)->get()
            : Business::where('status', 'active')->get();

        foreach ($businesses as $business) {
            try {
                $this->updateForBusiness($business);
            } catch (\Exception $e) {
                Log::error('UpdateMarketingUserKpisJob: Failed for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('UpdateMarketingUserKpisJob: Completed', [
            'businesses_count' => $businesses->count(),
        ]);
    }

    protected function updateForBusiness(Business $business): void
    {
        // Get marketing users
        $marketingUsers = $business->users()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'marketing');
            })
            ->get();

        // Calculate period boundaries
        [$periodStart, $periodEnd] = $this->getPeriodBoundaries();

        // Update business-wide KPI (no user filter)
        $this->updateKpi($business, null, $periodStart, $periodEnd);

        // Update per-user KPIs
        foreach ($marketingUsers as $user) {
            $this->updateKpi($business, $user, $periodStart, $periodEnd);
        }

        Log::info('UpdateMarketingUserKpisJob: Business completed', [
            'business_id' => $business->id,
            'users_count' => $marketingUsers->count(),
        ]);
    }

    protected function updateKpi(Business $business, ?User $user, Carbon $periodStart, Carbon $periodEnd): void
    {
        DB::transaction(function () use ($business, $user, $periodStart, $periodEnd) {
            // Build base queries
            $leadsQuery = Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd]);

            $expensesQuery = MarketingExpense::where('business_id', $business->id)
                ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')]);

            // Filter by user if specified
            if ($user) {
                $leadsQuery->where('assigned_to', $user->id);
                // Expenses might not be user-specific, so we skip that filter
            }

            // Calculate metrics
            $leadsCount = $leadsQuery->count();
            $qualifiedLeads = (clone $leadsQuery)->where('is_qualified', true)->count();
            $convertedLeads = (clone $leadsQuery)->where('status', 'won')->count();
            $totalRevenue = (clone $leadsQuery)->where('status', 'won')->sum('deal_value');
            $totalSpend = $user ? 0 : $expensesQuery->sum('amount'); // Only count spend for business-wide

            // Calculate derived metrics
            $cplActual = $leadsCount > 0 && $totalSpend > 0 ? $totalSpend / $leadsCount : 0;
            $roasActual = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
            $roiActual = $totalSpend > 0 ? (($totalRevenue - $totalSpend) / $totalSpend) * 100 : 0;

            // Get targets for completion calculation
            $targetCompletion = $this->calculateTargetCompletion($business, $user, $periodStart, $leadsCount, $totalRevenue);

            // Update or create KPI record
            MarketingUserKpi::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'user_id' => $user?->id,
                    'period_start' => $periodStart,
                    'period_type' => $this->periodType,
                ],
                [
                    'period_end' => $periodEnd,
                    'leads_count' => $leadsCount,
                    'qualified_leads' => $qualifiedLeads,
                    'converted_leads' => $convertedLeads,
                    'total_spend' => $totalSpend,
                    'total_revenue' => $totalRevenue,
                    'cpl_actual' => $cplActual,
                    'cpl_target' => $this->getTargetValue($business, $user, $periodStart, 'cpl'),
                    'roas_actual' => $roasActual,
                    'roas_target' => $this->getTargetValue($business, $user, $periodStart, 'roas'),
                    'roi_target' => $this->getTargetValue($business, $user, $periodStart, 'roi'),
                    'target_completion' => $targetCompletion,
                    'performance_data' => [
                        'by_channel' => $this->getChannelBreakdown($business, $user, $periodStart, $periodEnd),
                        'by_status' => $this->getStatusBreakdown($leadsQuery),
                        'updated_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });
    }

    protected function getPeriodBoundaries(): array
    {
        return match ($this->periodType) {
            'daily' => [
                $this->date->copy()->startOfDay(),
                $this->date->copy()->endOfDay(),
            ],
            'weekly' => [
                $this->date->copy()->startOfWeek(),
                $this->date->copy()->endOfWeek(),
            ],
            'monthly' => [
                $this->date->copy()->startOfMonth(),
                $this->date->copy()->endOfMonth(),
            ],
            default => [
                $this->date->copy()->startOfDay(),
                $this->date->copy()->endOfDay(),
            ],
        };
    }

    protected function calculateTargetCompletion(
        Business $business,
        ?User $user,
        Carbon $periodStart,
        int $leadsCount,
        float $revenue
    ): float {
        $leadsTarget = $this->getTargetValue($business, $user, $periodStart, 'leads');
        $revenueTarget = $this->getTargetValue($business, $user, $periodStart, 'revenue');

        $completions = [];

        if ($leadsTarget > 0) {
            $completions[] = min(100, ($leadsCount / $leadsTarget) * 100);
        }

        if ($revenueTarget > 0) {
            $completions[] = min(100, ($revenue / $revenueTarget) * 100);
        }

        return !empty($completions) ? array_sum($completions) / count($completions) : 0;
    }

    protected function getTargetValue(Business $business, ?User $user, Carbon $periodStart, string $type): float
    {
        $target = MarketingTarget::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->where('target_type', $type)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user?->id)
                    ->orWhereNull('user_id');
            })
            ->orderByRaw('user_id IS NULL') // Prefer user-specific target
            ->first();

        return $target?->target_value ?? 0;
    }

    protected function getChannelBreakdown(Business $business, ?User $user, Carbon $periodStart, Carbon $periodEnd): array
    {
        $query = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereNotNull('channel_id');

        if ($user) {
            $query->where('assigned_to', $user->id);
        }

        return $query->select('channel_id', DB::raw('COUNT(*) as count'))
            ->groupBy('channel_id')
            ->pluck('count', 'channel_id')
            ->toArray();
    }

    protected function getStatusBreakdown($leadsQuery): array
    {
        return (clone $leadsQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function tags(): array
    {
        return [
            'marketing-user-kpi',
            'period:' . $this->periodType,
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
