<?php

namespace App\Services\ContentAI;

use App\Models\ContentStyleGuide;
use App\Models\ContentTemplate;
use Illuminate\Support\Facades\Log;

/**
 * ContentStyleGuideService - Brand style guide boshqaruvi
 *
 * Bu service mavjud kontentni tahlil qilib,
 * biznes uchun avtomatik style guide yaratadi.
 */
class ContentStyleGuideService
{
    protected ContentAnalyzerService $analyzer;

    public function __construct(ContentAnalyzerService $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Style guide ni yaratish yoki yangilash
     */
    public function buildStyleGuide(string $businessId): ContentStyleGuide
    {
        // Mavjud guide ni olish yoki yangi yaratish
        $guide = ContentStyleGuide::getOrCreate($businessId);

        // Postlarni tahlil qilish
        $patterns = $this->analyzer->analyzeStylePatterns($businessId);

        if ($patterns['analyzed_count'] === 0) {
            Log::info('ContentStyleGuideService: No posts to analyze', [
                'business_id' => $businessId,
            ]);

            return $guide;
        }

        // Style guide ni yangilash
        $guide->update([
            'tone' => $this->mapTone($patterns['dominant_tone']),
            'language_style' => $this->determineLanguageStyle($patterns),
            'emoji_frequency' => $patterns['emoji_frequency'],
            'common_emojis' => $patterns['common_emojis'],
            'avg_post_length' => $patterns['avg_post_length'],
            'min_post_length' => max(50, $patterns['avg_post_length'] - 100),
            'max_post_length' => $patterns['avg_post_length'] + 200,
            'common_hashtags' => $patterns['common_hashtags'],
            'avg_hashtag_count' => $patterns['avg_hashtag_count'],
            'cta_style' => $this->determineCTAStyle($patterns['cta_types']),
            'content_pillars' => array_keys($patterns['top_topics']),
            'top_performing_topics' => $patterns['top_topics'],
            'analyzed_posts_count' => $patterns['analyzed_count'],
            'last_analyzed_at' => now(),
        ]);

        // CTA patterns ni to'plash
        $ctaPatterns = $this->collectCTAPatterns($businessId);
        if (!empty($ctaPatterns)) {
            $guide->update(['cta_patterns' => $ctaPatterns]);
        }

        Log::info('ContentStyleGuideService: Style guide updated', [
            'business_id' => $businessId,
            'analyzed_posts' => $patterns['analyzed_count'],
        ]);

        return $guide->fresh();
    }

    /**
     * Style guide ni qo'lda sozlash
     */
    public function updateStyleGuide(string $businessId, array $settings): ContentStyleGuide
    {
        $guide = ContentStyleGuide::getOrCreate($businessId);

        $allowedFields = [
            'tone',
            'language_style',
            'emoji_frequency',
            'common_emojis',
            'avg_post_length',
            'min_post_length',
            'max_post_length',
            'common_hashtags',
            'avg_hashtag_count',
            'use_branded_hashtags',
            'cta_patterns',
            'cta_style',
            'best_posting_times',
            'content_pillars',
            'channel_specific_settings',
            'ai_model',
            'creativity_level',
        ];

        $filtered = array_intersect_key($settings, array_flip($allowedFields));

        $guide->update($filtered);

        return $guide->fresh();
    }

    /**
     * Kanal uchun sozlamalarni yangilash
     */
    public function updateChannelSettings(string $businessId, string $channel, array $settings): ContentStyleGuide
    {
        $guide = ContentStyleGuide::getOrCreate($businessId);

        $currentSettings = $guide->channel_specific_settings ?? [];
        $currentSettings[$channel] = array_merge($currentSettings[$channel] ?? [], $settings);

        $guide->update(['channel_specific_settings' => $currentSettings]);

        return $guide->fresh();
    }

    /**
     * Best posting times ni aniqlash
     */
    public function analyzeBestPostingTimes(string $businessId): array
    {
        $templates = ContentTemplate::where('business_id', $businessId)
            ->whereNotNull('posted_at')
            ->where('engagement_rate', '>', 0)
            ->get();

        if ($templates->isEmpty()) {
            return $this->getDefaultPostingTimes();
        }

        $timePerformance = [];

        foreach ($templates as $template) {
            $day = strtolower($template->posted_at->format('l'));
            $hour = $template->posted_at->format('H:00');

            if (!isset($timePerformance[$day])) {
                $timePerformance[$day] = [];
            }

            if (!isset($timePerformance[$day][$hour])) {
                $timePerformance[$day][$hour] = [
                    'count' => 0,
                    'total_engagement' => 0,
                ];
            }

            $timePerformance[$day][$hour]['count']++;
            $timePerformance[$day][$hour]['total_engagement'] += $template->engagement_rate;
        }

        // Har bir kun uchun eng yaxshi vaqtlarni topish
        $bestTimes = [];

        foreach ($timePerformance as $day => $hours) {
            $hourScores = [];

            foreach ($hours as $hour => $data) {
                if ($data['count'] >= 2) {
                    $hourScores[$hour] = $data['total_engagement'] / $data['count'];
                }
            }

            arsort($hourScores);
            $bestTimes[$day] = array_slice(array_keys($hourScores), 0, 3);
        }

        return $bestTimes;
    }

    /**
     * Content pillars (mavzular) ni tavsiya qilish
     */
    public function suggestContentPillars(string $businessId): array
    {
        $templates = ContentTemplate::where('business_id', $businessId)
            ->whereNotNull('ai_analysis')
            ->where('is_top_performer', true)
            ->get();

        if ($templates->isEmpty()) {
            return $this->getDefaultContentPillars();
        }

        $allTopics = $templates->pluck('ai_analysis.topics')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc();

        return $allTopics->take(5)->keys()->toArray();
    }

    /**
     * Style guide summary (AI prompt uchun)
     */
    public function getPromptContext(string $businessId): string
    {
        $guide = ContentStyleGuide::getOrCreate($businessId);

        return $guide->buildPromptContext();
    }

    /**
     * Tone mapping
     */
    protected function mapTone(string $dominantTone): string
    {
        $mapping = [
            'formal' => 'formal',
            'casual' => 'casual',
            'professional' => 'professional',
            'friendly' => 'friendly',
            'playful' => 'playful',
            'humorous' => 'playful',
            'serious' => 'formal',
            'conversational' => 'casual',
        ];

        return $mapping[$dominantTone] ?? 'professional';
    }

    /**
     * Language style aniqlash
     */
    protected function determineLanguageStyle(array $patterns): string
    {
        // Agar topics texnik so'zlar bo'lsa - technical
        $techKeywords = ['api', 'code', 'software', 'tech', 'digital', 'data'];
        $topics = array_keys($patterns['top_topics']);

        foreach ($topics as $topic) {
            foreach ($techKeywords as $keyword) {
                if (stripos($topic, $keyword) !== false) {
                    return 'technical';
                }
            }
        }

        // Hook type ga qarab
        $hookTypes = $patterns['hook_types'];
        if (isset($hookTypes['story']) && $hookTypes['story'] > 3) {
            return 'creative';
        }

        if (isset($hookTypes['statistic']) && $hookTypes['statistic'] > 3) {
            return 'persuasive';
        }

        return 'simple';
    }

    /**
     * CTA style aniqlash
     */
    protected function determineCTAStyle(array $ctaTypes): string
    {
        if (empty($ctaTypes)) {
            return 'direct';
        }

        arsort($ctaTypes);
        $dominant = array_key_first($ctaTypes);

        return in_array($dominant, ['direct', 'soft', 'urgent', 'none']) ? $dominant : 'direct';
    }

    /**
     * CTA patterns to'plash
     */
    protected function collectCTAPatterns(string $businessId): array
    {
        $templates = ContentTemplate::where('business_id', $businessId)
            ->where('is_top_performer', true)
            ->whereNotNull('content')
            ->get();

        $patterns = [];

        foreach ($templates as $template) {
            $content = $template->content;

            // Oxirgi gapni olish (CTA odatda oxirida)
            $sentences = preg_split('/[.!?]+/', $content);
            $lastSentence = trim(end($sentences));

            if (mb_strlen($lastSentence) > 10 && mb_strlen($lastSentence) < 100) {
                // CTA kalit so'zlarni tekshirish
                $ctaKeywords = ['bog\'lan', 'yozing', 'qo\'ng\'iroq', 'bosing', 'kiring', 'ko\'ring', 'ulashing', 'like', 'follow', 'save'];

                foreach ($ctaKeywords as $keyword) {
                    if (mb_stripos($lastSentence, $keyword) !== false) {
                        $patterns[] = $lastSentence;
                        break;
                    }
                }
            }
        }

        return array_unique(array_slice($patterns, 0, 10));
    }

    /**
     * Default posting times
     */
    protected function getDefaultPostingTimes(): array
    {
        return [
            'monday' => ['09:00', '13:00', '18:00'],
            'tuesday' => ['09:00', '13:00', '18:00'],
            'wednesday' => ['09:00', '13:00', '18:00'],
            'thursday' => ['09:00', '13:00', '18:00'],
            'friday' => ['09:00', '13:00', '17:00'],
            'saturday' => ['10:00', '14:00', '19:00'],
            'sunday' => ['10:00', '14:00', '19:00'],
        ];
    }

    /**
     * Default content pillars
     */
    protected function getDefaultContentPillars(): array
    {
        return ['ta\'lim', 'ilhomlantirish', 'mahsulot', 'mijoz hikoyalari', 'tips'];
    }
}
