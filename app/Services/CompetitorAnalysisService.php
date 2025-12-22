<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorMetric;
use App\Models\CompetitorActivity;
use Carbon\Carbon;

class CompetitorAnalysisService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Generate SWOT analysis for a competitor
     */
    public function generateSWOTAnalysis(Competitor $competitor): array
    {
        // Gather competitor data
        $data = $this->gatherCompetitorData($competitor);

        // Build prompt for Claude AI
        $prompt = $this->buildSWOTPrompt($competitor, $data);

        // Get AI analysis
        $response = $this->claudeAI->complete($prompt, null, 4096);

        // Parse response
        return $this->parseSWOTResponse($response);
    }

    /**
     * Gather all available data about competitor
     */
    protected function gatherCompetitorData(Competitor $competitor): array
    {
        $data = [
            'basic_info' => [
                'name' => $competitor->name,
                'description' => $competitor->description,
                'industry' => $competitor->industry,
                'location' => $competitor->location,
                'threat_level' => $competitor->threat_level,
            ],
            'social_presence' => [],
            'metrics' => [],
            'recent_activities' => [],
            'trends' => [],
        ];

        // Social media presence
        if ($competitor->instagram_handle)
            $data['social_presence']['instagram'] = $competitor->instagram_handle;
        if ($competitor->telegram_handle)
            $data['social_presence']['telegram'] = $competitor->telegram_handle;
        if ($competitor->facebook_page)
            $data['social_presence']['facebook'] = $competitor->facebook_page;
        if ($competitor->tiktok_handle)
            $data['social_presence']['tiktok'] = $competitor->tiktok_handle;

        // Latest metrics
        $latestMetric = CompetitorMetric::where('competitor_id', $competitor->id)
            ->latest('date')
            ->first();

        if ($latestMetric) {
            $data['metrics'] = [
                'instagram_followers' => $latestMetric->instagram_followers,
                'instagram_engagement_rate' => $latestMetric->instagram_engagement_rate,
                'telegram_members' => $latestMetric->telegram_members,
                'follower_growth_rate' => $latestMetric->follower_growth_rate,
                'engagement_growth_rate' => $latestMetric->engagement_growth_rate,
            ];
        }

        // Metrics trend (last 30 days)
        $metricsHistory = CompetitorMetric::where('competitor_id', $competitor->id)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date', 'desc')
            ->get();

        if ($metricsHistory->count() > 1) {
            $data['trends'] = $this->analyzeTrends($metricsHistory);
        }

        // Recent activities
        $recentActivities = CompetitorActivity::where('competitor_id', $competitor->id)
            ->where('activity_date', '>=', now()->subDays(30))
            ->orderBy('activity_date', 'desc')
            ->limit(10)
            ->get();

        $data['recent_activities'] = $recentActivities->map(function ($activity) {
            return [
                'type' => $activity->type,
                'platform' => $activity->platform,
                'title' => $activity->title,
                'engagement' => $activity->likes + $activity->comments + $activity->shares,
                'is_viral' => $activity->is_viral,
                'date' => $activity->activity_date->format('Y-m-d'),
            ];
        })->toArray();

        return $data;
    }

    /**
     * Analyze trends from metrics history
     */
    protected function analyzeTrends($metrics): array
    {
        $trends = [
            'follower_trend' => 'stable',
            'engagement_trend' => 'stable',
            'activity_level' => 'moderate',
        ];

        $growthRates = $metrics->pluck('follower_growth_rate')->filter()->toArray();
        if (count($growthRates) > 0) {
            $avgGrowth = array_sum($growthRates) / count($growthRates);
            $trends['follower_trend'] = $avgGrowth > 5 ? 'growing' : ($avgGrowth < -5 ? 'declining' : 'stable');
        }

        $engagementRates = $metrics->pluck('engagement_growth_rate')->filter()->toArray();
        if (count($engagementRates) > 0) {
            $avgEngagement = array_sum($engagementRates) / count($engagementRates);
            $trends['engagement_trend'] = $avgEngagement > 10 ? 'increasing' : ($avgEngagement < -10 ? 'decreasing' : 'stable');
        }

        return $trends;
    }

    /**
     * Build SWOT analysis prompt for Claude AI
     */
    protected function buildSWOTPrompt(Competitor $competitor, array $data): string
    {
        $metricsInfo = '';
        if (!empty($data['metrics'])) {
            $metricsInfo = "\n**Current Metrics:**\n";
            foreach ($data['metrics'] as $key => $value) {
                if ($value !== null) {
                    $metricsInfo .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
                }
            }
        }

        $trendsInfo = '';
        if (!empty($data['trends'])) {
            $trendsInfo = "\n**Trends (Last 30 Days):**\n";
            $trendsInfo .= "- Follower Trend: {$data['trends']['follower_trend']}\n";
            $trendsInfo .= "- Engagement Trend: {$data['trends']['engagement_trend']}\n";
        }

        $activitiesInfo = '';
        if (!empty($data['recent_activities'])) {
            $activitiesInfo = "\n**Recent Activities:**\n";
            foreach (array_slice($data['recent_activities'], 0, 5) as $activity) {
                $activitiesInfo .= "- [{$activity['date']}] {$activity['type']} on {$activity['platform']}: {$activity['title']}\n";
            }
        }

        return <<<PROMPT
You are a competitive intelligence analyst. Perform a comprehensive SWOT analysis for the following competitor:

**Competitor Information:**
- Name: {$competitor->name}
- Industry: {$data['basic_info']['industry']}
- Location: {$data['basic_info']['location']}
- Threat Level: {$data['basic_info']['threat_level']}
- Description: {$data['basic_info']['description']}
{$metricsInfo}
{$trendsInfo}
{$activitiesInfo}

**Instructions:**
1. Analyze the competitor's Strengths, Weaknesses, Opportunities, and Threats
2. Focus on digital marketing, social media presence, and customer engagement
3. Be specific and data-driven when possible
4. Provide actionable insights

**Response Format (JSON):**
{
    "strengths": [
        "Specific strength 1",
        "Specific strength 2",
        "Specific strength 3"
    ],
    "weaknesses": [
        "Specific weakness 1",
        "Specific weakness 2",
        "Specific weakness 3"
    ],
    "opportunities": [
        "Specific opportunity 1",
        "Specific opportunity 2",
        "Specific opportunity 3"
    ],
    "threats": [
        "Specific threat 1",
        "Specific threat 2",
        "Specific threat 3"
    ],
    "overall_assessment": "Brief 2-3 sentence summary of the competitive position",
    "recommendations": [
        "Actionable recommendation 1",
        "Actionable recommendation 2",
        "Actionable recommendation 3"
    ]
}

Respond ONLY with the JSON, no additional text.
PROMPT;
    }

    /**
     * Parse SWOT response from Claude AI
     */
    protected function parseSWOTResponse(string $response): array
    {
        // Try to extract JSON from response
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json) {
                return [
                    'strengths' => $json['strengths'] ?? [],
                    'weaknesses' => $json['weaknesses'] ?? [],
                    'opportunities' => $json['opportunities'] ?? [],
                    'threats' => $json['threats'] ?? [],
                    'overall_assessment' => $json['overall_assessment'] ?? '',
                    'recommendations' => $json['recommendations'] ?? [],
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        }

        // Fallback response if parsing fails
        return [
            'strengths' => ['Analysis could not be generated'],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
            'overall_assessment' => 'Unable to generate SWOT analysis at this time.',
            'recommendations' => [],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Compare two competitors
     */
    public function compareCompetitors(Competitor $competitor1, Competitor $competitor2): array
    {
        $data1 = $this->gatherCompetitorData($competitor1);
        $data2 = $this->gatherCompetitorData($competitor2);

        $prompt = $this->buildComparisonPrompt($competitor1, $competitor2, $data1, $data2);

        $response = $this->claudeAI->complete($prompt, null, 3072);

        return $this->parseComparisonResponse($response);
    }

    /**
     * Build comparison prompt
     */
    protected function buildComparisonPrompt($comp1, $comp2, $data1, $data2): string
    {
        $followers1 = $data1['metrics']['instagram_followers'] ?? 'N/A';
        $engagement1 = $data1['metrics']['instagram_engagement_rate'] ?? 'N/A';
        $followers2 = $data2['metrics']['instagram_followers'] ?? 'N/A';
        $engagement2 = $data2['metrics']['instagram_engagement_rate'] ?? 'N/A';

        return <<<PROMPT
Compare these two competitors and provide insights:

**Competitor 1: {$comp1->name}**
- Instagram Followers: {$followers1}
- Engagement Rate: {$engagement1}%

**Competitor 2: {$comp2->name}**
- Instagram Followers: {$followers2}
- Engagement Rate: {$engagement2}%

Provide a brief comparison highlighting who is performing better and why (2-3 sentences).
PROMPT;
    }

    /**
     * Parse comparison response
     */
    protected function parseComparisonResponse(string $response): array
    {
        return [
            'comparison' => $response,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get competitive insights for business
     */
    public function getCompetitiveInsights(string $businessId): array
    {
        $competitors = Competitor::where('business_id', $businessId)
            ->where('is_active', true)
            ->with(['metrics' => fn($q) => $q->latest('date')->limit(1)])
            ->get();

        if ($competitors->isEmpty()) {
            return [
                'top_threat' => null,
                'fastest_growing' => null,
                'most_engaging' => null,
                'insights' => 'No competitors being tracked',
            ];
        }

        // Find top threat
        $topThreat = $competitors->where('threat_level', 'critical')->first()
            ?? $competitors->where('threat_level', 'high')->first();

        // Find fastest growing
        $fastestGrowing = $competitors->sortByDesc(function ($comp) {
            return $comp->metrics->first()?->follower_growth_rate ?? 0;
        })->first();

        // Find most engaging
        $mostEngaging = $competitors->sortByDesc(function ($comp) {
            return $comp->metrics->first()?->instagram_engagement_rate ?? 0;
        })->first();

        return [
            'top_threat' => $topThreat ? [
                'id' => $topThreat->id,
                'name' => $topThreat->name,
                'threat_level' => $topThreat->threat_level,
            ] : null,
            'fastest_growing' => $fastestGrowing ? [
                'id' => $fastestGrowing->id,
                'name' => $fastestGrowing->name,
                'growth_rate' => $fastestGrowing->metrics->first()?->follower_growth_rate ?? 0,
            ] : null,
            'most_engaging' => $mostEngaging ? [
                'id' => $mostEngaging->id,
                'name' => $mostEngaging->name,
                'engagement_rate' => $mostEngaging->metrics->first()?->instagram_engagement_rate ?? 0,
            ] : null,
        ];
    }
}
