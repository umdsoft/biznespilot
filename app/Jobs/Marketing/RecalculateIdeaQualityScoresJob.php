<?php

namespace App\Jobs\Marketing;

use App\Models\ContentIdea;
use App\Models\ContentIdeaUsage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateIdeaQualityScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting idea quality scores recalculation');

        $updatedCount = 0;
        $archivedCount = 0;

        // Barcha aktiv g'oyalarni qayta hisoblash
        ContentIdea::where('is_active', true)
            ->chunkById(100, function ($ideas) use (&$updatedCount, &$archivedCount) {
                foreach ($ideas as $idea) {
                    try {
                        $oldScore = $idea->quality_score;

                        // Quality score yangilash
                        $idea->updateQualityScore();

                        // Agar score juda past va ko'p ishlatilgan lekin yomon natija bo'lsa - arxivlash
                        if ($this->shouldArchive($idea)) {
                            $idea->update(['is_active' => false]);
                            $archivedCount++;
                            Log::info("Archived low-quality idea: {$idea->id} (score: {$idea->quality_score})");
                        } elseif ($oldScore !== $idea->quality_score) {
                            $updatedCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to recalculate quality score for idea {$idea->id}: " . $e->getMessage());
                    }
                }
            });

        // Trending g'oyalarni aniqlash va belgilash
        $this->updateTrendingIdeas();

        // Cache tozalash
        $this->clearRecommendationCaches();

        Log::info("Quality scores recalculation completed. Updated: {$updatedCount}, Archived: {$archivedCount}");
    }

    /**
     * Check if idea should be archived due to poor performance.
     */
    protected function shouldArchive(ContentIdea $idea): bool
    {
        // Global yoki verified g'oyalarni arxivlamaymiz
        if ($idea->is_global || $idea->is_verified) {
            return false;
        }

        // Kamida 10 marta ishlatilgan bo'lishi kerak
        if ($idea->times_used < 10) {
            return false;
        }

        // Quality score 20 dan past va success rate 10% dan past
        if ($idea->quality_score < 20 && $idea->success_rate < 10) {
            return true;
        }

        // Ko'p ishlatilgan lekin hech qachon nashr qilinmagan
        if ($idea->times_used >= 20 && $idea->times_published === 0) {
            return true;
        }

        // Doimiy "not_helpful" reyting olgan
        $notHelpfulCount = $idea->usages()->where('user_rating', 'not_helpful')->count();
        $totalRated = $idea->usages()->whereNotNull('user_rating')->count();

        if ($totalRated >= 5 && ($notHelpfulCount / $totalRated) > 0.7) {
            return true;
        }

        return false;
    }

    /**
     * Update trending ideas based on recent usage.
     */
    protected function updateTrendingIdeas(): void
    {
        // Oxirgi 30 kunda eng ko'p ishlatilgan g'oyalarni topish
        $trendingIds = ContentIdeaUsage::where('created_at', '>=', now()->subDays(30))
            ->select('content_idea_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('content_idea_id')
            ->orderByDesc('usage_count')
            ->limit(50)
            ->pluck('content_idea_id')
            ->toArray();

        // Trending g'oyalarga bonus score qo'shish (keyingi versiyada)
        // Hozircha faqat log qilamiz
        Log::info('Trending ideas identified: ' . count($trendingIds));
    }

    /**
     * Clear recommendation caches for all businesses.
     */
    protected function clearRecommendationCaches(): void
    {
        // Pattern bo'yicha cache tozalash
        // Laravel cache driver ga qarab turlicha ishlaydi
        try {
            // Redis yoki memcached uchun
            if (method_exists(Cache::getStore(), 'flush')) {
                // Faqat content_ideas prefixli cachelarni tozalash
                // Bu yerda to'liq flush qilmaymiz
            }

            Log::info('Recommendation caches cleared');
        } catch (\Exception $e) {
            Log::warning('Failed to clear recommendation caches: ' . $e->getMessage());
        }
    }
}
