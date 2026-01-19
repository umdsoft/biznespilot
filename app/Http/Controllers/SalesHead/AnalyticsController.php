<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $period = $request->get('period', 'month');

        $analytics = $this->getAnalyticsData($business?->id, $period);

        return Inertia::render('SalesHead/Analytics/Index', [
            'analytics' => $analytics,
            'period' => $period,
        ]);
    }

    public function conversion()
    {
        return Inertia::render('SalesHead/Analytics/Conversion');
    }

    public function revenue()
    {
        return Inertia::render('SalesHead/Analytics/Revenue');
    }

    /**
     * Export analytics data as PDF or Excel
     */
    public function export(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $period = $request->get('period', 'month');
        $format = $request->get('format', 'excel');

        $analytics = $this->getAnalyticsData($business?->id, $period);

        $periodLabels = [
            'week' => 'Haftalik',
            'month' => 'Oylik',
            'quarter' => 'Choraklik',
            'year' => 'Yillik',
        ];

        $periodLabel = $periodLabels[$period] ?? 'Oylik';
        $fileName = "Analitika_Hisobot_{$periodLabel}_" . now()->format('Y-m-d');

        if ($format === 'pdf') {
            return $this->exportPdf($analytics, $periodLabel, $fileName);
        }

        return $this->exportExcel($analytics, $periodLabel, $fileName);
    }

    /**
     * Get analytics data for period
     */
    private function getAnalyticsData($businessId, $period): array
    {
        if (!$businessId) {
            return [
                'leads_count' => 0,
                'won_count' => 0,
                'lost_count' => 0,
                'conversion_rate' => 0,
                'avg_deal_size' => 0,
                'total_revenue' => 0,
            ];
        }

        $dateRange = $this->getDateRange($period);

        $leadsCount = Lead::where('business_id', $businessId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $wonCount = Lead::where('business_id', $businessId)
            ->where('status', 'won')
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $lostCount = Lead::where('business_id', $businessId)
            ->where('status', 'lost')
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $totalRevenue = Lead::where('business_id', $businessId)
            ->where('status', 'won')
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->sum('estimated_value') ?? 0;

        $conversionRate = $leadsCount > 0 ? ($wonCount / $leadsCount) * 100 : 0;
        $avgDealSize = $wonCount > 0 ? $totalRevenue / $wonCount : 0;

        return [
            'leads_count' => $leadsCount,
            'won_count' => $wonCount,
            'lost_count' => $lostCount,
            'conversion_rate' => round($conversionRate, 1),
            'avg_deal_size' => round($avgDealSize),
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Get date range for period
     */
    private function getDateRange($period): array
    {
        $now = now();

        return match ($period) {
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Export as Excel (CSV)
     */
    private function exportExcel($analytics, $periodLabel, $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}.csv\"",
        ];

        $callback = function () use ($analytics, $periodLabel) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title
            fputcsv($file, ["Sotuv Analitikasi - {$periodLabel} Hisobot"]);
            fputcsv($file, ["Sana: " . now()->format('d.m.Y')]);
            fputcsv($file, []);

            // Headers
            fputcsv($file, ['Ko\'rsatkich', 'Qiymat']);

            // Data
            fputcsv($file, ['Jami lidlar', $analytics['leads_count']]);
            fputcsv($file, ['Yutilgan bitimlar', $analytics['won_count']]);
            fputcsv($file, ['Yo\'qotilgan', $analytics['lost_count']]);
            fputcsv($file, ['Konversiya (%)', $analytics['conversion_rate'] . '%']);
            fputcsv($file, ['O\'rtacha bitim (so\'m)', number_format($analytics['avg_deal_size'], 0, '.', ' ')]);
            fputcsv($file, ['Jami daromad (so\'m)', number_format($analytics['total_revenue'], 0, '.', ' ')]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export as PDF (HTML-based)
     */
    private function exportPdf($analytics, $periodLabel, $fileName)
    {
        $html = $this->generatePdfHtml($analytics, $periodLabel);

        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}.html\"",
        ];

        return Response::make($html, 200, $headers);
    }

    /**
     * Generate HTML for PDF
     */
    private function generatePdfHtml($analytics, $periodLabel): string
    {
        $date = now()->format('d.m.Y');
        $avgDeal = number_format($analytics['avg_deal_size'], 0, '.', ' ');
        $totalRevenue = number_format($analytics['total_revenue'], 0, '.', ' ');

        return <<<HTML
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Sotuv Analitikasi - {$periodLabel}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #fff; }
        h1 { color: #059669; border-bottom: 2px solid #059669; padding-bottom: 10px; }
        .date { color: #666; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f3f4f6; font-weight: bold; }
        .value { font-weight: bold; color: #059669; }
        .footer { margin-top: 40px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Sotuv Analitikasi - {$periodLabel} Hisobot</h1>
    <p class="date">Hisobot sanasi: {$date}</p>

    <table>
        <tr>
            <th>Ko'rsatkich</th>
            <th>Qiymat</th>
        </tr>
        <tr>
            <td>Jami lidlar</td>
            <td class="value">{$analytics['leads_count']}</td>
        </tr>
        <tr>
            <td>Yutilgan bitimlar</td>
            <td class="value">{$analytics['won_count']}</td>
        </tr>
        <tr>
            <td>Yo'qotilgan</td>
            <td class="value">{$analytics['lost_count']}</td>
        </tr>
        <tr>
            <td>Konversiya</td>
            <td class="value">{$analytics['conversion_rate']}%</td>
        </tr>
        <tr>
            <td>O'rtacha bitim</td>
            <td class="value">{$avgDeal} so'm</td>
        </tr>
        <tr>
            <td>Jami daromad</td>
            <td class="value">{$totalRevenue} so'm</td>
        </tr>
    </table>

    <div class="footer">
        <p>BiznesPilot - Sotuv Bo'limi Analitikasi</p>
    </div>
</body>
</html>
HTML;
    }
}
