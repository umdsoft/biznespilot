<?php

namespace App\Services\Agent\Analytics\Reports;

use App\Services\Agent\Analytics\Tools\KPICalculatorTool;
use Illuminate\Support\Facades\DB;

/**
 * Kundalik qisqa ma'lumot hisoboti (shablonli, bepul — AI chaqirilmaydi).
 * Har kuni ertalab 08:00 da generatsiya qilinadi.
 */
class DailyBriefReport
{
    public function __construct(
        private KPICalculatorTool $kpiTool,
    ) {}

    /**
     * Kundalik hisobot yaratish
     */
    public function generate(string $businessId): array
    {
        $today = $this->kpiTool->getTodaySummary($businessId);

        if (!($today['success'] ?? false)) {
            return ['success' => false, 'error' => 'Ma\'lumot olishda xatolik'];
        }

        $t = $today['today'];
        $y = $today['yesterday'];

        // Solishtirish
        $salesChange = $y['sales_count'] > 0
            ? round((($t['sales_count'] - $y['sales_count']) / $y['sales_count']) * 100, 1)
            : 0;

        $revenueChange = $y['sales_total'] > 0
            ? round((($t['sales_total'] - $y['sales_total']) / $y['sales_total']) * 100, 1)
            : 0;

        $emoji = fn ($v) => $v > 0 ? '📈' : ($v < 0 ? '📉' : '➡️');

        $report = "☀️ **Kundalik hisobot — " . now()->format('d.m.Y') . "**\n\n"
            . "💰 Sotuvlar: **{$t['sales_count']}** ta {$emoji($salesChange)} {$salesChange}%\n"
            . "💵 Daromad: **" . number_format($t['sales_total'], 0, '.', ',') . " so'm** {$emoji($revenueChange)} {$revenueChange}%\n"
            . "👥 Yangi leadlar: **{$t['leads_count']}** ta\n"
            . "\nYaxshi kun tilaymiz! 🙌";

        return [
            'success' => true,
            'report' => $report,
            'data' => ['today' => $t, 'yesterday' => $y],
            'ai_tokens' => 0, // Shablonli, bepul
        ];
    }
}
