<?php

namespace App\Services\ContentAI;

use App\Models\ContentTemplate;
use App\Models\ContentStyleGuide;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ContentAnalyzerService - Mavjud kontentni tahlil qilish
 *
 * Bu service postlarni AI orqali tahlil qilib,
 * style guide yaratish uchun ma'lumot to'playdi.
 */
class ContentAnalyzerService
{
    protected string $apiKey;
    protected string $model = 'claude-3-haiku-20240307';
    protected string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    /**
     * Bitta postni tahlil qilish
     */
    public function analyzePost(ContentTemplate $template): array
    {
        $prompt = $this->buildAnalysisPrompt($template->content);

        try {
            $response = $this->callClaudeApi($prompt, 500);

            $analysis = $this->parseAnalysisResponse($response);

            // Template ni yangilash
            $template->update([
                'ai_analysis' => $analysis,
                'content_cleaned' => $template->cleanContent(),
                'hashtags' => ContentTemplate::extractHashtags($template->content),
                'mentions' => ContentTemplate::extractMentions($template->content),
                'analyzed_at' => now(),
            ]);

            // Performance score hisoblash
            $template->updatePerformanceScore();

            return $analysis;

        } catch (\Exception $e) {
            Log::error('ContentAnalyzerService: Post analysis failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Bir nechta postni tahlil qilish
     */
    public function analyzeMultiplePosts(string $businessId, int $limit = 50): array
    {
        $templates = ContentTemplate::where('business_id', $businessId)
            ->whereNull('analyzed_at')
            ->orderByDesc('engagement_rate')
            ->limit($limit)
            ->get();

        $results = [];

        foreach ($templates as $template) {
            $results[$template->id] = $this->analyzePost($template);

            // Rate limiting - 1 request per second
            usleep(500000);
        }

        return $results;
    }

    /**
     * Style patterns ni aniqlash
     */
    public function analyzeStylePatterns(string $businessId): array
    {
        $templates = ContentTemplate::where('business_id', $businessId)
            ->whereNotNull('ai_analysis')
            ->where('is_active', true)
            ->get();

        if ($templates->isEmpty()) {
            return $this->getDefaultPatterns();
        }

        // Tone distribution
        $tones = $templates->pluck('ai_analysis.tone')->filter()->countBy();

        // Hook types
        $hookTypes = $templates->pluck('ai_analysis.hook_type')->filter()->countBy();

        // Sentiment
        $sentiments = $templates->pluck('ai_analysis.sentiment')->filter()->countBy();

        // CTA types
        $ctaTypes = $templates->pluck('ai_analysis.cta_type')->filter()->countBy();

        // Average post length
        $avgLength = $templates->avg(fn($t) => mb_strlen($t->content_cleaned ?? $t->content));

        // Common topics from top performers
        $topPerformers = $templates->where('is_top_performer', true);
        $topics = $topPerformers->pluck('ai_analysis.topics')->flatten()->filter()->countBy();

        // Emoji analysis
        $emojiPattern = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';
        $allEmojis = [];
        foreach ($templates as $t) {
            preg_match_all($emojiPattern, $t->content, $matches);
            $allEmojis = array_merge($allEmojis, $matches[0]);
        }
        $emojiCounts = array_count_values($allEmojis);
        arsort($emojiCounts);

        // Hashtag analysis
        $allHashtags = $templates->pluck('hashtags')->flatten()->filter()->countBy();

        return [
            'dominant_tone' => $tones->sortDesc()->keys()->first() ?? 'professional',
            'tone_distribution' => $tones->toArray(),
            'hook_types' => $hookTypes->sortDesc()->toArray(),
            'sentiments' => $sentiments->toArray(),
            'cta_types' => $ctaTypes->toArray(),
            'avg_post_length' => round($avgLength),
            'top_topics' => $topics->sortDesc()->take(10)->toArray(),
            'common_emojis' => array_slice(array_keys($emojiCounts), 0, 10),
            'emoji_frequency' => $this->calculateEmojiFrequency(count($allEmojis), $templates->count()),
            'common_hashtags' => $allHashtags->sortDesc()->take(20)->keys()->toArray(),
            'avg_hashtag_count' => round($templates->avg(fn($t) => count($t->hashtags ?? []))),
            'analyzed_count' => $templates->count(),
            'top_performers_count' => $topPerformers->count(),
        ];
    }

    /**
     * Analysis prompt yaratish
     */
    protected function buildAnalysisPrompt(string $content): string
    {
        return <<<PROMPT
Quyidagi ijtimoiy tarmoq postini tahlil qilib, JSON formatda javob ber:

POST:
---
{$content}
---

Tahlil qilish kerak:
1. tone: post toni (formal/casual/professional/friendly/playful)
2. sentiment: his-tuyg'u (positive/negative/neutral)
3. hook_type: boshlanish turi (question/statistic/story/statement/emoji/provocative)
4. cta_type: call-to-action turi (direct/soft/urgent/none)
5. topics: asosiy mavzular ro'yxati (3-5 ta)
6. key_phrases: muhim iboralar (3-5 ta)
7. language_style: til uslubi (simple/technical/creative/persuasive)

Faqat JSON formatda javob ber, boshqa hech narsa yozma:
{"tone": "...", "sentiment": "...", "hook_type": "...", "cta_type": "...", "topics": [...], "key_phrases": [...], "language_style": "..."}
PROMPT;
    }

    /**
     * Claude API ga so'rov yuborish
     */
    protected function callClaudeApi(string $prompt, int $maxTokens = 500): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(30)->post($this->apiUrl, [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Claude API error: ' . $response->body());
        }

        $data = $response->json();

        return $data['content'][0]['text'] ?? '';
    }

    /**
     * Analysis javobini parse qilish
     */
    protected function parseAnalysisResponse(string $response): array
    {
        // JSON ni topish
        preg_match('/\{[^{}]*\}/s', $response, $matches);

        if (empty($matches[0])) {
            return [];
        }

        $decoded = json_decode($matches[0], true);

        return $decoded ?? [];
    }

    /**
     * Emoji chastotasini aniqlash
     */
    protected function calculateEmojiFrequency(int $totalEmojis, int $postsCount): string
    {
        if ($postsCount === 0) {
            return 'medium';
        }

        $avgPerPost = $totalEmojis / $postsCount;

        if ($avgPerPost < 1) return 'low';
        if ($avgPerPost < 3) return 'medium';
        return 'high';
    }

    /**
     * Default patterns
     */
    protected function getDefaultPatterns(): array
    {
        return [
            'dominant_tone' => 'professional',
            'tone_distribution' => [],
            'hook_types' => [],
            'sentiments' => [],
            'cta_types' => [],
            'avg_post_length' => 200,
            'top_topics' => [],
            'common_emojis' => [],
            'emoji_frequency' => 'medium',
            'common_hashtags' => [],
            'avg_hashtag_count' => 5,
            'analyzed_count' => 0,
            'top_performers_count' => 0,
        ];
    }
}
