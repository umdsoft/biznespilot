<?php

namespace App\Services\KPI;

use App\Models\Business;
use App\Models\KpiDailyEntry;
use App\Models\KpiDailySourceDetail;
use App\Models\LeadSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiSourceAnalyzer
{
    /**
     * Analyze lead sources for a period
     */
    public function analyzeSourcesForPeriod(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        // Get all daily entries for the period
        $entryIds = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('id');

        // Get aggregated source details
        $sourceStats = KpiDailySourceDetail::whereIn('daily_entry_id', $entryIds)
            ->select(
                'lead_source_id',
                DB::raw('SUM(leads_count) as total_leads'),
                DB::raw('SUM(spend_amount) as total_spend'),
                DB::raw('SUM(conversions) as total_conversions'),
                DB::raw('SUM(revenue) as total_revenue')
            )
            ->groupBy('lead_source_id')
            ->get();

        // Get lead sources
        $leadSources = LeadSource::whereIn('id', $sourceStats->pluck('lead_source_id'))
            ->get()
            ->keyBy('id');

        // Build analysis
        $analysis = [];
        $totals = [
            'leads' => 0,
            'spend' => 0,
            'conversions' => 0,
            'revenue' => 0,
        ];

        foreach ($sourceStats as $stat) {
            $source = $leadSources->get($stat->lead_source_id);
            if (! $source) {
                continue;
            }

            $conversionRate = $stat->total_leads > 0
                ? round(($stat->total_conversions / $stat->total_leads) * 100, 1)
                : 0;

            $cpl = $stat->total_leads > 0 && $stat->total_spend > 0
                ? round($stat->total_spend / $stat->total_leads, 0)
                : 0;

            $roi = $stat->total_spend > 0
                ? round((($stat->total_revenue - $stat->total_spend) / $stat->total_spend) * 100, 1)
                : ($stat->total_revenue > 0 ? 999 : 0); // Infinite ROI if no spend but has revenue

            $analysis[] = [
                'source_id' => $source->id,
                'source_code' => $source->code,
                'source_name' => $source->name,
                'category' => $source->category,
                'icon' => $source->icon,
                'color' => $source->color,
                'leads' => (int) $stat->total_leads,
                'spend' => (float) $stat->total_spend,
                'conversions' => (int) $stat->total_conversions,
                'revenue' => (float) $stat->total_revenue,
                'conversion_rate' => $conversionRate,
                'cpl' => $cpl,
                'roi' => $roi,
            ];

            $totals['leads'] += $stat->total_leads;
            $totals['spend'] += $stat->total_spend;
            $totals['conversions'] += $stat->total_conversions;
            $totals['revenue'] += $stat->total_revenue;
        }

        // Calculate percentages
        foreach ($analysis as &$item) {
            $item['leads_percent'] = $totals['leads'] > 0
                ? round(($item['leads'] / $totals['leads']) * 100, 1)
                : 0;
            $item['revenue_percent'] = $totals['revenue'] > 0
                ? round(($item['revenue'] / $totals['revenue']) * 100, 1)
                : 0;
        }

        // Sort by leads descending
        usort($analysis, fn ($a, $b) => $b['leads'] <=> $a['leads']);

        // Group by category
        $byCategory = collect($analysis)->groupBy('category');

        // Find best performers
        $bestConversion = collect($analysis)->where('leads', '>=', 5)->sortByDesc('conversion_rate')->first();
        $bestRoi = collect($analysis)->where('spend', '>', 0)->sortByDesc('roi')->first();
        $lowestCpl = collect($analysis)->where('spend', '>', 0)->sortBy('cpl')->first();

        return [
            'sources' => $analysis,
            'by_category' => $byCategory->toArray(),
            'totals' => $totals,
            'insights' => [
                'best_conversion' => $bestConversion,
                'best_roi' => $bestRoi,
                'lowest_cpl' => $lowestCpl,
            ],
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Analyze current month sources
     */
    public function analyzeCurrentMonth(Business $business): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();

        return $this->analyzeSourcesForPeriod($business, $startOfMonth, $today);
    }

    /**
     * Get category summary
     * Now also aggregates from kpi_daily_entries if source_details are empty
     */
    public function getCategorySummary(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        // First try to get from source details
        $analysis = $this->analyzeSourcesForPeriod($business, $startDate, $endDate);

        $categorySummary = [
            'digital' => ['leads' => 0, 'spend' => 0, 'conversions' => 0, 'revenue' => 0],
            'offline' => ['leads' => 0, 'spend' => 0, 'conversions' => 0, 'revenue' => 0],
            'referral' => ['leads' => 0, 'spend' => 0, 'conversions' => 0, 'revenue' => 0],
            'organic' => ['leads' => 0, 'spend' => 0, 'conversions' => 0, 'revenue' => 0],
        ];

        // If we have source details, use them
        if (! empty($analysis['sources'])) {
            foreach ($analysis['sources'] as $source) {
                $cat = $source['category'];
                if (isset($categorySummary[$cat])) {
                    $categorySummary[$cat]['leads'] += $source['leads'];
                    $categorySummary[$cat]['spend'] += $source['spend'];
                    $categorySummary[$cat]['conversions'] += $source['conversions'];
                    $categorySummary[$cat]['revenue'] += $source['revenue'];
                }
            }
        } else {
            // Fallback: aggregate directly from kpi_daily_entries
            $entries = KpiDailyEntry::where('business_id', $business->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $totalSales = $entries->sum('sales_total');
            $totalLeads = $entries->sum('leads_total');

            $categorySummary['digital'] = [
                'leads' => (int) $entries->sum('leads_digital'),
                'spend' => (float) $entries->sum('spend_digital'),
                'conversions' => 0, // Can't calculate per-category conversions
                'revenue' => 0,
            ];

            $categorySummary['offline'] = [
                'leads' => (int) $entries->sum('leads_offline'),
                'spend' => (float) $entries->sum('spend_offline'),
                'conversions' => 0,
                'revenue' => 0,
            ];

            $categorySummary['referral'] = [
                'leads' => (int) $entries->sum('leads_referral'),
                'spend' => 0,
                'conversions' => 0,
                'revenue' => 0,
            ];

            $categorySummary['organic'] = [
                'leads' => (int) $entries->sum('leads_organic'),
                'spend' => 0,
                'conversions' => 0,
                'revenue' => 0,
            ];
        }

        // Calculate metrics for each category
        foreach ($categorySummary as $cat => &$data) {
            $data['conversion_rate'] = $data['leads'] > 0
                ? round(($data['conversions'] / $data['leads']) * 100, 1)
                : 0;
            $data['cpl'] = $data['leads'] > 0 && $data['spend'] > 0
                ? round($data['spend'] / $data['leads'], 0)
                : 0;
            $data['roi'] = $data['spend'] > 0
                ? round((($data['revenue'] - $data['spend']) / $data['spend']) * 100, 1)
                : 0;
        }

        return $categorySummary;
    }

    /**
     * Generate recommendations based on analysis
     */
    public function generateRecommendations(array $analysis): array
    {
        $recommendations = [];

        // Check for high-performing sources
        if ($analysis['insights']['best_conversion']) {
            $source = $analysis['insights']['best_conversion'];
            if ($source['conversion_rate'] > 30) {
                $recommendations[] = [
                    'type' => 'positive',
                    'icon' => 'trending-up',
                    'title' => $source['source_name'].' juda samarali',
                    'message' => $source['conversion_rate'].'% konversiya bilan eng yaxshi natija. Bu kanalni kengaytirish tavsiya etiladi.',
                ];
            }
        }

        // Check for high CPL sources
        foreach ($analysis['sources'] as $source) {
            if ($source['cpl'] > 20000 && $source['leads'] >= 10) {
                $recommendations[] = [
                    'type' => 'warning',
                    'icon' => 'alert-triangle',
                    'title' => $source['source_name'].' da CPL yuqori',
                    'message' => number_format($source['cpl'], 0, ',', ' ')." so'm/lid - targetingni qayta ko'rib chiqing.",
                ];
            }
        }

        // Check for underutilized organic sources
        $organicSources = collect($analysis['sources'])->where('category', 'organic');
        $totalLeads = $analysis['totals']['leads'];

        if ($totalLeads > 0) {
            $organicPercent = $organicSources->sum('leads') / $totalLeads * 100;
            if ($organicPercent < 10) {
                $recommendations[] = [
                    'type' => 'info',
                    'icon' => 'lightbulb',
                    'title' => 'Organik trafikni oshiring',
                    'message' => 'Organik manbalar faqat '.round($organicPercent, 1).'% tashkil qiladi. SEO va content marketingga e\'tibor bering.',
                ];
            }
        }

        // Check for referral potential
        $referralSources = collect($analysis['sources'])->where('category', 'referral');
        if ($referralSources->isNotEmpty()) {
            $avgReferralConversion = $referralSources->avg('conversion_rate');
            if ($avgReferralConversion > 40) {
                $recommendations[] = [
                    'type' => 'positive',
                    'icon' => 'users',
                    'title' => 'Referral dasturini kuchaytiring',
                    'message' => 'Tavsiya orqali lidlar '.round($avgReferralConversion, 1).'% konversiya bermoqda - eng yuqori!',
                ];
            }
        }

        return $recommendations;
    }
}
