<?php

namespace App\Observers;

use App\Models\ContentGeneration;
use App\Models\ContentIdeaUsage;
use Illuminate\Support\Facades\Log;

class ContentGenerationObserver
{
    /**
     * Handle the ContentGeneration "updated" event.
     * Kontent nashr qilinganda yoki metrikalar kelganda g'oya statistikasini yangilash.
     */
    public function updated(ContentGeneration $generation): void
    {
        // Faqat status o'zgarganda va published bo'lganda
        if ($generation->wasChanged('status') && $generation->status === 'published') {
            $this->syncToIdeaUsage($generation);
        }

        // Engagement metrikalar kelganda
        if ($generation->wasChanged(['post_engagement_rate', 'post_likes', 'post_comments'])) {
            $this->updateIdeaMetrics($generation);
        }
    }

    /**
     * Sync generation to idea usage when published.
     */
    protected function syncToIdeaUsage(ContentGeneration $generation): void
    {
        // Bu generatsiya qaysi g'oyadan yaratilgan?
        $ideaUsage = ContentIdeaUsage::where('content_generation_id', $generation->id)->first();

        if (!$ideaUsage) {
            // Agar ideaUsage yo'q bo'lsa, demak bu g'oyadan emas, to'g'ridan-to'g'ri yaratilgan
            return;
        }

        // Usage ni published qilish
        $ideaUsage->update([
            'outcome' => 'published',
        ]);

        Log::info("ContentGeneration {$generation->id} published, IdeaUsage {$ideaUsage->id} synced");
    }

    /**
     * Update idea metrics when engagement data comes in.
     */
    protected function updateIdeaMetrics(ContentGeneration $generation): void
    {
        $ideaUsage = ContentIdeaUsage::where('content_generation_id', $generation->id)->first();

        if (!$ideaUsage) {
            return;
        }

        // Metrikalarni yangilash
        $ideaUsage->update([
            'engagement_rate' => $generation->post_engagement_rate,
            'likes_count' => $generation->post_likes,
            'comments_count' => $generation->post_comments,
        ]);

        // G'oya quality score ni qayta hisoblash
        if ($ideaUsage->idea) {
            $ideaUsage->idea->updateQualityScore();
        }

        Log::info("ContentGeneration {$generation->id} metrics synced to IdeaUsage {$ideaUsage->id}");
    }
}
