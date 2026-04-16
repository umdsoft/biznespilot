<?php

namespace App\Services\Agent\Marketing;

use App\Services\Agent\Marketing\Tools\CompetitorDataTool;
use App\Services\Agent\Marketing\Tools\ContentAnalysisTool;
use App\Services\Agent\Marketing\Tools\OptimalTimeTool;
use App\Services\Agent\Memory\ShortTermMemory;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Marketing agenti — kontent strategiyasi, kanal boshqaruvi, raqobatchi tahlili.
 *
 * Gibrid mantiq:
 * 1. Bazadan kontent statistikasi (bepul)
 * 2. Bazadan optimal vaqt (bepul)
 * 3. Bazadan raqobatchi ma'lumoti (bepul)
 * 4. Oddiy tavsiya — Haiku (arzon)
 * 5. Strategik reja — Sonnet (sifatli)
 */
class MarketingAgentService
{
    private string $contentPrompt;
    private string $competitorPrompt;

    public function __construct(
        private AIService $aiService,
        private ContentAnalysisTool $contentTool,
        private OptimalTimeTool $timeTool,
        private CompetitorDataTool $competitorTool,
        private ShortTermMemory $shortTermMemory,
    ) {
        $contentPath = __DIR__ . '/Prompts/content_suggestion.txt';
        $this->contentPrompt = file_exists($contentPath) ? file_get_contents($contentPath) : '';

        $competitorPath = __DIR__ . '/Prompts/competitor_analysis.txt';
        $this->competitorPrompt = file_exists($competitorPath) ? file_get_contents($competitorPath) : '';
    }

    /**
     * Marketing savolini qayta ishlash
     */
    public function handle(string $message, string $businessId, string $conversationId): AIResponse
    {
        $normalizedMessage = mb_strtolower(trim($message));

        try {
            $questionType = $this->classifyQuestion($normalizedMessage);
            $data = $this->gatherData($questionType, $businessId);

            // Raqobatchi tahlili — alohida prompt
            if ($questionType === 'competitor') {
                return $this->analyzeCompetitors($message, $data, $businessId);
            }

            // Oddiy statistika — bazadan javob (bepul)
            if ($questionType === 'simple_stats' && ($data['content']['success'] ?? false)) {
                return $this->formatSimpleStats($data);
            }

            // Optimal vaqt — bazadan javob (bepul)
            if ($questionType === 'optimal_time' && isset($data['optimal_times']['success']) && $data['optimal_times']['success']) {
                return $this->formatOptimalTimeResponse($data['optimal_times']);
            }

            // Kontent tavsiya va strategiya — AI bilan
            return $this->generateContentAdvice($message, $data, $businessId, $questionType);

        } catch (\Exception $e) {
            Log::error('MarketingAgent: xatolik', ['error' => $e->getMessage()]);
            return AIResponse::error($e->getMessage());
        }
    }

    /**
     * Savol turini aniqlash
     */
    private function classifyQuestion(string $message): string
    {
        // Kontent tavsiya
        if ($this->containsAny($message, ['nima post', 'kontent tavsiya', 'bugun nima', 'post qilsam', 'kontent reja'])) {
            return 'content_suggestion';
        }

        // Raqobatchi
        if ($this->containsAny($message, ['raqobatchi', 'competitor', 'rakib'])) {
            return 'competitor';
        }

        // Optimal vaqt
        if ($this->containsAny($message, ['qachon post', 'eng yaxshi vaqt', 'optimal vaqt', 'qaysi vaqtda'])) {
            return 'optimal_time';
        }

        // Oddiy statistika
        if ($this->containsAny($message, ['reach', 'engagement', 'like', 'post statistika', 'natija'])) {
            return 'simple_stats';
        }

        // Strategiya — murakkab
        if ($this->containsAny($message, ['strategiya', 'reja', 'kampaniya', 'oylik reja', 'haftalik reja'])) {
            return 'strategy';
        }

        return 'general';
    }

    /**
     * Savol turiga qarab ma'lumot yig'ish
     */
    private function gatherData(string $questionType, string $businessId): array
    {
        $data = [];

        // Kontent statistikasi — ko'p savollar uchun kerak
        if (in_array($questionType, ['content_suggestion', 'simple_stats', 'strategy', 'general'])) {
            $data['content'] = $this->contentTool->getRecentPostsPerformance($businessId);
            $data['top_worst'] = $this->contentTool->getTopAndWorstPosts($businessId);
            $data['by_type'] = $this->contentTool->getPerformanceByContentType($businessId);
        }

        // Optimal vaqt
        if (in_array($questionType, ['content_suggestion', 'optimal_time', 'strategy'])) {
            $data['optimal_times'] = $this->timeTool->getOptimalTimes($businessId);
        }

        // Raqobatchi
        if (in_array($questionType, ['competitor', 'strategy'])) {
            $data['competitors'] = $this->competitorTool->getCompetitorsSummary($businessId);
            $data['competitor_activities'] = $this->competitorTool->getCompetitorActivities($businessId);
        }

        return $data;
    }

    /**
     * Oddiy statistika javob (bazadan, bepul)
     */
    private function formatSimpleStats(array $data): AIResponse
    {
        $summary = $data['content']['summary'] ?? [];
        $topWorse = $data['top_worst'] ?? [];

        $response = "📊 **Oxirgi 7 kun kontent statistikasi:**\n\n"
            . "📝 Postlar soni: **{$summary['post_count']}** ta\n"
            . "💬 Engagement: **{$summary['avg_engagement']}%**\n"
            . "👁️ Umumiy reach: **" . number_format($summary['total_reach']) . "**\n"
            . "❤️ Likelar: **" . number_format($summary['total_likes']) . "**\n"
            . "💬 Izohlar: **{$summary['total_comments']}**\n";

        // Eng yaxshi post
        if (!empty($topWorse['top_posts'])) {
            $best = $topWorse['top_posts'][0] ?? null;
            if ($best) {
                $caption = mb_substr($best->caption ?? '', 0, 50);
                $response .= "\n🏆 **Eng yaxshi post:** \"{$caption}...\" (engagement: {$best->engagement_rate}%)";
            }
        }

        return AIResponse::fromDatabase($response);
    }

    /**
     * Optimal vaqt javob (bazadan, bepul)
     */
    private function formatOptimalTimeResponse(array $data): AIResponse
    {
        if (empty($data['top_times'])) {
            return AIResponse::fromDatabase("Hozircha post statistikasi yetarli emas. Ko'proq post joylang, keyin eng yaxshi vaqtni aniqlaymiz.");
        }

        $response = "⏰ **Eng yaxshi post joylash vaqtlari:**\n\n";
        foreach ($data['top_times'] as $i => $t) {
            $num = $i + 1;
            $response .= "{$num}. **{$t['day']} {$t['hour']}** — engagement {$t['avg_engagement']}% ({$t['post_count']} ta post asosida)\n";
        }

        if ($data['today_best']) {
            $response .= "\n🎯 **Bugun eng yaxshi vaqt: {$data['today_best']['hour']}** (engagement {$data['today_best']['avg_engagement']}%)";
        }

        return AIResponse::fromDatabase($response);
    }

    /**
     * Raqobatchi tahlili
     */
    private function analyzeCompetitors(string $message, array $data, string $businessId): AIResponse
    {
        $dataText = $this->formatCompetitorData($data);

        if (empty($dataText)) {
            return AIResponse::fromDatabase("Raqobatchi ma'lumotlari topilmadi. Avval raqobatchilarni qo'shing.");
        }

        return $this->aiService->ask(
            prompt: "Foydalanuvchi savoli: {$message}\n\nRaqobatchi ma'lumotlari:\n{$dataText}",
            systemPrompt: $this->competitorPrompt ?: 'Sen marketing agentisan. O\'zbek tilida.',
            preferredModel: 'haiku',
            maxTokens: 1200,
            businessId: $businessId,
            agentType: 'marketing',
        );
    }

    /**
     * AI orqali kontent tavsiya yaratish
     */
    private function generateContentAdvice(string $message, array $data, string $businessId, string $questionType): AIResponse
    {
        $dataText = $this->formatContentData($data);

        // Marketing Orchestrator dan kontekst qo'shish (yagona marketing brain)
        $orchestratorContext = '';
        try {
            $snapshot = app(\App\Services\Marketing\Orchestrator\MarketingOrchestrator::class)
                ->getSnapshot($businessId);
            if (!empty($snapshot['health'])) {
                $orchestratorContext = "\n\nMARKETING SOG'LIK: {$snapshot['health']['overall']}/100 ({$snapshot['health']['grade']})";
                if (!empty($snapshot['priorities']) && count($snapshot['priorities']) > 0) {
                    $orchestratorContext .= "\nUSTUVOR ISHLAR:";
                    foreach (array_slice($snapshot['priorities'], 0, 3) as $p) {
                        $orchestratorContext .= "\n- " . $p['title'];
                    }
                }
            }
        } catch (\Exception $e) {}

        $model = 'haiku';
        $maxTokens = 1200;

        $prompt = "Foydalanuvchi savoli: {$message}\n\nMavjud ma'lumotlar:\n{$dataText}{$orchestratorContext}";

        return $this->aiService->ask(
            prompt: $prompt,
            systemPrompt: $this->contentPrompt ?: 'Sen marketing agentisan. O\'zbek tilida.',
            preferredModel: $model,
            maxTokens: $maxTokens,
            businessId: $businessId,
            agentType: 'marketing',
        );
    }

    /**
     * Kontent ma'lumotlarini matn formatida tayyorlash
     */
    private function formatContentData(array $data): string
    {
        $parts = [];

        if (isset($data['content']['summary'])) {
            $s = $data['content']['summary'];
            $parts[] = "Oxirgi 7 kun: {$s['post_count']} post, engagement {$s['avg_engagement']}%, reach {$s['total_reach']}";
        }

        if (isset($data['by_type']['by_type'])) {
            $parts[] = "Kontent turlari bo'yicha:";
            foreach ($data['by_type']['by_type'] as $type) {
                $parts[] = "  - {$type->media_type}: {$type->count} ta, engagement {$type->avg_engagement}%";
            }
        }

        if (isset($data['optimal_times']['top_times'])) {
            $parts[] = "Eng yaxshi vaqtlar:";
            foreach (array_slice($data['optimal_times']['top_times'], 0, 3) as $t) {
                $parts[] = "  - {$t['day']} {$t['hour']} (engagement {$t['avg_engagement']}%)";
            }
        }

        return implode("\n", $parts) ?: "Kontent statistikasi hozircha mavjud emas.";
    }

    /**
     * Raqobatchi ma'lumotlarini matn formatida
     */
    private function formatCompetitorData(array $data): string
    {
        $parts = [];

        if (isset($data['competitors']['competitors'])) {
            foreach ($data['competitors']['competitors'] as $c) {
                $followers = $c['followers'] ? number_format($c['followers']) : '?';
                $engagement = $c['engagement_rate'] ?? '?';
                $parts[] = "- {$c['name']}: followers {$followers}, engagement {$engagement}%";
            }
        }

        if (isset($data['competitor_activities']['activities'])) {
            $parts[] = "\nOxirgi faoliyatlar:";
            foreach (array_slice($data['competitor_activities']['activities'], 0, 5) as $a) {
                $parts[] = "- {$a->competitor_name}: {$a->description}";
            }
        }

        return implode("\n", $parts);
    }

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
