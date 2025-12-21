<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Models\DashboardWidget;
use App\Models\KpiDailySnapshot;
use App\Models\Alert;
use App\Models\AiInsight;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getDashboardData(Business $business): array
    {
        $today = Carbon::today();
        $todaySnapshot = $this->getTodaySnapshot($business);
        $yesterdaySnapshot = $this->getSnapshot($business, $today->copy()->subDay());
        $weekAgoSnapshot = $this->getSnapshot($business, $today->copy()->subWeek());

        return [
            'kpis' => $this->getKPISummary($business, $todaySnapshot, $yesterdaySnapshot, $weekAgoSnapshot),
            'health_score' => $todaySnapshot?->health_score ?? 0,
            'funnel' => $this->getFunnelData($business, $todaySnapshot),
            'alerts' => $this->getActiveAlerts($business),
            'insights' => $this->getActiveInsights($business),
            'trends' => $this->getWeeklyTrends($business),
            'last_updated' => now()->toISOString(),
        ];
    }

    public function getWidgets(Business $business, User $user): Collection
    {
        $widgets = DashboardWidget::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->visible()
            ->ordered()
            ->get();

        if ($widgets->isEmpty()) {
            $widgets = $this->createDefaultWidgets($business, $user);
        }

        return $widgets;
    }

    public function createDefaultWidgets(Business $business, User $user): Collection
    {
        $defaults = DashboardWidget::getDefaultWidgets();
        $widgets = collect();

        foreach ($defaults as $index => $config) {
            $widget = DashboardWidget::create(array_merge($config, [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'sort_order' => $index,
            ]));
            $widgets->push($widget);
        }

        return $widgets;
    }

    public function getKPISummary(Business $business, ?KpiDailySnapshot $today, ?KpiDailySnapshot $yesterday, ?KpiDailySnapshot $weekAgo): array
    {
        $kpis = [
            'revenue' => [
                'label' => 'Daromad',
                'value' => $today?->revenue_total ?? 0,
                'formatted' => $this->formatMoney($today?->revenue_total ?? 0),
                'change_day' => $this->calculateChange($today?->revenue_total, $yesterday?->revenue_total),
                'change_week' => $this->calculateChange($today?->revenue_total, $weekAgo?->revenue_total),
                'icon' => 'currency-dollar',
                'color' => 'green',
            ],
            'leads' => [
                'label' => 'Lidlar',
                'value' => $today?->leads_total ?? 0,
                'formatted' => number_format($today?->leads_total ?? 0),
                'change_day' => $this->calculateChange($today?->leads_total, $yesterday?->leads_total),
                'change_week' => $this->calculateChange($today?->leads_total, $weekAgo?->leads_total),
                'icon' => 'user-group',
                'color' => 'blue',
            ],
            'cac' => [
                'label' => 'CAC',
                'value' => $today?->cac ?? 0,
                'formatted' => $this->formatMoney($today?->cac ?? 0),
                'change_day' => $this->calculateChange($today?->cac, $yesterday?->cac, true),
                'change_week' => $this->calculateChange($today?->cac, $weekAgo?->cac, true),
                'icon' => 'banknotes',
                'color' => 'purple',
            ],
            'roas' => [
                'label' => 'ROAS',
                'value' => $today?->ad_roas ?? 0,
                'formatted' => ($today?->ad_roas ?? 0) . 'x',
                'change_day' => $this->calculateChange($today?->ad_roas, $yesterday?->ad_roas),
                'change_week' => $this->calculateChange($today?->ad_roas, $weekAgo?->ad_roas),
                'icon' => 'chart-bar',
                'color' => 'amber',
            ],
            'engagement' => [
                'label' => 'Engagement',
                'value' => $today?->engagement_rate ?? 0,
                'formatted' => ($today?->engagement_rate ?? 0) . '%',
                'change_day' => $this->calculateChange($today?->engagement_rate, $yesterday?->engagement_rate),
                'change_week' => $this->calculateChange($today?->engagement_rate, $weekAgo?->engagement_rate),
                'icon' => 'heart',
                'color' => 'pink',
            ],
            'conversion' => [
                'label' => 'Konversiya',
                'value' => $today?->conversion_rate ?? 0,
                'formatted' => ($today?->conversion_rate ?? 0) . '%',
                'change_day' => $this->calculateChange($today?->conversion_rate, $yesterday?->conversion_rate),
                'change_week' => $this->calculateChange($today?->conversion_rate, $weekAgo?->conversion_rate),
                'icon' => 'arrow-trending-up',
                'color' => 'indigo',
            ],
        ];

        return $kpis;
    }

    public function getTrendData(Business $business, string $metric, int $days = 30): array
    {
        $snapshots = KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date')
            ->get();

        $data = [];
        foreach ($snapshots as $snapshot) {
            $data[] = [
                'date' => $snapshot->snapshot_date->format('Y-m-d'),
                'value' => $snapshot->{$metric} ?? 0,
            ];
        }

        return $data;
    }

    public function getFunnelData(Business $business, ?KpiDailySnapshot $snapshot = null): array
    {
        if (!$snapshot) {
            $snapshot = $this->getTodaySnapshot($business);
        }

        return [
            ['stage' => 'Awareness', 'label' => 'Xabardorlik', 'value' => $snapshot?->funnel_awareness ?? 0],
            ['stage' => 'Interest', 'label' => 'Qiziqish', 'value' => $snapshot?->funnel_interest ?? 0],
            ['stage' => 'Consideration', 'label' => 'O\'ylash', 'value' => $snapshot?->funnel_consideration ?? 0],
            ['stage' => 'Intent', 'label' => 'Niyat', 'value' => $snapshot?->funnel_intent ?? 0],
            ['stage' => 'Purchase', 'label' => 'Sotib olish', 'value' => $snapshot?->funnel_purchase ?? 0],
        ];
    }

    public function getChannelComparison(Business $business): array
    {
        // This would aggregate data from different channels
        // For now, return mock structure
        return [
            'instagram' => ['leads' => 0, 'engagement' => 0, 'reach' => 0],
            'telegram' => ['leads' => 0, 'engagement' => 0, 'reach' => 0],
            'facebook' => ['leads' => 0, 'engagement' => 0, 'reach' => 0],
        ];
    }

    public function getTodaySnapshot(Business $business): ?KpiDailySnapshot
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', Carbon::today())
            ->first();
    }

    public function getSnapshot(Business $business, Carbon $date): ?KpiDailySnapshot
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', $date->format('Y-m-d'))
            ->first();
    }

    protected function getActiveAlerts(Business $business): Collection
    {
        return Alert::where('business_id', $business->id)
            ->active()
            ->unresolved()
            ->notSnoozed()
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low', 'info')")
            ->limit(5)
            ->get();
    }

    protected function getActiveInsights(Business $business): Collection
    {
        return AiInsight::where('business_id', $business->id)
            ->active()
            ->notExpired()
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->limit(5)
            ->get();
    }

    protected function getWeeklyTrends(Business $business): array
    {
        return [
            'revenue' => $this->getTrendData($business, 'revenue_total', 7),
            'leads' => $this->getTrendData($business, 'leads_total', 7),
        ];
    }

    protected function calculateChange($current, $previous, bool $inverse = false): ?array
    {
        if (!$previous || $previous == 0) {
            return null;
        }

        $change = (($current - $previous) / $previous) * 100;
        $isPositive = $inverse ? $change < 0 : $change > 0;

        return [
            'value' => round(abs($change), 1),
            'direction' => $change >= 0 ? 'up' : 'down',
            'is_positive' => $isPositive,
        ];
    }

    protected function formatMoney($value): string
    {
        if ($value >= 1000000000) {
            return round($value / 1000000000, 1) . 'B';
        }
        if ($value >= 1000000) {
            return round($value / 1000000, 1) . 'M';
        }
        if ($value >= 1000) {
            return round($value / 1000, 1) . 'K';
        }
        return number_format($value);
    }

    public function refreshDashboard(Business $business): void
    {
        // Clear any cached dashboard data
        // Trigger re-calculation of KPIs if needed
    }
}
