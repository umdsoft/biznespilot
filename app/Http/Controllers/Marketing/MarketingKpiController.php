<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\MarketingKpiSnapshot;
use App\Models\MarketingSpend;
use App\Services\Marketing\MarketingKpiCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MarketingKpiController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected MarketingKpiCalculatorService $kpiService
    ) {}

    /**
     * Marketing KPI Dashboard
     */
    public function dashboard(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $periodType = $request->get('period', 'monthly');

        // Period sanalarini hisoblash
        $now = now();
        [$from, $to] = match ($periodType) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        // Dashboard data
        $dashboardData = $this->kpiService->getDashboardData($businessId, $from, $to);

        // Umumiy KPI kartochkalari
        $kpiCards = [
            [
                'title' => 'CPL',
                'value' => number_format($dashboardData['kpis']['cpl'], 0, ',', ' '),
                'suffix' => "so'm",
                'description' => 'Cost Per Lead',
                'trend' => $this->calculateTrend($businessId, 'cpl', $periodType),
                'color' => 'blue',
            ],
            [
                'title' => 'ROAS',
                'value' => number_format($dashboardData['kpis']['roas'], 2),
                'suffix' => 'x',
                'description' => 'Return On Ad Spend',
                'trend' => $this->calculateTrend($businessId, 'roas', $periodType),
                'color' => 'green',
            ],
            [
                'title' => 'ROI',
                'value' => number_format($dashboardData['kpis']['roi'], 1),
                'suffix' => '%',
                'description' => 'Return On Investment',
                'trend' => $this->calculateTrend($businessId, 'roi', $periodType),
                'color' => 'purple',
            ],
            [
                'title' => 'CAC',
                'value' => number_format($dashboardData['kpis']['cac'], 0, ',', ' '),
                'suffix' => "so'm",
                'description' => 'Customer Acquisition Cost',
                'trend' => $this->calculateTrend($businessId, 'cac', $periodType),
                'color' => 'orange',
            ],
        ];

        // Funnel ma'lumotlari
        $funnel = [
            ['stage' => 'Lidlar', 'count' => $dashboardData['overview']['total_leads'], 'color' => 'blue'],
            ['stage' => 'MQL', 'count' => $dashboardData['overview']['total_mql'], 'color' => 'cyan'],
            ['stage' => 'SQL', 'count' => $dashboardData['overview']['total_sql'], 'color' => 'green'],
            ['stage' => 'Won', 'count' => $dashboardData['overview']['total_won'], 'color' => 'emerald'],
        ];

        // Top kampaniyalar
        $topCampaigns = collect($dashboardData['by_campaign'])
            ->sortByDesc('leads')
            ->take(5)
            ->values()
            ->toArray();

        // Kanal samaradorligi
        $channelPerformance = collect($dashboardData['by_channel'])
            ->map(function ($channel) {
                $channel['efficiency_score'] = $this->calculateChannelEfficiency(
                    $channel['spend'],
                    $channel['leads'],
                    $channel['revenue']
                );
                return $channel;
            })
            ->sortByDesc('efficiency_score')
            ->values()
            ->toArray();

        // Period uchun snapshotlar (grafik uchun)
        $snapshots = $this->getSnapshotsForChart($businessId, $periodType);

        return Inertia::render('Marketing/KPI/Dashboard', [
            'kpiCards' => $kpiCards,
            'overview' => $dashboardData['overview'],
            'funnel' => $funnel,
            'topCampaigns' => $topCampaigns,
            'channelPerformance' => $channelPerformance,
            'snapshots' => $snapshots,
            'periodType' => $periodType,
            'panelType' => 'marketing',
        ]);
    }

    /**
     * Marketing Leaderboard - Kanallar va kampaniyalar reytingi
     */
    public function leaderboard(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $periodType = $request->get('period', 'monthly');
        $sortBy = $request->get('sort', 'roas');

        // Period sanalarini hisoblash
        $now = now();
        [$from, $to] = match ($periodType) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        // Kanal leaderboard
        $channelLeaderboard = $this->getChannelLeaderboard($businessId, $from, $to, $sortBy);

        // Kampaniya leaderboard
        $campaignLeaderboard = $this->getCampaignLeaderboard($businessId, $from, $to, $sortBy);

        // Rekordlar
        $records = $this->getMarketingRecords($businessId);

        // Oylik tarix
        $monthlyHistory = $this->getMonthlyHistory($businessId);

        return Inertia::render('Marketing/KPI/Leaderboard', [
            'channelLeaderboard' => $channelLeaderboard,
            'campaignLeaderboard' => $campaignLeaderboard,
            'records' => $records,
            'monthlyHistory' => $monthlyHistory,
            'periodType' => $periodType,
            'sortBy' => $sortBy,
            'panelType' => 'marketing',
        ]);
    }

    /**
     * KPI trend hisoblash
     */
    protected function calculateTrend(string $businessId, string $metric, string $periodType): ?array
    {
        $now = now();

        // Joriy va oldingi periodlar
        [$currentFrom, $currentTo] = match ($periodType) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        [$previousFrom, $previousTo] = match ($periodType) {
            'daily' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'weekly' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'monthly' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            default => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
        };

        // Snapshot dan olish
        $currentSnapshot = MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', $periodType)
            ->whereNull('channel_id')
            ->whereNull('campaign_id')
            ->whereBetween('date', [$currentFrom->toDateString(), $currentTo->toDateString()])
            ->orderByDesc('date')
            ->first();

        $previousSnapshot = MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', $periodType)
            ->whereNull('channel_id')
            ->whereNull('campaign_id')
            ->whereBetween('date', [$previousFrom->toDateString(), $previousTo->toDateString()])
            ->orderByDesc('date')
            ->first();

        if (!$currentSnapshot || !$previousSnapshot) {
            return null;
        }

        $currentValue = $currentSnapshot->{$metric} ?? 0;
        $previousValue = $previousSnapshot->{$metric} ?? 0;

        if ($previousValue == 0) {
            return null;
        }

        $change = (($currentValue - $previousValue) / $previousValue) * 100;

        // CPL va CAC uchun past = yaxshi
        $isPositive = in_array($metric, ['cpl', 'cac']) ? $change < 0 : $change > 0;

        return [
            'value' => round(abs($change), 1),
            'direction' => $change >= 0 ? 'up' : 'down',
            'positive' => $isPositive,
        ];
    }

    /**
     * Kanal samaradorlik balli
     */
    protected function calculateChannelEfficiency(float $spend, int $leads, float $revenue): int
    {
        if ($spend <= 0) {
            return 0;
        }

        $roi = (($revenue - $spend) / $spend) * 100;
        $cpl = $leads > 0 ? $spend / $leads : PHP_INT_MAX;

        // ROI ball (0-50)
        $roiScore = min(50, max(0, ($roi / 200) * 50));

        // CPL ball (0-50) - past = yaxshi
        $cplScore = max(0, min(50, 50 - (($cpl - 50000) / 450000 * 50)));

        return (int) round($roiScore + $cplScore);
    }

    /**
     * Grafik uchun snapshotlar
     */
    protected function getSnapshotsForChart(string $businessId, string $periodType): array
    {
        $limit = match ($periodType) {
            'daily' => 30,
            'weekly' => 12,
            'monthly' => 12,
            default => 12,
        };

        return MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', $periodType == 'monthly' ? 'daily' : $periodType)
            ->whereNull('channel_id')
            ->whereNull('campaign_id')
            ->orderByDesc('date')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn ($s) => [
                'date' => $s->date->format('d M'),
                'cpl' => $s->cpl,
                'roas' => $s->roas,
                'roi' => $s->roi,
                'leads' => $s->leads_count,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Kanal leaderboard
     */
    protected function getChannelLeaderboard(string $businessId, Carbon $from, Carbon $to, string $sortBy): array
    {
        $channels = MarketingChannel::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($channels as $channel) {
            $leads = $this->kpiService->getLeadsCount($businessId, $channel->id, null, $from, $to);
            $won = $this->kpiService->getWonCount($businessId, $channel->id, null, $from, $to);
            $spend = $this->kpiService->getTotalSpend($businessId, $channel->id, null, $from, $to);
            $revenue = $this->kpiService->getTotalRevenue($businessId, $channel->id, null, $from, $to);

            $cpl = $leads > 0 ? $spend / $leads : 0;
            $roas = $spend > 0 ? $revenue / $spend : 0;
            $roi = $spend > 0 ? (($revenue - $spend) / $spend) * 100 : 0;

            $result[] = [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'leads' => $leads,
                'won' => $won,
                'spend' => $spend,
                'revenue' => $revenue,
                'cpl' => round($cpl, 0),
                'roas' => round($roas, 2),
                'roi' => round($roi, 1),
                'efficiency_score' => $this->calculateChannelEfficiency($spend, $leads, $revenue),
            ];
        }

        // Tartiblash
        usort($result, function ($a, $b) use ($sortBy) {
            if ($sortBy === 'cpl') {
                // CPL uchun kichik = yaxshi
                if ($a['cpl'] == 0) return 1;
                if ($b['cpl'] == 0) return -1;
                return $a['cpl'] <=> $b['cpl'];
            }
            return $b[$sortBy] <=> $a[$sortBy];
        });

        // Rank qo'shish
        foreach ($result as $index => &$item) {
            $item['rank'] = $index + 1;
        }

        return $result;
    }

    /**
     * Kampaniya leaderboard
     */
    protected function getCampaignLeaderboard(string $businessId, Carbon $from, Carbon $to, string $sortBy): array
    {
        $campaigns = Campaign::where('business_id', $businessId)
            ->whereIn('status', ['active', 'completed'])
            ->get();

        $result = [];
        foreach ($campaigns as $campaign) {
            $leads = $this->kpiService->getLeadsCount($businessId, null, $campaign->id, $from, $to);
            $won = $this->kpiService->getWonCount($businessId, null, $campaign->id, $from, $to);
            $spend = $this->kpiService->getTotalSpend($businessId, null, $campaign->id, $from, $to);
            $revenue = $this->kpiService->getTotalRevenue($businessId, null, $campaign->id, $from, $to);

            $cpl = $leads > 0 ? $spend / $leads : 0;
            $roas = $spend > 0 ? $revenue / $spend : 0;
            $roi = $spend > 0 ? (($revenue - $spend) / $spend) * 100 : 0;

            $result[] = [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'leads' => $leads,
                'won' => $won,
                'spend' => $spend,
                'revenue' => $revenue,
                'cpl' => round($cpl, 0),
                'roas' => round($roas, 2),
                'roi' => round($roi, 1),
                'efficiency_score' => $this->calculateChannelEfficiency($spend, $leads, $revenue),
            ];
        }

        // Tartiblash
        usort($result, function ($a, $b) use ($sortBy) {
            if ($sortBy === 'cpl') {
                if ($a['cpl'] == 0) return 1;
                if ($b['cpl'] == 0) return -1;
                return $a['cpl'] <=> $b['cpl'];
            }
            return $b[$sortBy] <=> $a[$sortBy];
        });

        // Rank qo'shish
        foreach ($result as $index => &$item) {
            $item['rank'] = $index + 1;
        }

        return $result;
    }

    /**
     * Marketing rekordlari
     */
    protected function getMarketingRecords(string $businessId): array
    {
        // Eng yaxshi CPL
        $bestCpl = MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', 'monthly')
            ->whereNotNull('channel_id')
            ->where('cpl', '>', 0)
            ->orderBy('cpl')
            ->with('channel:id,name')
            ->first();

        // Eng yuqori ROAS
        $bestRoas = MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', 'monthly')
            ->whereNotNull('channel_id')
            ->orderByDesc('roas')
            ->with('channel:id,name')
            ->first();

        // Eng ko'p lid
        $mostLeads = MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', 'monthly')
            ->whereNotNull('channel_id')
            ->orderByDesc('leads_count')
            ->with('channel:id,name')
            ->first();

        return [
            'best_cpl' => $bestCpl ? [
                'value' => number_format($bestCpl->cpl, 0, ',', ' ') . " so'm",
                'holder_name' => $bestCpl->channel?->name ?? 'Noma\'lum',
                'date' => $bestCpl->date->format('M Y'),
            ] : null,
            'best_roas' => $bestRoas ? [
                'value' => number_format($bestRoas->roas, 2) . 'x',
                'holder_name' => $bestRoas->channel?->name ?? 'Noma\'lum',
                'date' => $bestRoas->date->format('M Y'),
            ] : null,
            'most_leads' => $mostLeads ? [
                'value' => $mostLeads->leads_count,
                'holder_name' => $mostLeads->channel?->name ?? 'Noma\'lum',
                'date' => $mostLeads->date->format('M Y'),
            ] : null,
        ];
    }

    /**
     * Oylik tarix
     */
    protected function getMonthlyHistory(string $businessId): array
    {
        return MarketingKpiSnapshot::where('business_id', $businessId)
            ->where('period_type', 'monthly')
            ->whereNull('channel_id')
            ->whereNull('campaign_id')
            ->orderByDesc('date')
            ->limit(6)
            ->get()
            ->map(fn ($s) => [
                'month' => $s->date->format('M Y'),
                'leads' => $s->leads_count,
                'won' => $s->won_count,
                'spend' => $s->total_spend,
                'revenue' => $s->total_revenue,
                'cpl' => $s->cpl,
                'roas' => $s->roas,
                'roi' => $s->roi,
            ])
            ->toArray();
    }
}
