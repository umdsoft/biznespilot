<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\MarketingChannel;
use App\Models\MarketingContent;
use App\Models\Offer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        // Date range filter
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Overview Stats
        $stats = [
            'total_sales' => Sale::where('business_id', $currentBusiness->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'total_revenue' => Sale::where('business_id', $currentBusiness->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'dream_buyers' => DreamBuyer::where('business_id', $currentBusiness->id)->count(),
            'active_offers' => Offer::where('business_id', $currentBusiness->id)
                ->where('status', 'active')
                ->count(),
            'marketing_channels' => MarketingChannel::where('business_id', $currentBusiness->id)
                ->where('is_active', true)
                ->count(),
            'competitors_tracked' => Competitor::where('business_id', $currentBusiness->id)
                ->where('is_active', true)
                ->count(),
        ];

        // Sales Trend (last 7 days)
        $salesTrend = Sale::where('business_id', $currentBusiness->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Sales by Status
        $salesByStatus = Sale::where('business_id', $currentBusiness->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                ];
            });

        // Top Marketing Channels
        $topChannels = MarketingChannel::where('business_id', $currentBusiness->id)
            ->select('name', 'platform', 'monthly_budget')
            ->orderBy('monthly_budget', 'desc')
            ->take(5)
            ->get()
            ->map(function ($channel) {
                return [
                    'name' => $channel->name,
                    'platform' => $channel->platform,
                    'monthly_budget' => (float) $channel->monthly_budget,
                ];
            });

        // Marketing Content Performance
        // Note: MarketingContent model doesn't exist in this project, using empty collection
        $contentStats = collect([]);

        // Offers Performance
        $offersPerformance = Offer::where('business_id', $currentBusiness->id)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('AVG(conversion_rate) as avg_conversion'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                    'avg_conversion' => round((float) $item->avg_conversion, 2),
                ];
            });

        // Competitor Threat Analysis
        $competitorStats = Competitor::where('business_id', $currentBusiness->id)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(threat_level) as avg_threat'),
                DB::raw('SUM(CASE WHEN threat_level >= 7 THEN 1 ELSE 0 END) as high_threat'),
                DB::raw('SUM(CASE WHEN threat_level BETWEEN 4 AND 6 THEN 1 ELSE 0 END) as medium_threat'),
                DB::raw('SUM(CASE WHEN threat_level < 4 THEN 1 ELSE 0 END) as low_threat')
            )
            ->first();

        return Inertia::render('Business/Reports/Index', [
            'stats' => $stats,
            'salesTrend' => $salesTrend,
            'salesByStatus' => $salesByStatus,
            'topChannels' => $topChannels,
            'contentStats' => $contentStats,
            'offersPerformance' => $offersPerformance,
            'competitorStats' => [
                'total' => $competitorStats->total ?? 0,
                'avg_threat' => round((float) ($competitorStats->avg_threat ?? 0), 1),
                'high_threat' => $competitorStats->high_threat ?? 0,
                'medium_threat' => $competitorStats->medium_threat ?? 0,
                'low_threat' => $competitorStats->low_threat ?? 0,
            ],
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}
