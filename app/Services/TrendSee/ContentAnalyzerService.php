<?php

declare(strict_types=1);

namespace App\Services\TrendSee;

use App\Models\ViralContent;
use App\Services\ClaudeAIService;
use Illuminate\Support\Facades\Log;

/**
 * ContentAnalyzerService - "The Analyst"
 *
 * Analyzes viral Instagram content using AI to extract:
 * - Hook effectiveness (what grabs attention)
 * - Emotional triggers (FOMO, Curiosity, Greed, Fear)
 * - Replication strategy (how businesses can adapt)
 * - Hook score (1-10 viral potential)
 */
class ContentAnalyzerService
{
    private ClaudeAIService $ai;

    public function __construct(ClaudeAIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Analyze a single viral post.
     *
     * @param array $postData Post data from RocketApiService
     * @return array Analysis result with hook_score, summary, psychology, replication_tip
     */
    public function analyzePost(array $postData): array
    {
        $caption = $postData['caption'] ?? '';
        $playCount = $postData['play_count'] ?? 0;
        $likeCount = $postData['like_count'] ?? 0;
        $commentCount = $postData['comment_count'] ?? 0;
        $musicTitle = $postData['music_title'] ?? null;
        $musicArtist = $postData['music_artist'] ?? null;
        $niche = $postData['niche'] ?? 'general';

        // Build context for AI
        $context = $this->buildAnalysisContext($postData);

        $systemPrompt = <<<PROMPT
Sen ijtimoiy tarmoqlarda viral kontentni tahlil qiluvchi ekspertsan.
Sening vazifang - nima uchun bu Instagram Reels viral bo'lganini aniqlash va bizneslar uchun foydali tavsiyalar berish.

Har doim O'ZBEK tilida javob ber.
Javobni qisqa va aniq qil.
Faqat JSON formatda javob ber.
PROMPT;

        $prompt = <<<PROMPT
Quyidagi viral Instagram Reels kontentini tahlil qil:

{$context}

Tahlil qil va JSON formatda javob ber:
{
    "hook_score": [1-10 ball, viral potensial],
    "hook_analysis": "Nima bilan e'tiborni tortadi? (2-3 jumla)",
    "psychology": {
        "primary_trigger": "[FOMO/CURIOSITY/GREED/FEAR/JOY/ANGER]",
        "explanation": "Qaysi hissiyotni qo'zg'atadi va qanday?"
    },
    "viral_factors": ["omil1", "omil2", "omil3"],
    "replication_tip": "Biznes bu usuldan qanday foydalanishi mumkin? (aniq maslahat)",
    "summary": "Video haqida 1 jumlalik xulosa"
}
PROMPT;

        try {
            $response = $this->ai->complete(
                prompt: $prompt,
                systemPrompt: $systemPrompt,
                maxTokens: 800,
                useCache: true,
                usePremiumModel: false // Use haiku for cost efficiency
            );

            $analysis = $this->parseJsonResponse($response);

            if (empty($analysis)) {
                Log::warning('ContentAnalyzer: Failed to parse AI response', [
                    'response' => $response,
                ]);
                return $this->getFallbackAnalysis($postData);
            }

            // Ensure hook_score is numeric
            $analysis['hook_score'] = (int) ($analysis['hook_score'] ?? 5);

            Log::info('ContentAnalyzer: Post analyzed successfully', [
                'platform_id' => $postData['platform_id'] ?? 'unknown',
                'hook_score' => $analysis['hook_score'],
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('ContentAnalyzer: Analysis failed', [
                'error' => $e->getMessage(),
                'platform_id' => $postData['platform_id'] ?? 'unknown',
            ]);

            return $this->getFallbackAnalysis($postData);
        }
    }

    /**
     * Analyze and save to ViralContent model.
     */
    public function analyzeAndSave(ViralContent $content): ViralContent
    {
        $postData = [
            'platform_id' => $content->platform_id,
            'caption' => $content->caption,
            'play_count' => $content->play_count,
            'like_count' => $content->like_count,
            'comment_count' => $content->comment_count,
            'music_title' => $content->music_title,
            'music_artist' => $content->music_artist,
            'niche' => $content->niche,
            'thumbnail_url' => $content->thumbnail_url,
        ];

        $analysis = $this->analyzePost($postData);

        $content->update([
            'ai_analysis_json' => $analysis,
            'hook_score' => $analysis['hook_score'] ?? null,
            'ai_summary' => $analysis['summary'] ?? null,
            'is_processed' => true,
            'analyzed_at' => now(),
        ]);

        return $content->fresh();
    }

    /**
     * Batch analyze multiple posts.
     *
     * @param array $posts Array of post data
     * @return array Array of analysis results keyed by platform_id
     */
    public function batchAnalyze(array $posts): array
    {
        $results = [];

        foreach ($posts as $post) {
            $platformId = $post['platform_id'] ?? null;

            if (!$platformId) {
                continue;
            }

            // Add delay to avoid rate limits
            usleep(500000); // 0.5 second delay between calls

            $results[$platformId] = $this->analyzePost($post);
        }

        return $results;
    }

    /**
     * Build context string for AI analysis.
     */
    private function buildAnalysisContext(array $postData): string
    {
        $parts = [];

        // Caption
        $caption = $postData['caption'] ?? '';
        if (!empty($caption)) {
            $parts[] = "CAPTION:\n" . mb_substr($caption, 0, 500);
        }

        // Metrics
        $playCount = number_format($postData['play_count'] ?? 0);
        $likeCount = number_format($postData['like_count'] ?? 0);
        $commentCount = number_format($postData['comment_count'] ?? 0);

        $parts[] = "METRIKA:\n- Ko'rishlar: {$playCount}\n- Layklar: {$likeCount}\n- Izohlar: {$commentCount}";

        // Music
        if (!empty($postData['music_title'])) {
            $musicInfo = "MUSIQA: " . $postData['music_title'];
            if (!empty($postData['music_artist'])) {
                $musicInfo .= " - " . $postData['music_artist'];
            }
            $parts[] = $musicInfo;
        }

        // Niche
        if (!empty($postData['niche'])) {
            $parts[] = "NISHA: #" . $postData['niche'];
        }

        // Engagement rate calculation
        if (($postData['play_count'] ?? 0) > 0) {
            $engagementRate = (($postData['like_count'] ?? 0) + ($postData['comment_count'] ?? 0)) / $postData['play_count'] * 100;
            $parts[] = "ENGAGEMENT RATE: " . number_format($engagementRate, 2) . "%";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Parse JSON from AI response.
     */
    private function parseJsonResponse(string $response): array
    {
        // Try to extract JSON from response
        $response = trim($response);

        // Remove markdown code blocks if present
        $response = preg_replace('/^```json?\s*/i', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);

        // Try direct JSON parse
        $decoded = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try to find JSON in response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Get fallback analysis when AI fails.
     */
    private function getFallbackAnalysis(array $postData): array
    {
        $playCount = $postData['play_count'] ?? 0;

        // Calculate basic hook score based on metrics
        $hookScore = match (true) {
            $playCount >= 1000000 => 9,
            $playCount >= 500000 => 8,
            $playCount >= 200000 => 7,
            $playCount >= 100000 => 6,
            $playCount >= 50000 => 5,
            default => 4,
        };

        return [
            'hook_score' => $hookScore,
            'hook_analysis' => 'Tahlil vaqtinchalik mavjud emas.',
            'psychology' => [
                'primary_trigger' => 'UNKNOWN',
                'explanation' => 'AI tahlili amalga oshmadi.',
            ],
            'viral_factors' => ['yuqori ko\'rishlar'],
            'replication_tip' => 'Kontentni o\'rganing va o\'z biznesingizga moslashtiring.',
            'summary' => 'Viral kontent - ' . number_format($playCount) . ' ko\'rish.',
        ];
    }

    /**
     * Generate summary for Telegram notification.
     */
    public function generateAlertSummary(ViralContent $content): string
    {
        $analysis = $content->ai_analysis_json ?? [];

        $summary = $analysis['summary'] ?? $content->caption_summary;
        $hookAnalysis = $analysis['hook_analysis'] ?? '';
        $psychology = $analysis['psychology']['primary_trigger'] ?? 'UNKNOWN';
        $tip = $analysis['replication_tip'] ?? '';

        $parts = [];

        if (!empty($summary)) {
            $parts[] = $summary;
        }

        if (!empty($hookAnalysis)) {
            $parts[] = $hookAnalysis;
        }

        return implode(' ', $parts) ?: 'Viral kontent topildi!';
    }
}
