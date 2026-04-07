<?php

namespace App\Services\Agent\Analytics;

use App\Services\Agent\Analytics\Tools\FunnelAnalysisTool;
use App\Services\Agent\Analytics\Tools\KPICalculatorTool;
use App\Services\Agent\BusinessDataService;
use App\Services\Agent\Memory\BusinessContextMemory;
use App\Services\Agent\Memory\ShortTermMemory;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Tahlil agenti — KPI hisoblash, funnel tahlili, anomaliya aniqlash.
 *
 * Gibrid mantiq:
 * 1. Bazadan raqamlarni olish (bepul)
 * 2. Oddiy savollar uchun raqamlarni formatlash (bepul)
 * 3. Murakkab tahlil uchun Haiku ga yuborish (arzon)
 * 4. Strategik tavsiya uchun Sonnet ga yuborish (sifatli)
 */
class AnalyticsAgentService
{
    private string $systemPrompt;

    public function __construct(
        private AIService $aiService,
        private KPICalculatorTool $kpiTool,
        private FunnelAnalysisTool $funnelTool,
        private ShortTermMemory $shortTermMemory,
        private BusinessContextMemory $businessContextMemory,
    ) {
        // Tizim ko'rsatmasini fayldan yuklash
        $promptPath = __DIR__ . '/Prompts/kpi_analysis.txt';
        $this->systemPrompt = file_exists($promptPath)
            ? file_get_contents($promptPath)
            : 'Sen BiznesPilot AI tahlil agentisan. O\'zbek tilida javob ber.';
    }

    /**
     * Tahlil savolini qayta ishlash
     */
    public function handle(string $message, string $businessId, string $conversationId): AIResponse
    {
        $normalizedMessage = mb_strtolower(trim($message));

        try {
            // 1-qadam: Savol turini aniqlash
            $questionType = $this->classifyQuestion($normalizedMessage);

            // 2-qadam: Bazadan ma'lumot olish
            $data = $this->gatherData($questionType, $businessId, $normalizedMessage);

            // 3-qadam: HAR DOIM AI tahlil bilan javob berish
            // Bazadan raqam olinadi + AI (Haiku) tahlil va tavsiya qo'shadi
            return $this->analyzeWithAI($message, $data, $businessId, $questionType);

        } catch (\Exception $e) {
            Log::error('AnalyticsAgent: xatolik', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
            ]);
            return AIResponse::error($e->getMessage());
        }
    }

    /**
     * Savol turini aniqlash (qoidaga asoslangan, bepul)
     */
    private function classifyQuestion(string $message): string
    {
        // Oddiy raqam so'rovlari — bazadan javob berish mumkin (AI chaqirilMAYDI)
        $simplePatterns = [
            // Sotuv — turli shakllar
            'bugungi sotuv', 'bugun sotuv', 'sotuvlar soni', 'nechta sotuv',
            'bugun nechta', 'sotuv qanday', 'sotuvlar qanday', 'savdo qanday',
            // Lead — turli shakllar
            'bugungi lead', 'leadlar soni', 'nechta lead', 'lid soni',
            'leadlar holati', 'leadlar qanday', 'issiq lead', 'sovuq lead',
            'yangi lead', 'lead kim',
            // Daromad — turli shakllar
            'bugungi daromad', 'jami daromad', 'daromad qancha',
            'bu hafta daromad', 'bu oy daromad', 'haftalik daromad',
            'bu oydagi daromad', 'oylik daromad', 'tushum',
            // KPI raqamlar
            'bugungi kpi', 'kpi ko\'rsat', 'kpi raqamlar', 'kpi larim',
            'kpi qanday', 'ko\'rsatkichlar',
            // Hisob-kitob
            'cac nechta', 'cac qancha', 'mijoz jalb narxi',
            'clv', 'umr qiymati', 'roas', 'roi',
            'konversiya nechta', 'conversion rate',
            // Raqobatchi
            'raqobatchi kim', 'raqobatchilar',
        ];

        foreach ($simplePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return 'simple_kpi';
            }
        }

        // Funnel tahlili
        if ($this->containsAny($message, ['funnel', 'bosqich', 'konversiya', 'pipeline', 'savdo bosqich'])) {
            return 'funnel';
        }

        // Solishtirish
        if ($this->containsAny($message, ['solishtir', 'farq', 'o\'tgan oy', 'o\'tgan hafta', 'oldingi'])) {
            return 'comparison';
        }

        // Nega savollari — chuqur tahlil
        if ($this->containsAny($message, ['nega', 'sabab', 'nima uchun', 'tushdi', 'kamaydi'])) {
            return 'deep_analysis';
        }

        // Hisobot
        if ($this->containsAny($message, ['hisobot', 'report', 'umumiy holat', 'biznes holati'])) {
            return 'report';
        }

        // Default — umumiy tahlil
        return 'general';
    }

    /**
     * Savol turiga qarab bazadan ma'lumot yig'ish
     */
    private function gatherData(string $questionType, string $businessId, string $message): array
    {
        $data = ['success' => true, 'type' => $questionType];

        // Davrni aniqlash
        $period = $this->detectPeriod($message);

        switch ($questionType) {
            case 'simple_kpi':
                $data['today'] = $this->kpiTool->getTodaySummary($businessId);
                // KPI hisoblash ham (CAC, ROAS, konversiya kabi savollar uchun)
                $data['kpi'] = $this->kpiTool->calculate($businessId, $period);
                break;

            case 'funnel':
                $data['funnel'] = $this->funnelTool->analyze($businessId);
                break;

            case 'comparison':
            case 'deep_analysis':
            case 'report':
            case 'general':
                $data['kpi'] = $this->kpiTool->calculate($businessId, $period);
                $data['funnel'] = $this->funnelTool->analyze($businessId);
                break;
        }

        return $data;
    }

    /**
     * Oddiy KPI savollariga bazadan javob (AI chaqirilmaydi — bepul)
     */
    private function formatSimpleKPIResponse(array $data): AIResponse
    {
        $today = $data['today'] ?? [];

        if (! ($today['success'] ?? false)) {
            return AIResponse::fromDatabase("Ma'lumot topilmadi. Iltimos keyinroq urinib ko'ring.");
        }

        $todayData = $today['today'];
        $yesterdayData = $today['yesterday'];

        // Agar hamma narsa 0 bo'lsa — tavsiya bilan javob
        $totalActivity = ($todayData['sales_count'] ?? 0) + ($todayData['leads_count'] ?? 0) + ($yesterdayData['sales_count'] ?? 0);
        if ($totalActivity === 0) {
            return AIResponse::fromDatabase(
                "Hozircha biznesingizda sotuv va lidlar kiritilmagan.\n\n"
                . "Boshlash uchun tavsiyalar:\n"
                . "1. Lidlar bo'limiga o'ting va birinchi mijozlaringizni kiriting\n"
                . "2. Marketing bo'limida kontent reja tuzing\n"
                . "3. Integratsiyalar orqali Instagram/Telegram ulang\n"
                . "4. KPI Reja bo'limida maqsadlaringizni belgilang\n\n"
                . "Ma'lumotlar kiritilgandan so'ng men sizga batafsil tahlil beraman!"
            );
        }

        // Solishtirish
        $salesChange = $yesterdayData['sales_count'] > 0
            ? round((($todayData['sales_count'] - $yesterdayData['sales_count']) / $yesterdayData['sales_count']) * 100, 1)
            : 0;

        $salesEmoji = $salesChange > 0 ? '📈' : ($salesChange < 0 ? '📉' : '➡️');
        $revenueFormatted = number_format($todayData['sales_total'], 0, '.', ',');

        $response = "Bugungi holat:\n\n"
            . "Sotuvlar: {$todayData['sales_count']} ta ({$salesEmoji} {$salesChange}% kechaga nisbatan)\n"
            . "Daromad: {$revenueFormatted} so'm\n"
            . "Yangi leadlar: {$todayData['leads_count']} ta\n\n"
            . "Kecha: {$yesterdayData['sales_count']} ta sotuv, " . number_format($yesterdayData['sales_total'], 0, '.', ',') . " so'm";

        return AIResponse::fromDatabase($response);
    }

    /**
     * KPI hisoblash natijasini formatlash (bazadan, bepul)
     */
    private function formatKPICalculationResponse(array $kpi): AIResponse
    {
        $current = $kpi['current'] ?? [];
        $changes = $kpi['changes'] ?? [];
        $period = $kpi['period'] ?? [];

        $response = "📊 **KPI ko'rsatkichlar** ({$period['start']} — {$period['end']}):\n\n";

        $metrics = [
            'revenue_total' => ['💰 Daromad', 'so\'m'],
            'sales_total' => ['🛒 Sotuvlar', 'ta'],
            'leads_total' => ['👥 Leadlar', 'ta'],
            'avg_check' => ['🧾 O\'rtacha chek', 'so\'m'],
            'conversion_rate' => ['📈 Konversiya', '%'],
            'cac' => ['💸 CAC', 'so\'m'],
            'roi' => ['📊 ROI', '%'],
        ];

        foreach ($metrics as $key => [$label, $unit]) {
            $value = $current[$key] ?? 0;
            if ($value == 0) continue;

            $formatted = $unit === 'so\'m' ? number_format($value, 0, '.', ',') : $value;
            $change = $changes[$key] ?? 0;
            $emoji = $change > 0 ? '📈' : ($change < 0 ? '📉' : '➡️');
            $changeStr = $change != 0 ? " {$emoji} {$change}%" : '';

            $response .= "{$label}: **{$formatted}** {$unit}{$changeStr}\n";
        }

        return AIResponse::fromDatabase($response);
    }

    /**
     * Funnel ma'lumotlarini formatlash (bazadan, bepul)
     */
    private function formatFunnelResponse(array $funnel): AIResponse
    {
        $response = "📈 **Savdo funnel holati:**\n\n"
            . "👥 Jami leadlar: **{$funnel['total_leads']}**\n"
            . "🏆 Yutilgan: **{$funnel['won_count']}**\n"
            . "❌ Yo'qotilgan: **{$funnel['lost_count']}**\n"
            . "📊 Umumiy konversiya: **{$funnel['overall_conversion']}%**\n";

        if (!empty($funnel['stages'])) {
            $response .= "\n**Bosqichlar:**\n";
            $stageEmoji = ['new' => '🆕', 'contacted' => '📞', 'qualified' => '✅', 'proposal' => '📋', 'negotiation' => '🤝', 'won' => '🏆', 'lost' => '❌'];
            foreach ($funnel['stages'] as $stage) {
                if ($stage['count'] > 0) {
                    $emoji = $stageEmoji[$stage['stage']] ?? '•';
                    $response .= "{$emoji} {$stage['stage']}: **{$stage['count']}** ({$stage['conversion_from_total']}%)\n";
                }
            }
        }

        if ($funnel['avg_time_to_close_hours'] > 0) {
            $days = round($funnel['avg_time_to_close_hours'] / 24, 1);
            $response .= "\n⏱ O'rtacha yopish vaqti: **{$days} kun**";
        }

        return AIResponse::fromDatabase($response);
    }

    /**
     * AI bilan chuqur tahlil
     */
    private function analyzeWithAI(string $message, array $data, string $businessId, string $questionType): AIResponse
    {
        // Ma'lumotlarni matn formatida tayyorlash
        $dataText = $this->formatDataForAI($data);

        $model = 'haiku';
        $maxTokens = 1500;

        $businessContext = app(BusinessDataService::class)->getContextForAI($businessId, 'analytics');

        $prompt = "Foydalanuvchi savoli: {$message}\n\n"
            . "BIZNES MA'LUMOTLARI:\n{$businessContext}\n\n"
            . "TAHLIL RAQAMLARI:\n{$dataText}";

        return $this->aiService->ask(
            prompt: $prompt,
            systemPrompt: $this->systemPrompt,
            preferredModel: $model,
            maxTokens: $maxTokens,
            businessId: $businessId,
            agentType: 'analytics',
        );
    }

    /**
     * Ma'lumotlarni AI uchun matn formatida tayyorlash
     */
    private function formatDataForAI(array $data): string
    {
        $parts = [];

        if (isset($data['kpi']['current'])) {
            $kpi = $data['kpi']['current'];
            $changes = $data['kpi']['changes'] ?? [];

            $parts[] = "KPI ko'rsatkichlar:";
            foreach ($kpi as $key => $value) {
                if (is_numeric($value)) {
                    $change = isset($changes[$key]) ? " ({$changes[$key]}%)" : '';
                    $parts[] = "  - {$key}: {$value}{$change}";
                }
            }
        }

        if (isset($data['today']['today'])) {
            $today = $data['today']['today'];
            $parts[] = "Bugungi holat:";
            $parts[] = "  - Sotuvlar: {$today['sales_count']} ta";
            $parts[] = "  - Daromad: {$today['sales_total']} so'm";
            $parts[] = "  - Leadlar: {$today['leads_count']} ta";
        }

        if (isset($data['funnel']) && ($data['funnel']['success'] ?? false)) {
            $funnel = $data['funnel'];
            $parts[] = "Savdo funnel:";
            $parts[] = "  - Jami leadlar: {$funnel['total_leads']}";
            $parts[] = "  - Yutilgan: {$funnel['won_count']}";
            $parts[] = "  - Yo'qotilgan: {$funnel['lost_count']}";
            $parts[] = "  - Umumiy konversiya: {$funnel['overall_conversion']}%";
        }

        return implode("\n", $parts) ?: "Ma'lumotlar hozircha mavjud emas.";
    }

    /**
     * Xabardagi davr nomini aniqlash
     */
    private function detectPeriod(string $message): string
    {
        if ($this->containsAny($message, ['bugun', 'today'])) return 'today';
        if ($this->containsAny($message, ['kecha', 'yesterday'])) return 'yesterday';
        if ($this->containsAny($message, ['bu hafta', 'shu hafta', 'this week'])) return 'week';
        if ($this->containsAny($message, ['o\'tgan hafta', 'last week'])) return 'last_week';
        if ($this->containsAny($message, ['bu oy', 'shu oy', 'this month'])) return 'month';
        if ($this->containsAny($message, ['o\'tgan oy', 'last month'])) return 'last_month';
        return 'month'; // default — bu oy
    }

    /**
     * Matnda istalgan kalit so'z bormi
     */
    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
