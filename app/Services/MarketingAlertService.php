<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\MarketingAlert;
use App\Models\MarketingChannel;
use App\Models\MarketingUserKpi;
use App\Models\User;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * MarketingAlertService - Marketing alertlar boshqarish
 *
 * DRY: HasKpiCalculation traitdan foydalanadi (deviation hisoblash uchun)
 */
class MarketingAlertService
{
    use HasKpiCalculation;
    // Alert thresholds
    private const CPL_DEVIATION_WARNING = 0.15; // 15% above target
    private const CPL_DEVIATION_CRITICAL = 0.30; // 30% above target
    private const ROAS_DEVIATION_WARNING = 0.20; // 20% below target
    private const ROAS_DEVIATION_CRITICAL = 0.40; // 40% below target
    private const LEAD_DROP_WARNING = 0.25; // 25% drop
    private const LEAD_DROP_CRITICAL = 0.50; // 50% drop
    private const BUDGET_WARNING_THRESHOLD = 0.80; // 80% spent
    private const BUDGET_CRITICAL_THRESHOLD = 0.95; // 95% spent

    public function checkAndCreateAlerts(Business $business): Collection
    {
        $alerts = collect();

        // Check CPL alerts
        $alerts = $alerts->merge($this->checkCplAlerts($business));

        // Check ROAS alerts
        $alerts = $alerts->merge($this->checkRoasAlerts($business));

        // Check Lead drop alerts
        $alerts = $alerts->merge($this->checkLeadDropAlerts($business));

        // Check Budget alerts
        $alerts = $alerts->merge($this->checkBudgetAlerts($business));

        // Check ROI alerts
        $alerts = $alerts->merge($this->checkRoiAlerts($business));

        Log::info('Marketing alerts checked', [
            'business_id' => $business->id,
            'alerts_created' => $alerts->count(),
        ]);

        return $alerts;
    }

    public function checkCplAlerts(Business $business): Collection
    {
        $alerts = collect();

        // Get channels with CPL data
        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->whereNotNull('target_cpl')
            ->get();

        foreach ($channels as $channel) {
            // Get recent performance data
            $recentCpl = $this->getRecentChannelCpl($channel);

            if ($recentCpl <= 0 || !$channel->target_cpl) {
                continue;
            }

            $deviation = ($recentCpl - $channel->target_cpl) / $channel->target_cpl;

            if ($deviation >= self::CPL_DEVIATION_CRITICAL) {
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_CPL_HIGH,
                    'severity' => MarketingAlert::SEVERITY_CRITICAL,
                    'title' => "CPL kritik darajada yuqori: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida CPL %.0f%% yuqori (Hozirgi: %s, Target: %s)',
                        $channel->name,
                        $deviation * 100,
                        number_format($recentCpl, 0),
                        number_format($channel->target_cpl, 0)
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $channel->target_cpl,
                    'actual_value' => $recentCpl,
                    'deviation_percent' => $deviation * 100,
                ]);
                $alerts->push($alert);
            } elseif ($deviation >= self::CPL_DEVIATION_WARNING) {
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_CPL_HIGH,
                    'severity' => MarketingAlert::SEVERITY_WARNING,
                    'title' => "CPL yuqorilashmoqda: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida CPL %.0f%% yuqori (Hozirgi: %s, Target: %s)',
                        $channel->name,
                        $deviation * 100,
                        number_format($recentCpl, 0),
                        number_format($channel->target_cpl, 0)
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $channel->target_cpl,
                    'actual_value' => $recentCpl,
                    'deviation_percent' => $deviation * 100,
                ]);
                $alerts->push($alert);
            }
        }

        return $alerts;
    }

    public function checkRoasAlerts(Business $business): Collection
    {
        $alerts = collect();

        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->whereNotNull('target_roas')
            ->get();

        foreach ($channels as $channel) {
            $recentRoas = $this->getRecentChannelRoas($channel);

            if ($recentRoas <= 0 || !$channel->target_roas) {
                continue;
            }

            $deviation = ($channel->target_roas - $recentRoas) / $channel->target_roas;

            if ($deviation >= self::ROAS_DEVIATION_CRITICAL) {
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_ROAS_LOW,
                    'severity' => MarketingAlert::SEVERITY_CRITICAL,
                    'title' => "ROAS kritik darajada past: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida ROAS %.0f%% past (Hozirgi: %.2fx, Target: %.2fx)',
                        $channel->name,
                        $deviation * 100,
                        $recentRoas,
                        $channel->target_roas
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $channel->target_roas,
                    'actual_value' => $recentRoas,
                    'deviation_percent' => $deviation * 100,
                ]);
                $alerts->push($alert);
            } elseif ($deviation >= self::ROAS_DEVIATION_WARNING) {
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_ROAS_LOW,
                    'severity' => MarketingAlert::SEVERITY_WARNING,
                    'title' => "ROAS pasaymoqda: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida ROAS %.0f%% past (Hozirgi: %.2fx, Target: %.2fx)',
                        $channel->name,
                        $deviation * 100,
                        $recentRoas,
                        $channel->target_roas
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $channel->target_roas,
                    'actual_value' => $recentRoas,
                    'deviation_percent' => $deviation * 100,
                ]);
                $alerts->push($alert);
            }
        }

        return $alerts;
    }

    public function checkLeadDropAlerts(Business $business): Collection
    {
        $alerts = collect();

        // Compare current week with previous week
        $currentWeekLeads = $this->getLeadsCount($business, now()->startOfWeek(), now());
        $previousWeekLeads = $this->getLeadsCount(
            $business,
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        );

        if ($previousWeekLeads <= 0) {
            return $alerts;
        }

        $dropRate = ($previousWeekLeads - $currentWeekLeads) / $previousWeekLeads;

        if ($dropRate >= self::LEAD_DROP_CRITICAL) {
            $alert = $this->createAlert($business, [
                'type' => MarketingAlert::TYPE_LEAD_DROP,
                'severity' => MarketingAlert::SEVERITY_CRITICAL,
                'title' => 'Lead soni keskin tushdi',
                'message' => sprintf(
                    'Bu hafta lead soni %.0f%% kamaydi (Oldingi hafta: %d, Bu hafta: %d)',
                    $dropRate * 100,
                    $previousWeekLeads,
                    $currentWeekLeads
                ),
                'threshold_value' => $previousWeekLeads,
                'actual_value' => $currentWeekLeads,
                'deviation_percent' => $dropRate * 100,
                'comparison' => [
                    'previous_week' => $previousWeekLeads,
                    'current_week' => $currentWeekLeads,
                ],
            ]);
            $alerts->push($alert);
        } elseif ($dropRate >= self::LEAD_DROP_WARNING) {
            $alert = $this->createAlert($business, [
                'type' => MarketingAlert::TYPE_LEAD_DROP,
                'severity' => MarketingAlert::SEVERITY_WARNING,
                'title' => 'Lead soni kamaymoqda',
                'message' => sprintf(
                    'Bu hafta lead soni %.0f%% kamaydi (Oldingi hafta: %d, Bu hafta: %d)',
                    $dropRate * 100,
                    $previousWeekLeads,
                    $currentWeekLeads
                ),
                'threshold_value' => $previousWeekLeads,
                'actual_value' => $currentWeekLeads,
                'deviation_percent' => $dropRate * 100,
                'comparison' => [
                    'previous_week' => $previousWeekLeads,
                    'current_week' => $currentWeekLeads,
                ],
            ]);
            $alerts->push($alert);
        }

        return $alerts;
    }

    public function checkBudgetAlerts(Business $business): Collection
    {
        $alerts = collect();

        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->whereNotNull('budget')
            ->where('budget', '>', 0)
            ->get();

        foreach ($channels as $channel) {
            $spent = $this->getMonthlySpend($channel);
            $budget = $channel->budget;
            $spentRatio = $spent / $budget;

            // Days remaining in month
            $daysInMonth = now()->daysInMonth;
            $daysPassed = now()->day;
            $expectedSpentRatio = $daysPassed / $daysInMonth;

            if ($spentRatio >= 1.0) {
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_BUDGET_EXCEEDED,
                    'severity' => MarketingAlert::SEVERITY_CRITICAL,
                    'title' => "Byudjet tugadi: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida oylik byudjet tugadi (Sarflandi: %s, Byudjet: %s)',
                        $channel->name,
                        number_format($spent, 0),
                        number_format($budget, 0)
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $budget,
                    'actual_value' => $spent,
                    'deviation_percent' => ($spentRatio - 1) * 100,
                ]);
                $alerts->push($alert);
            } elseif ($spentRatio >= self::BUDGET_CRITICAL_THRESHOLD) {
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_BUDGET_WARNING,
                    'severity' => MarketingAlert::SEVERITY_CRITICAL,
                    'title' => "Byudjet tugamoqda: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida %.0f%% byudjet sarflandi (Qoldi: %s)',
                        $channel->name,
                        $spentRatio * 100,
                        number_format($budget - $spent, 0)
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $budget,
                    'actual_value' => $spent,
                    'deviation_percent' => $spentRatio * 100,
                ]);
                $alerts->push($alert);
            } elseif ($spentRatio >= self::BUDGET_WARNING_THRESHOLD && $spentRatio > $expectedSpentRatio * 1.1) {
                // Budget usage is faster than expected
                $alert = $this->createAlert($business, [
                    'type' => MarketingAlert::TYPE_BUDGET_WARNING,
                    'severity' => MarketingAlert::SEVERITY_WARNING,
                    'title' => "Byudjet tez sarflanmoqda: {$channel->name}",
                    'message' => sprintf(
                        '%s kanalida byudjet kutilganidan tez sarflanmoqda (%.0f%% sarflandi, oyning %.0f%% o\'tdi)',
                        $channel->name,
                        $spentRatio * 100,
                        $expectedSpentRatio * 100
                    ),
                    'channel_id' => $channel->id,
                    'threshold_value' => $budget,
                    'actual_value' => $spent,
                    'deviation_percent' => $spentRatio * 100,
                ]);
                $alerts->push($alert);
            }
        }

        return $alerts;
    }

    public function checkRoiAlerts(Business $business): Collection
    {
        $alerts = collect();

        // Get current month KPI
        $kpi = MarketingUserKpi::where('business_id', $business->id)
            ->where('period_start', now()->startOfMonth())
            ->whereNull('user_id') // Business-wide KPI
            ->first();

        if (!$kpi || $kpi->total_spend <= 0) {
            return $alerts;
        }

        $roi = $kpi->getRoiAttribute();

        if ($roi < -20) {
            $alert = $this->createAlert($business, [
                'type' => MarketingAlert::TYPE_ROI_NEGATIVE,
                'severity' => MarketingAlert::SEVERITY_CRITICAL,
                'title' => 'ROI kritik darajada manfiy',
                'message' => sprintf(
                    'Oylik ROI %.1f%% (Sarflandi: %s, Daromad: %s)',
                    $roi,
                    number_format($kpi->total_spend, 0),
                    number_format($kpi->total_revenue, 0)
                ),
                'threshold_value' => 0,
                'actual_value' => $roi,
                'deviation_percent' => abs($roi),
            ]);
            $alerts->push($alert);
        } elseif ($roi < 0) {
            $alert = $this->createAlert($business, [
                'type' => MarketingAlert::TYPE_ROI_NEGATIVE,
                'severity' => MarketingAlert::SEVERITY_WARNING,
                'title' => 'ROI manfiy',
                'message' => sprintf(
                    'Oylik ROI %.1f%% (Sarflandi: %s, Daromad: %s)',
                    $roi,
                    number_format($kpi->total_spend, 0),
                    number_format($kpi->total_revenue, 0)
                ),
                'threshold_value' => 0,
                'actual_value' => $roi,
                'deviation_percent' => abs($roi),
            ]);
            $alerts->push($alert);
        }

        return $alerts;
    }

    public function createAlert(Business $business, array $data): MarketingAlert
    {
        // Check if similar alert already exists and is unresolved
        $existingAlert = MarketingAlert::where('business_id', $business->id)
            ->where('type', $data['type'])
            ->where('channel_id', $data['channel_id'] ?? null)
            ->unresolved()
            ->recent(24) // Within last 24 hours
            ->first();

        if ($existingAlert) {
            // Update existing alert with new values
            $existingAlert->update([
                'actual_value' => $data['actual_value'] ?? null,
                'deviation_percent' => $data['deviation_percent'] ?? null,
                'severity' => $data['severity'],
                'message' => $data['message'],
            ]);
            return $existingAlert;
        }

        return MarketingAlert::create([
            'business_id' => $business->id,
            'type' => $data['type'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'message' => $data['message'],
            'channel_id' => $data['channel_id'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'data' => $data['data'] ?? null,
            'comparison' => $data['comparison'] ?? null,
            'threshold_value' => $data['threshold_value'] ?? null,
            'actual_value' => $data['actual_value'] ?? null,
            'deviation_percent' => $data['deviation_percent'] ?? null,
            'status' => 'active',
        ]);
    }

    public function getActiveAlerts(Business $business, ?string $severity = null): Collection
    {
        $query = MarketingAlert::where('business_id', $business->id)
            ->active()
            ->orderBy('created_at', 'desc');

        if ($severity) {
            $query->where('severity', $severity);
        }

        return $query->get();
    }

    public function getUnresolvedAlerts(Business $business): Collection
    {
        return MarketingAlert::where('business_id', $business->id)
            ->unresolved()
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAlertsSummary(Business $business): array
    {
        $alerts = MarketingAlert::where('business_id', $business->id)
            ->unresolved()
            ->get();

        return [
            'total' => $alerts->count(),
            'critical' => $alerts->where('severity', 'critical')->count(),
            'warning' => $alerts->where('severity', 'warning')->count(),
            'info' => $alerts->where('severity', 'info')->count(),
            'by_type' => $alerts->groupBy('type')->map->count()->toArray(),
        ];
    }

    public function acknowledgeAlert(MarketingAlert $alert, ?string $userId = null): void
    {
        $alert->acknowledge($userId);
    }

    public function resolveAlert(MarketingAlert $alert, ?string $userId = null, ?string $notes = null): void
    {
        $alert->resolve($userId, $notes);
    }

    public function dismissAlert(MarketingAlert $alert): void
    {
        $alert->dismiss();
    }

    // Helper methods

    private function getRecentChannelCpl(MarketingChannel $channel): float
    {
        // Get CPL from last 7 days
        $leads = $channel->leads()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($leads <= 0) {
            return 0;
        }

        $spend = $channel->expenses()
            ->where('date', '>=', now()->subDays(7))
            ->sum('amount');

        return $spend / $leads;
    }

    private function getRecentChannelRoas(MarketingChannel $channel): float
    {
        $spend = $channel->expenses()
            ->where('date', '>=', now()->subDays(7))
            ->sum('amount');

        if ($spend <= 0) {
            return 0;
        }

        // Get revenue from leads converted in last 7 days
        $revenue = $channel->leads()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('deal_value')
            ->where('status', 'won')
            ->sum('deal_value');

        return $revenue / $spend;
    }

    private function getLeadsCount(Business $business, Carbon $from, Carbon $to): int
    {
        return $business->leads()
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function getMonthlySpend(MarketingChannel $channel): float
    {
        return $channel->expenses()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');
    }
}
