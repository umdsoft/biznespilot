<?php

namespace App\Services\Agent\Analytics\Reports;

use App\Services\Agent\Analytics\Tools\FunnelAnalysisTool;
use App\Services\Agent\Analytics\Tools\KPICalculatorTool;
use App\Services\AI\AIService;

/**
 * Haftalik samaradorlik hisoboti (bazadan + Haiku).
 * Har dushanba 09:00 da generatsiya qilinadi.
 */
class WeeklyPerformanceReport
{
    public function __construct(
        private AIService $aiService,
        private KPICalculatorTool $kpiTool,
        private FunnelAnalysisTool $funnelTool,
    ) {}

    /**
     * Haftalik hisobot yaratish
     */
    public function generate(string $businessId): array
    {
        // 1. Bazadan ma'lumot olish (bepul)
        $kpi = $this->kpiTool->calculate($businessId, 'last_week');
        $funnel = $this->funnelTool->analyze(
            $businessId,
            now()->subWeek()->startOfWeek()->toDateString(),
            now()->subWeek()->endOfWeek()->toDateString(),
        );

        // 2. Ma'lumotlarni matnda tayyorlash
        $dataText = $this->formatData($kpi, $funnel);

        // 3. Haiku bilan tahlil va tavsiya
        $aiResponse = $this->aiService->ask(
            prompt: "O'tgan hafta natijalari:\n{$dataText}\n\nQisqa tahlil va 3 ta amaliy tavsiya ber.",
            systemPrompt: "Sen BiznesPilot tahlilchisisan. Haftalik hisobot tayyorla. O'zbek tilida, aniq va qisqa.",
            preferredModel: 'haiku',
            maxTokens: 1000,
            businessId: $businessId,
            agentType: 'analytics_report',
        );

        $report = "📊 **Haftalik samaradorlik hisoboti**\n"
            . "📅 " . now()->subWeek()->startOfWeek()->format('d.m') . " — " . now()->subWeek()->endOfWeek()->format('d.m.Y') . "\n\n"
            . $dataText . "\n\n"
            . "🤖 **AI tahlili:**\n" . ($aiResponse->success ? $aiResponse->content : 'Tahlil mavjud emas.');

        return [
            'success' => true,
            'report' => $report,
            'ai_tokens' => $aiResponse->tokensInput + $aiResponse->tokensOutput,
            'cost_usd' => $aiResponse->costUsd,
        ];
    }

    private function formatData(array $kpi, array $funnel): string
    {
        $parts = [];

        if (isset($kpi['current'])) {
            $c = $kpi['current'];
            $ch = $kpi['changes'] ?? [];

            $parts[] = "KPI:";
            if (isset($c['revenue_total'])) $parts[] = "  Daromad: " . number_format($c['revenue_total']) . " so'm (" . ($ch['revenue_total'] ?? 0) . "%)";
            if (isset($c['sales_total'])) $parts[] = "  Sotuvlar: {$c['sales_total']} ta (" . ($ch['sales_total'] ?? 0) . "%)";
            if (isset($c['leads_total'])) $parts[] = "  Leadlar: {$c['leads_total']} ta (" . ($ch['leads_total'] ?? 0) . "%)";
            if (isset($c['conversion_rate'])) $parts[] = "  Konversiya: {$c['conversion_rate']}%";
        }

        if (isset($funnel['overall_conversion'])) {
            $parts[] = "\nFunnel: konversiya {$funnel['overall_conversion']}%, yutilgan {$funnel['won_count']}, yo'qotilgan {$funnel['lost_count']}";
        }

        return implode("\n", $parts) ?: "Ma'lumot mavjud emas.";
    }
}
