<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\Offer;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetricsCalculatorService
{
    public function calculate(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'sales' => $this->calculateSalesMetrics($business, $startDate, $endDate),
            'marketing' => $this->calculateMarketingMetrics($business, $startDate, $endDate),
            'leads' => $this->calculateLeadMetrics($business, $startDate, $endDate),
            'overview' => $this->calculateOverviewMetrics($business),
        ];
    }

    protected function calculateSalesMetrics(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $sales = Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_count' => $sales->count(),
            'total_revenue' => (float) $sales->sum('amount'),
            'average_order_value' => (float) $sales->avg('amount') ?: 0,
            'total_profit' => (float) $sales->sum('profit') ?: 0,
        ];
    }

    protected function calculateMarketingMetrics(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true);

        return [
            'active_channels' => $channels->count(),
            'total_budget' => (float) $channels->sum('monthly_budget'),
        ];
    }

    protected function calculateLeadMetrics(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalLeads = $leads->count();
        $convertedLeads = (clone $leads)->where('status', 'won')->count();

        return [
            'total_leads' => $totalLeads,
            'converted_leads' => $convertedLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
            'new_leads' => (clone $leads)->where('status', 'new')->count(),
        ];
    }

    protected function calculateOverviewMetrics(Business $business): array
    {
        return [
            'dream_buyers' => DreamBuyer::where('business_id', $business->id)->count(),
            'competitors' => Competitor::where('business_id', $business->id)->where('is_active', true)->count(),
            'active_offers' => Offer::where('business_id', $business->id)->where('status', 'active')->count(),
        ];
    }
}
