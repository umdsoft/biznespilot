<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\ContentTemplate;
use App\Services\ContentAI\ContentAnalyzerService;
use App\Services\ContentAI\ContentStyleGuideService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeContentTemplatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ContentAnalyzerService $analyzer,
        ContentStyleGuideService $styleGuideService
    ): void {
        // Agar business_id berilgan bo'lsa faqat shu biznesni
        if ($this->businessId) {
            $this->analyzeForBusiness($this->businessId, $analyzer, $styleGuideService);
            return;
        }

        // Barcha aktiv bizneslarni tahlil qilish
        $businesses = Business::whereHas('contentTemplates', function ($query) {
            $query->whereNull('analyzed_at')
                ->orWhere('analyzed_at', '<', now()->subDays(7));
        })->get();

        foreach ($businesses as $business) {
            try {
                $this->analyzeForBusiness($business->id, $analyzer, $styleGuideService);
            } catch (\Exception $e) {
                Log::error("Content analysis failed for business {$business->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Analyze templates for a specific business.
     */
    protected function analyzeForBusiness(
        string $businessId,
        ContentAnalyzerService $analyzer,
        ContentStyleGuideService $styleGuideService
    ): void {
        // Tahlil qilinmagan templatelarni topish
        $templates = ContentTemplate::where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereNull('analyzed_at')
                    ->orWhere('analyzed_at', '<', now()->subDays(7));
            })
            ->orderByDesc('performance_score')
            ->limit(20)
            ->get();

        if ($templates->isEmpty()) {
            Log::info("No templates to analyze for business {$businessId}");
            return;
        }

        $analyzedCount = 0;

        foreach ($templates as $template) {
            try {
                $analyzer->analyzePost($template);
                $analyzedCount++;

                // API rate limiting - har 10 ta so'rovdan keyin 1 soniya kutish
                if ($analyzedCount % 10 === 0) {
                    sleep(1);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to analyze template {$template->id}: " . $e->getMessage());
            }
        }

        // Style Guide yangilash
        if ($analyzedCount > 0) {
            try {
                $styleGuideService->buildStyleGuide($businessId);
                Log::info("Style guide updated for business {$businessId} after analyzing {$analyzedCount} templates");
            } catch (\Exception $e) {
                Log::warning("Failed to update style guide for business {$businessId}: " . $e->getMessage());
            }
        }
    }
}
