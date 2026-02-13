<?php

namespace App\Providers;

use App\Services\ClaudeAIService;
use App\Services\ContentAI\ContentAIEnrichmentService;
use App\Services\ContentAI\ContentPerformanceFeedback;
use App\Services\ContentAI\ContentPlanEngine;
use App\Services\ContentAI\CrossBusinessLearningService;
use App\Services\ContentAI\IndustryContentLibrary;
use App\Services\ContentAI\InstagramAlgorithmEngine;
use App\Services\ContentAI\SurveyContentBridge;
use Illuminate\Support\ServiceProvider;

class ContentAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClaudeAIService::class);

        $this->app->singleton(ContentAIEnrichmentService::class, function ($app) {
            return new ContentAIEnrichmentService(
                $app->make(ClaudeAIService::class),
            );
        });

        $this->app->singleton(ContentPlanEngine::class, function ($app) {
            return new ContentPlanEngine(
                $app->make(CrossBusinessLearningService::class),
                $app->make(SurveyContentBridge::class),
                $app->make(InstagramAlgorithmEngine::class),
                $app->make(ContentPerformanceFeedback::class),
                $app->make(IndustryContentLibrary::class),
                $app->make(ContentAIEnrichmentService::class),
            );
        });
    }
}
