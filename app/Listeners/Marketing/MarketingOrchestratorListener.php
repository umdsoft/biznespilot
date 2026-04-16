<?php

namespace App\Listeners\Marketing;

use App\Events\Marketing\CampaignStarted;
use App\Events\Marketing\CompetitorActivityDetected;
use App\Events\Marketing\ContentEngagementUpdated;
use App\Events\Marketing\ContentPublished;
use App\Events\Marketing\LowPerformanceDetected;
use App\Services\Marketing\Orchestrator\MarketingOrchestrator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Marketing eventlarini tinglab Orchestrator keshini tozalaydi va trigger qiladi.
 *
 * Bu listener har bir marketing qismini (content, competitor, campaign) boshqa qismlar bilan
 * SINXRONLAYDI — biror qismda o'zgarish bo'lsa, butun tizim xabardor bo'ladi.
 */
class MarketingOrchestratorListener
{
    public function __construct(
        private MarketingOrchestrator $orchestrator,
    ) {}

    public function subscribe(Dispatcher $events): array
    {
        return [
            ContentPublished::class => 'handleContentPublished',
            ContentEngagementUpdated::class => 'handleEngagementUpdated',
            CompetitorActivityDetected::class => 'handleCompetitorActivity',
            CampaignStarted::class => 'handleCampaignStarted',
            LowPerformanceDetected::class => 'handleLowPerformance',
        ];
    }

    /**
     * Kontent publish qilindi — snapshot yangilanishi kerak
     */
    public function handleContentPublished(ContentPublished $event): void
    {
        $this->orchestrator->invalidate($event->businessId);

        Log::info('MarketingOrchestrator: content publish event', [
            'content_id' => $event->content->id,
            'business_id' => $event->businessId,
        ]);
    }

    /**
     * Engagement yangilandi
     */
    public function handleEngagementUpdated(ContentEngagementUpdated $event): void
    {
        $this->orchestrator->invalidate($event->businessId);

        // Agar katta o'sish bo'lsa — "top performer" kabi saqlash
        if ($event->newEngagement > $event->oldEngagement * 2 && $event->newEngagement > 5) {
            Log::info('Top performer detected', [
                'content_id' => $event->content->id,
                'engagement' => $event->newEngagement,
            ]);
            // Keyingi fazalarda: ContentIdea ga qo'shish
        }
    }

    /**
     * Raqobatchi faoliyati aniqlandi
     */
    public function handleCompetitorActivity(CompetitorActivityDetected $event): void
    {
        $this->orchestrator->invalidate($event->businessId);

        Log::info('Competitor activity detected', [
            'competitor_id' => $event->competitorId,
            'activity_type' => $event->activityType,
        ]);
    }

    /**
     * Kampaniya boshlandi
     */
    public function handleCampaignStarted(CampaignStarted $event): void
    {
        $this->orchestrator->invalidate($event->businessId);

        Log::info('Campaign started', [
            'campaign_id' => $event->campaignId,
            'budget' => $event->budget,
        ]);
    }

    /**
     * Past samaradorlik aniqlandi
     */
    public function handleLowPerformance(LowPerformanceDetected $event): void
    {
        $this->orchestrator->invalidate($event->businessId);

        Log::warning('Low performance detected', [
            'business_id' => $event->businessId,
            'metric' => $event->metricType,
            'value' => $event->currentValue,
            'threshold' => $event->threshold,
        ]);
    }
}
