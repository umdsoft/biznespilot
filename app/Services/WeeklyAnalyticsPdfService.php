<?php

namespace App\Services;

use App\Models\WeeklyAnalytics;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class WeeklyAnalyticsPdfService
{
    /**
     * Generate PDF for weekly analytics report
     */
    public function generatePdf(WeeklyAnalytics $analytics): string
    {
        $data = $this->prepareData($analytics);

        $pdf = Pdf::loadView('pdf.weekly-analytics', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = $this->getFilename($analytics);
        $path = 'analytics/pdf/' . $filename;

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Download PDF directly (stream response)
     */
    public function downloadPdf(WeeklyAnalytics $analytics)
    {
        $data = $this->prepareData($analytics);
        $filename = $this->getFilename($analytics);

        $pdf = Pdf::loadView('pdf.weekly-analytics', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Stream PDF in browser
     */
    public function streamPdf(WeeklyAnalytics $analytics)
    {
        $data = $this->prepareData($analytics);
        $filename = $this->getFilename($analytics);

        $pdf = Pdf::loadView('pdf.weekly-analytics', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    /**
     * Prepare data for PDF template
     */
    protected function prepareData(WeeklyAnalytics $analytics): array
    {
        $business = $analytics->business;
        $summary = $analytics->summary_stats ?? [];
        $vsLastWeek = $summary['vs_last_week'] ?? [];

        return [
            'analytics' => $analytics,
            'business' => $business,
            'summary' => [
                'total_leads' => $summary['total_leads'] ?? 0,
                'won' => $summary['won'] ?? 0,
                'lost' => $summary['lost'] ?? 0,
                'in_progress' => $summary['in_progress'] ?? 0,
                'conversion_rate' => $summary['conversion_rate'] ?? 0,
                'total_revenue' => $summary['total_revenue'] ?? 0,
                'lost_revenue' => $summary['lost_revenue'] ?? 0,
                'pipeline_value' => $summary['pipeline_value'] ?? 0,
                'avg_deal_value' => $summary['avg_deal_value'] ?? 0,
                'win_loss_ratio' => $summary['win_loss_ratio'] ?? 0,
                'hot_leads' => $summary['hot_leads'] ?? 0,
            ],
            'vs_last_week' => $vsLastWeek,
            'channels' => $analytics->channel_stats ?? [],
            'operators' => $analytics->operator_stats ?? [],
            'time_stats' => $analytics->time_stats ?? [],
            'lost_reasons' => $analytics->lost_reason_stats ?? [],
            'trends' => $analytics->trend_data ?? [],
            'regional' => $analytics->regional_stats ?? [],
            'qualification' => $analytics->qualification_stats ?? [],
            'calls' => $analytics->call_stats ?? [],
            'tasks' => $analytics->task_stats ?? [],
            'pipeline' => $analytics->pipeline_stats ?? [],
            'ai' => [
                'has_analysis' => $analytics->hasAiAnalysis(),
                'overall_assessment' => $analytics->ai_overall_assessment,
                'good_results' => $analytics->ai_good_results ?? [],
                'problems' => $analytics->ai_problems ?? [],
                'recommendations' => $analytics->ai_recommendations ?? [],
                'next_week_goal' => $analytics->ai_next_week_goal,
                'score' => $analytics->ai_score,
                'emoji' => $analytics->ai_emoji,
            ],
            'generated_at' => now()->format('d.m.Y H:i'),
        ];
    }

    /**
     * Generate filename for PDF
     */
    protected function getFilename(WeeklyAnalytics $analytics): string
    {
        $businessName = str_replace([' ', '/', '\\'], '_', $analytics->business->name);
        $weekStart = $analytics->week_start->format('Y-m-d');

        return "haftalik_hisobot_{$businessName}_{$weekStart}.pdf";
    }

    /**
     * Format money for display
     */
    public static function formatMoney($amount): string
    {
        if ($amount >= 1000000000) {
            return round($amount / 1000000000, 1) . ' mlrd so\'m';
        }
        if ($amount >= 1000000) {
            return round($amount / 1000000, 1) . ' mln so\'m';
        }
        if ($amount >= 1000) {
            return round($amount / 1000, 1) . 'k so\'m';
        }

        return number_format($amount, 0, '.', ' ') . ' so\'m';
    }

    /**
     * Format percentage change
     */
    public static function formatChange($value): string
    {
        if ($value > 0) {
            return "+{$value}%";
        }

        return "{$value}%";
    }

    /**
     * Get change class for styling
     */
    public static function getChangeClass($value): string
    {
        if ($value > 0) {
            return 'positive';
        }
        if ($value < 0) {
            return 'negative';
        }

        return 'neutral';
    }
}
