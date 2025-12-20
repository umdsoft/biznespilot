<?php

namespace App\Services;

use App\Models\Business;
use Carbon\Carbon;

class ExportService
{
    /**
     * Generate Excel file from analytics data
     *
     * @param Business $business
     * @param array $data
     * @param string $reportType
     * @return string File path
     */
    public function generateExcel(Business $business, array $data, string $reportType = 'full'): string
    {
        // Create CSV (can be upgraded to PhpSpreadsheet for advanced Excel features)
        $filename = $this->generateFilename($business, 'xlsx', $reportType);
        $filepath = storage_path('app/exports/' . $filename);

        // Ensure exports directory exists
        if (!is_dir(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $handle = fopen($filepath, 'w');

        // Write header
        $this->writeExcelHeader($handle, $business, $data);

        // Write content based on report type
        switch ($reportType) {
            case 'funnel':
                $this->writeExcelFunnel($handle, $data);
                break;
            case 'performance':
                $this->writeExcelPerformance($handle, $data);
                break;
            case 'revenue':
                $this->writeExcelRevenue($handle, $data);
                break;
            default:
                $this->writeExcelFull($handle, $data);
                break;
        }

        fclose($handle);

        return $filename;
    }

    /**
     * Generate PDF file from analytics data
     *
     * @param Business $business
     * @param array $data
     * @param string $reportType
     * @return string File path
     */
    public function generatePDF(Business $business, array $data, string $reportType = 'full'): string
    {
        $filename = $this->generateFilename($business, 'pdf', $reportType);
        $filepath = storage_path('app/exports/' . $filename);

        // Ensure exports directory exists
        if (!is_dir(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        // Generate HTML content
        $html = $this->generatePDFHtml($business, $data, $reportType);

        // For now, save as HTML (can be upgraded to use Dompdf or similar)
        file_put_contents($filepath, $html);

        return $filename;
    }

    /**
     * Generate filename for export
     *
     * @param Business $business
     * @param string $extension
     * @param string $reportType
     * @return string
     */
    protected function generateFilename(Business $business, string $extension, string $reportType): string
    {
        $businessSlug = str_replace(' ', '_', strtolower($business->name));
        $timestamp = Carbon::now()->format('Y-m-d_His');
        return "{$businessSlug}_analytics_{$reportType}_{$timestamp}.{$extension}";
    }

    /**
     * Write Excel header
     *
     * @param resource $handle
     * @param Business $business
     * @param array $data
     */
    protected function writeExcelHeader($handle, Business $business, array $data): void
    {
        fputcsv($handle, ['SALES ANALYTICS REPORT']);
        fputcsv($handle, ['Business:', $business->name]);
        fputcsv($handle, ['Generated:', Carbon::now()->format('d.m.Y H:i')]);

        if (!empty($data['filters']['date_from']) || !empty($data['filters']['date_to'])) {
            $dateRange = sprintf(
                'From %s to %s',
                $data['filters']['date_from'] ?? 'Start',
                $data['filters']['date_to'] ?? 'Now'
            );
            fputcsv($handle, ['Period:', $dateRange]);
        }

        fputcsv($handle, []); // Empty line
    }

    /**
     * Write full analytics report to Excel
     *
     * @param resource $handle
     * @param array $data
     */
    protected function writeExcelFull($handle, array $data): void
    {
        // Dashboard Metrics
        fputcsv($handle, ['DASHBOARD METRICS']);
        fputcsv($handle, ['Metric', 'Value']);
        fputcsv($handle, ['Total Leads', $data['metrics']['total_leads'] ?? 0]);
        fputcsv($handle, ['New Leads', $data['metrics']['new_leads'] ?? 0]);
        fputcsv($handle, ['Won Deals', $data['metrics']['won_deals'] ?? 0]);
        fputcsv($handle, ['Total Revenue', $this->formatPrice($data['metrics']['total_revenue'] ?? 0)]);
        fputcsv($handle, ['Pipeline Value', $this->formatPrice($data['metrics']['pipeline_value'] ?? 0)]);
        fputcsv($handle, ['Conversion Rate', ($data['metrics']['conversion_rate'] ?? 0) . '%']);
        fputcsv($handle, ['Average Deal Size', $this->formatPrice($data['metrics']['avg_deal_size'] ?? 0)]);
        fputcsv($handle, ['Revenue Growth', ($data['metrics']['revenue_growth'] ?? 0) . '%']);
        fputcsv($handle, []);

        // Funnel Data
        if (!empty($data['funnel']['funnel_stages'])) {
            fputcsv($handle, ['CONVERSION FUNNEL']);
            fputcsv($handle, ['Stage', 'Count', 'Percentage', 'Conversion Rate', 'Dropoff Rate']);

            foreach ($data['funnel']['funnel_stages'] as $stage) {
                fputcsv($handle, [
                    $stage['label'],
                    $stage['count'],
                    $stage['percentage'] . '%',
                    $stage['conversion_rate'] . '%',
                    $stage['dropoff_rate'] . '%',
                ]);
            }
            fputcsv($handle, []);
        }

        // Dream Buyer Performance
        if (!empty($data['dream_buyer_performance'])) {
            fputcsv($handle, ['DREAM BUYER PERFORMANCE']);
            fputcsv($handle, ['Dream Buyer', 'Total Leads', 'Won Deals', 'Conversion Rate', 'Total Revenue', 'Avg Deal Size']);

            foreach ($data['dream_buyer_performance'] as $buyer) {
                fputcsv($handle, [
                    $buyer['dream_buyer_name'],
                    $buyer['total_leads'],
                    $buyer['won_leads'],
                    $buyer['conversion_rate'] . '%',
                    $this->formatPrice($buyer['total_revenue']),
                    $this->formatPrice($buyer['avg_deal_size']),
                ]);
            }
            fputcsv($handle, []);
        }

        // Offer Performance
        if (!empty($data['offer_performance'])) {
            fputcsv($handle, ['OFFER PERFORMANCE']);
            fputcsv($handle, ['Offer', 'Value Score', 'Total Leads', 'Won Deals', 'Conversion Rate', 'Total Revenue', 'ROI']);

            foreach ($data['offer_performance'] as $offer) {
                fputcsv($handle, [
                    $offer['offer_name'],
                    $offer['value_score'] ?? 'N/A',
                    $offer['total_leads'],
                    $offer['won_leads'],
                    $offer['conversion_rate'] . '%',
                    $this->formatPrice($offer['total_revenue']),
                    $offer['roi'] . '%',
                ]);
            }
            fputcsv($handle, []);
        }

        // Source Analysis
        if (!empty($data['source_analysis'])) {
            fputcsv($handle, ['LEAD SOURCE ANALYSIS']);
            fputcsv($handle, ['Source', 'Type', 'Total Leads', 'Won Deals', 'Conversion Rate', 'Total Revenue', 'Cost per Lead', 'ROI', 'ROAS']);

            foreach ($data['source_analysis'] as $source) {
                fputcsv($handle, [
                    $source['source_name'],
                    $source['channel_type'],
                    $source['total_leads'],
                    $source['won_leads'],
                    $source['conversion_rate'] . '%',
                    $this->formatPrice($source['total_revenue']),
                    $this->formatPrice($source['cost_per_lead']),
                    $source['roi'] . '%',
                    $source['roas'] . 'x',
                ]);
            }
        }
    }

    /**
     * Write funnel report to Excel
     *
     * @param resource $handle
     * @param array $data
     */
    protected function writeExcelFunnel($handle, array $data): void
    {
        fputcsv($handle, ['CONVERSION FUNNEL ANALYSIS']);
        fputcsv($handle, []);

        // Summary
        fputcsv($handle, ['SUMMARY']);
        fputcsv($handle, ['Total Leads', $data['funnel']['summary']['total_leads'] ?? 0]);
        fputcsv($handle, ['Won Leads', $data['funnel']['summary']['won_leads'] ?? 0]);
        fputcsv($handle, ['Active Leads', $data['funnel']['summary']['active_leads'] ?? 0]);
        fputcsv($handle, ['Overall Conversion Rate', ($data['funnel']['summary']['overall_conversion_rate'] ?? 0) . '%']);
        fputcsv($handle, ['Win Rate', ($data['funnel']['summary']['win_rate'] ?? 0) . '%']);
        fputcsv($handle, []);

        // Funnel Stages
        if (!empty($data['funnel']['funnel_stages'])) {
            fputcsv($handle, ['FUNNEL STAGES']);
            fputcsv($handle, ['Stage', 'Label', 'Count', 'Percentage', 'Conversion Rate', 'Dropoff Rate']);

            foreach ($data['funnel']['funnel_stages'] as $stage) {
                fputcsv($handle, [
                    $stage['stage'],
                    $stage['label'],
                    $stage['count'],
                    $stage['percentage'] . '%',
                    $stage['conversion_rate'] . '%',
                    $stage['dropoff_rate'] . '%',
                ]);
            }
        }
    }

    /**
     * Write performance report to Excel
     *
     * @param resource $handle
     * @param array $data
     */
    protected function writeExcelPerformance($handle, array $data): void
    {
        // Dream Buyer Performance
        if (!empty($data['dream_buyer_performance'])) {
            fputcsv($handle, ['DREAM BUYER PERFORMANCE']);
            fputcsv($handle, ['Dream Buyer', 'Total Leads', 'Won Deals', 'Conversion Rate', 'Total Revenue', 'Avg Deal Size', 'Lifetime Value']);

            foreach ($data['dream_buyer_performance'] as $buyer) {
                fputcsv($handle, [
                    $buyer['dream_buyer_name'],
                    $buyer['total_leads'],
                    $buyer['won_leads'],
                    $buyer['conversion_rate'] . '%',
                    $this->formatPrice($buyer['total_revenue']),
                    $this->formatPrice($buyer['avg_deal_size']),
                    $this->formatPrice($buyer['lifetime_value']),
                ]);
            }
            fputcsv($handle, []);
        }

        // Offer Performance
        if (!empty($data['offer_performance'])) {
            fputcsv($handle, ['OFFER PERFORMANCE']);
            fputcsv($handle, ['Offer', 'Value Score', 'Total Leads', 'Won Deals', 'Conversion Rate', 'Total Revenue', 'Avg Deal Size', 'ROI']);

            foreach ($data['offer_performance'] as $offer) {
                fputcsv($handle, [
                    $offer['offer_name'],
                    $offer['value_score'] ?? 'N/A',
                    $offer['total_leads'],
                    $offer['won_leads'],
                    $offer['conversion_rate'] . '%',
                    $this->formatPrice($offer['total_revenue']),
                    $this->formatPrice($offer['avg_deal_size']),
                    $offer['roi'] . '%',
                ]);
            }
            fputcsv($handle, []);
        }

        // Source Analysis
        if (!empty($data['source_analysis'])) {
            fputcsv($handle, ['LEAD SOURCE ANALYSIS']);
            fputcsv($handle, ['Source', 'Type', 'Total Leads', 'Won Deals', 'Conversion Rate', 'Total Revenue', 'Total Cost', 'Cost per Lead', 'Cost per Acquisition', 'ROI', 'ROAS']);

            foreach ($data['source_analysis'] as $source) {
                fputcsv($handle, [
                    $source['source_name'],
                    $source['channel_type'],
                    $source['total_leads'],
                    $source['won_leads'],
                    $source['conversion_rate'] . '%',
                    $this->formatPrice($source['total_revenue']),
                    $this->formatPrice($source['total_cost']),
                    $this->formatPrice($source['cost_per_lead']),
                    $this->formatPrice($source['cost_per_acquisition']),
                    $source['roi'] . '%',
                    $source['roas'] . 'x',
                ]);
            }
        }
    }

    /**
     * Write revenue report to Excel
     *
     * @param resource $handle
     * @param array $data
     */
    protected function writeExcelRevenue($handle, array $data): void
    {
        fputcsv($handle, ['REVENUE ANALYSIS']);
        fputcsv($handle, []);

        // Revenue Trends
        if (!empty($data['trends']['trends'])) {
            fputcsv($handle, ['REVENUE TRENDS']);
            fputcsv($handle, ['Date', 'Revenue', 'Deal Count', 'Avg Deal Size']);

            foreach ($data['trends']['trends'] as $trend) {
                fputcsv($handle, [
                    $trend['date'],
                    $this->formatPrice($trend['revenue']),
                    $trend['deal_count'],
                    $this->formatPrice($trend['avg_deal_size']),
                ]);
            }
            fputcsv($handle, []);
        }

        // Revenue Forecast
        if (!empty($data['forecast']['forecast'])) {
            fputcsv($handle, ['REVENUE FORECAST']);
            fputcsv($handle, ['Avg Daily Revenue', $this->formatPrice($data['forecast']['summary']['avg_daily_revenue'] ?? 0)]);
            fputcsv($handle, ['Recent Avg', $this->formatPrice($data['forecast']['summary']['recent_avg'] ?? 0)]);
            fputcsv($handle, ['Growth Rate', ($data['forecast']['summary']['growth_rate'] ?? 0) . '%']);
            fputcsv($handle, ['Forecast Total', $this->formatPrice($data['forecast']['summary']['forecast_total'] ?? 0)]);
            fputcsv($handle, []);

            fputcsv($handle, ['Date', 'Forecast Revenue', 'Lower Bound', 'Upper Bound', 'Confidence']);
            foreach ($data['forecast']['forecast'] as $item) {
                fputcsv($handle, [
                    $item['date'],
                    $this->formatPrice($item['forecast_revenue']),
                    $this->formatPrice($item['lower_bound']),
                    $this->formatPrice($item['upper_bound']),
                    $item['confidence'],
                ]);
            }
        }
    }

    /**
     * Generate PDF HTML content
     *
     * @param Business $business
     * @param array $data
     * @param string $reportType
     * @return string
     */
    protected function generatePDFHtml(Business $business, array $data, string $reportType): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Analytics Report - {$business->name}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #333; padding: 40px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #4F46E5; padding-bottom: 20px; }
        .header h1 { color: #4F46E5; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .info-box { background: #F3F4F6; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
        .info-box .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .info-box .label { font-weight: bold; color: #4B5563; }
        .info-box .value { color: #1F2937; }
        .section { margin-bottom: 40px; }
        .section-title { color: #1F2937; font-size: 18px; font-weight: bold; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #E5E7EB; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { background: #4F46E5; color: white; padding: 12px 8px; text-align: left; font-weight: 600; }
        table td { padding: 10px 8px; border-bottom: 1px solid #E5E7EB; }
        table tr:nth-child(even) { background: #F9FAFB; }
        table tr:hover { background: #F3F4F6; }
        .metric-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .metric-card { background: white; border: 1px solid #E5E7EB; border-radius: 8px; padding: 15px; text-align: center; }
        .metric-card .label { color: #6B7280; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; }
        .metric-card .value { color: #1F2937; font-size: 24px; font-weight: bold; }
        .metric-card .change { color: #10B981; font-size: 11px; margin-top: 5px; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 2px solid #E5E7EB; text-align: center; color: #6B7280; font-size: 11px; }
        .text-green { color: #10B981; }
        .text-red { color: #EF4444; }
        .text-blue { color: #3B82F6; }
        .text-purple { color: #8B5CF6; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        .badge-success { background: #D1FAE5; color: #065F46; }
        .badge-warning { background: #FEF3C7; color: #92400E; }
        .badge-danger { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Sales Analytics Report</h1>
        <p>{$business->name}</p>
    </div>

    <div class="info-box">
        <div class="row">
            <span class="label">Generated:</span>
            <span class="value">{$this->formatDate(Carbon::now())}</span>
        </div>
HTML;

        if (!empty($data['filters']['date_from']) || !empty($data['filters']['date_to'])) {
            $dateFrom = $data['filters']['date_from'] ?? 'Start';
            $dateTo = $data['filters']['date_to'] ?? 'Now';
            $html .= <<<HTML
        <div class="row">
            <span class="label">Period:</span>
            <span class="value">{$dateFrom} to {$dateTo}</span>
        </div>
HTML;
        }

        $html .= "</div>";

        // Content based on report type
        switch ($reportType) {
            case 'funnel':
                $html .= $this->generatePDFFunnelContent($data);
                break;
            case 'performance':
                $html .= $this->generatePDFPerformanceContent($data);
                break;
            case 'revenue':
                $html .= $this->generatePDFRevenueContent($data);
                break;
            default:
                $html .= $this->generatePDFFullContent($data);
                break;
        }

        $html .= <<<HTML
    <div class="footer">
        <p>Generated by BiznеsPilot Analytics System | © {$this->formatYear(Carbon::now())} {$business->name}</p>
        <p style="margin-top: 5px;">This report is confidential and intended for internal use only.</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Generate full PDF content
     *
     * @param array $data
     * @return string
     */
    protected function generatePDFFullContent(array $data): string
    {
        $html = '<div class="section">';
        $html .= '<h2 class="section-title">Dashboard Metrics</h2>';
        $html .= '<div class="metric-grid">';

        $metrics = [
            ['label' => 'Total Revenue', 'value' => $this->formatPrice($data['metrics']['total_revenue'] ?? 0), 'change' => ($data['metrics']['revenue_growth'] ?? 0) . '%'],
            ['label' => 'Total Leads', 'value' => $data['metrics']['total_leads'] ?? 0, 'change' => ''],
            ['label' => 'Won Deals', 'value' => $data['metrics']['won_deals'] ?? 0, 'change' => ''],
            ['label' => 'Conversion Rate', 'value' => ($data['metrics']['conversion_rate'] ?? 0) . '%', 'change' => ''],
            ['label' => 'Pipeline Value', 'value' => $this->formatPrice($data['metrics']['pipeline_value'] ?? 0), 'change' => ''],
            ['label' => 'Avg Deal Size', 'value' => $this->formatPrice($data['metrics']['avg_deal_size'] ?? 0), 'change' => ''],
        ];

        foreach ($metrics as $metric) {
            $html .= '<div class="metric-card">';
            $html .= '<div class="label">' . $metric['label'] . '</div>';
            $html .= '<div class="value">' . $metric['value'] . '</div>';
            if ($metric['change']) {
                $html .= '<div class="change text-green">+' . $metric['change'] . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div></div>';

        // Add other sections (Funnel, Performance, etc.)
        $html .= $this->generatePDFFunnelContent($data);
        $html .= $this->generatePDFPerformanceContent($data);

        return $html;
    }

    /**
     * Generate funnel PDF content
     *
     * @param array $data
     * @return string
     */
    protected function generatePDFFunnelContent(array $data): string
    {
        if (empty($data['funnel']['funnel_stages'])) {
            return '';
        }

        $html = '<div class="section">';
        $html .= '<h2 class="section-title">Conversion Funnel</h2>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Stage</th><th>Count</th><th>Percentage</th><th>Conversion Rate</th><th>Dropoff Rate</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($data['funnel']['funnel_stages'] as $stage) {
            $html .= '<tr>';
            $html .= '<td><strong>' . $stage['label'] . '</strong></td>';
            $html .= '<td>' . $stage['count'] . '</td>';
            $html .= '<td>' . $stage['percentage'] . '%</td>';
            $html .= '<td class="text-green">' . $stage['conversion_rate'] . '%</td>';
            $html .= '<td class="text-red">' . $stage['dropoff_rate'] . '%</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Generate performance PDF content
     *
     * @param array $data
     * @return string
     */
    protected function generatePDFPerformanceContent(array $data): string
    {
        $html = '';

        // Dream Buyer Performance
        if (!empty($data['dream_buyer_performance'])) {
            $html .= '<div class="section">';
            $html .= '<h2 class="section-title">Dream Buyer Performance</h2>';
            $html .= '<table><thead><tr>';
            $html .= '<th>Dream Buyer</th><th>Total Leads</th><th>Won Deals</th><th>CR</th><th>Revenue</th>';
            $html .= '</tr></thead><tbody>';

            foreach (array_slice($data['dream_buyer_performance'], 0, 10) as $buyer) {
                $html .= '<tr>';
                $html .= '<td><strong>' . $buyer['dream_buyer_name'] . '</strong></td>';
                $html .= '<td>' . $buyer['total_leads'] . '</td>';
                $html .= '<td>' . $buyer['won_leads'] . '</td>';
                $html .= '<td class="text-purple">' . $buyer['conversion_rate'] . '%</td>';
                $html .= '<td class="text-blue">' . $this->formatPrice($buyer['total_revenue']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        // Offer Performance
        if (!empty($data['offer_performance'])) {
            $html .= '<div class="section">';
            $html .= '<h2 class="section-title">Offer Performance</h2>';
            $html .= '<table><thead><tr>';
            $html .= '<th>Offer</th><th>Value Score</th><th>Won Deals</th><th>CR</th><th>Revenue</th><th>ROI</th>';
            $html .= '</tr></thead><tbody>';

            foreach (array_slice($data['offer_performance'], 0, 10) as $offer) {
                $html .= '<tr>';
                $html .= '<td><strong>' . $offer['offer_name'] . '</strong></td>';
                $html .= '<td>' . ($offer['value_score'] ?? 'N/A') . '</td>';
                $html .= '<td>' . $offer['won_leads'] . '</td>';
                $html .= '<td class="text-purple">' . $offer['conversion_rate'] . '%</td>';
                $html .= '<td class="text-blue">' . $this->formatPrice($offer['total_revenue']) . '</td>';
                $html .= '<td class="text-green">' . $offer['roi'] . '%</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        return $html;
    }

    /**
     * Generate revenue PDF content
     *
     * @param array $data
     * @return string
     */
    protected function generatePDFRevenueContent(array $data): string
    {
        $html = '';

        if (!empty($data['forecast']['summary'])) {
            $html .= '<div class="section">';
            $html .= '<h2 class="section-title">Revenue Forecast</h2>';
            $html .= '<div class="metric-grid">';

            $metrics = [
                ['label' => 'Avg Daily Revenue', 'value' => $this->formatPrice($data['forecast']['summary']['avg_daily_revenue'] ?? 0)],
                ['label' => 'Recent Avg', 'value' => $this->formatPrice($data['forecast']['summary']['recent_avg'] ?? 0)],
                ['label' => 'Growth Rate', 'value' => ($data['forecast']['summary']['growth_rate'] ?? 0) . '%'],
                ['label' => 'Forecast Total', 'value' => $this->formatPrice($data['forecast']['summary']['forecast_total'] ?? 0)],
            ];

            foreach ($metrics as $metric) {
                $html .= '<div class="metric-card">';
                $html .= '<div class="label">' . $metric['label'] . '</div>';
                $html .= '<div class="value">' . $metric['value'] . '</div>';
                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        return $html;
    }

    /**
     * Format price for export
     *
     * @param float $price
     * @return string
     */
    protected function formatPrice(float $price): string
    {
        return number_format($price, 0, '.', ' ') . ' so\'m';
    }

    /**
     * Format date
     *
     * @param Carbon $date
     * @return string
     */
    protected function formatDate(Carbon $date): string
    {
        return $date->format('d.m.Y H:i');
    }

    /**
     * Format year
     *
     * @param Carbon $date
     * @return string
     */
    protected function formatYear(Carbon $date): string
    {
        return $date->format('Y');
    }

    /**
     * Clean up old export files (older than 24 hours)
     */
    public function cleanupOldExports(): void
    {
        $exportDir = storage_path('app/exports');

        if (!is_dir($exportDir)) {
            return;
        }

        $files = glob($exportDir . '/*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) >= 86400) { // 24 hours
                unlink($file);
            }
        }
    }
}
