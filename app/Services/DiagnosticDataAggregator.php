<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\Hypothesis;
use App\Models\Integration;
use App\Models\MaturityAssessment;
use App\Models\Problem;
use Illuminate\Support\Collection;

class DiagnosticDataAggregator
{
    /**
     * Aggregate all FAZA 1 data for a business
     */
    public function aggregateForBusiness(Business $business): array
    {
        return [
            'business' => $this->getBusinessData($business),
            'maturity' => $this->getMaturityData($business),
            'integrations' => $this->getIntegrationData($business),
            'problems' => $this->getProblemsData($business),
            'dream_buyers' => $this->getDreamBuyersData($business),
            'competitors' => $this->getCompetitorsData($business),
            'hypotheses' => $this->getHypothesesData($business),
            'summary' => $this->generateSummary($business),
        ];
    }

    /**
     * Get business profile data
     */
    protected function getBusinessData(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'industry' => $business->industry?->name,
            'industry_id' => $business->industry_id,
            'sub_industry' => $business->subIndustry?->name,
            'business_type' => $business->business_type,
            'business_model' => $business->business_model,
            'company_size' => $business->company_size,
            'monthly_revenue' => $business->monthly_revenue,
            'target_audience' => $business->target_audience,
            'main_products' => $business->main_products,
            'unique_value' => $business->unique_value,
            'marketing_budget' => $business->marketing_budget,
            'team_size' => $business->team_size,
            'years_in_business' => $business->years_in_business,
            'goals' => $business->goals,
            'challenges' => $business->challenges,
            'onboarding_completed_at' => $business->onboarding_completed_at?->toDateTimeString(),
            'onboarding_progress' => $business->onboarding_progress,
        ];
    }

    /**
     * Get maturity assessment data
     */
    protected function getMaturityData(Business $business): array
    {
        $assessment = MaturityAssessment::where('business_id', $business->id)
            ->latest()
            ->first();

        if (!$assessment) {
            return [
                'exists' => false,
                'overall_score' => 0,
                'level' => 'unknown',
            ];
        }

        return [
            'exists' => true,
            'overall_score' => $assessment->overall_score,
            'level' => $assessment->maturity_level,
            'level_label' => $assessment->getMaturityLabel(),
            'categories' => [
                'marketing' => [
                    'score' => $assessment->marketing_score,
                    'details' => $assessment->marketing_details,
                ],
                'sales' => [
                    'score' => $assessment->sales_score,
                    'details' => $assessment->sales_details,
                ],
                'content' => [
                    'score' => $assessment->content_score,
                    'details' => $assessment->content_details,
                ],
                'funnel' => [
                    'score' => $assessment->funnel_score,
                    'details' => $assessment->funnel_details,
                ],
                'analytics' => [
                    'score' => $assessment->analytics_score,
                    'details' => $assessment->analytics_details,
                ],
            ],
            'strengths' => $assessment->strengths ?? [],
            'weaknesses' => $assessment->weaknesses ?? [],
            'recommendations' => $assessment->recommendations ?? [],
            'assessed_at' => $assessment->created_at->toDateTimeString(),
        ];
    }

    /**
     * Get integrations data
     */
    protected function getIntegrationData(Business $business): array
    {
        $integrations = Integration::where('business_id', $business->id)->get();

        $connected = $integrations->where('is_connected', true);
        $platforms = $connected->pluck('platform')->unique()->values()->toArray();

        return [
            'total' => $integrations->count(),
            'connected' => $connected->count(),
            'platforms' => $platforms,
            'has_social_media' => $this->hasPlatformType($connected, ['instagram', 'facebook', 'telegram', 'tiktok']),
            'has_crm' => $this->hasPlatformType($connected, ['bitrix24', 'amocrm']),
            'has_analytics' => $this->hasPlatformType($connected, ['google_analytics', 'yandex_metrica']),
            'has_ads' => $this->hasPlatformType($connected, ['google_ads', 'facebook_ads', 'yandex_direct']),
            'details' => $connected->map(function ($integration) {
                return [
                    'platform' => $integration->platform,
                    'type' => $integration->type,
                    'connected_at' => $integration->connected_at?->toDateTimeString(),
                    'last_sync' => $integration->last_sync_at?->toDateTimeString(),
                    'metrics' => $integration->cached_metrics ?? [],
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get problems data
     */
    protected function getProblemsData(Business $business): array
    {
        $problems = Problem::where('business_id', $business->id)->get();

        return [
            'total' => $problems->count(),
            'by_category' => $problems->groupBy('category')->map->count()->toArray(),
            'by_priority' => $problems->groupBy('priority')->map->count()->toArray(),
            'high_priority' => $problems->where('priority', 'high')->count(),
            'details' => $problems->map(function ($problem) {
                return [
                    'title' => $problem->title,
                    'description' => $problem->description,
                    'category' => $problem->category,
                    'priority' => $problem->priority,
                    'impact' => $problem->impact,
                    'current_solution' => $problem->current_solution,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get dream buyers data
     */
    protected function getDreamBuyersData(Business $business): array
    {
        $dreamBuyers = DreamBuyer::where('business_id', $business->id)->get();

        return [
            'total' => $dreamBuyers->count(),
            'personas' => $dreamBuyers->map(function ($buyer) {
                return [
                    'name' => $buyer->name,
                    'age_range' => $buyer->age_range,
                    'gender' => $buyer->gender,
                    'location' => $buyer->location,
                    'income_level' => $buyer->income_level,
                    'occupation' => $buyer->occupation,
                    'interests' => $buyer->interests,
                    'pain_points' => $buyer->pain_points,
                    'goals' => $buyer->goals,
                    'buying_triggers' => $buyer->buying_triggers,
                    'objections' => $buyer->objections,
                    'preferred_channels' => $buyer->preferred_channels,
                    'is_primary' => $buyer->is_primary,
                ];
            })->values()->toArray(),
            'has_primary' => $dreamBuyers->where('is_primary', true)->count() > 0,
        ];
    }

    /**
     * Get competitors data
     */
    protected function getCompetitorsData(Business $business): array
    {
        $competitors = Competitor::where('business_id', $business->id)->get();

        return [
            'total' => $competitors->count(),
            'by_type' => $competitors->groupBy('type')->map->count()->toArray(),
            'competitors' => $competitors->map(function ($competitor) {
                return [
                    'name' => $competitor->name,
                    'type' => $competitor->type,
                    'website' => $competitor->website,
                    'strengths' => $competitor->strengths,
                    'weaknesses' => $competitor->weaknesses,
                    'price_range' => $competitor->price_range,
                    'market_share' => $competitor->market_share,
                    'social_presence' => $competitor->social_presence,
                    'notes' => $competitor->notes,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get hypotheses data
     */
    protected function getHypothesesData(Business $business): array
    {
        $hypotheses = Hypothesis::where('business_id', $business->id)->get();

        return [
            'total' => $hypotheses->count(),
            'by_status' => $hypotheses->groupBy('status')->map->count()->toArray(),
            'by_category' => $hypotheses->groupBy('category')->map->count()->toArray(),
            'validated' => $hypotheses->where('status', 'validated')->count(),
            'invalidated' => $hypotheses->where('status', 'invalidated')->count(),
            'pending' => $hypotheses->where('status', 'pending')->count(),
            'hypotheses' => $hypotheses->map(function ($hypothesis) {
                return [
                    'title' => $hypothesis->title,
                    'description' => $hypothesis->description,
                    'category' => $hypothesis->category,
                    'status' => $hypothesis->status,
                    'priority' => $hypothesis->priority,
                    'expected_impact' => $hypothesis->expected_impact,
                    'test_method' => $hypothesis->test_method,
                    'success_criteria' => $hypothesis->success_criteria,
                    'result' => $hypothesis->result,
                    'learnings' => $hypothesis->learnings,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Generate summary of all data
     */
    protected function generateSummary(Business $business): array
    {
        $integrations = Integration::where('business_id', $business->id)->where('is_connected', true)->count();
        $problems = Problem::where('business_id', $business->id)->count();
        $dreamBuyers = DreamBuyer::where('business_id', $business->id)->count();
        $competitors = Competitor::where('business_id', $business->id)->count();
        $hypotheses = Hypothesis::where('business_id', $business->id)->count();

        $maturity = MaturityAssessment::where('business_id', $business->id)->latest()->first();

        return [
            'data_completeness' => $this->calculateDataCompleteness($business),
            'total_integrations' => $integrations,
            'total_problems' => $problems,
            'total_dream_buyers' => $dreamBuyers,
            'total_competitors' => $competitors,
            'total_hypotheses' => $hypotheses,
            'maturity_score' => $maturity?->overall_score ?? 0,
            'maturity_level' => $maturity?->maturity_level ?? 'unknown',
            'has_revenue_data' => !empty($business->monthly_revenue),
            'has_budget_data' => !empty($business->marketing_budget),
            'has_team_data' => !empty($business->team_size),
            'onboarding_complete' => $business->onboarding_progress >= 100,
        ];
    }

    /**
     * Calculate data completeness percentage
     */
    protected function calculateDataCompleteness(Business $business): int
    {
        $fields = [
            'name', 'industry_id', 'business_type', 'business_model',
            'company_size', 'monthly_revenue', 'target_audience',
            'main_products', 'marketing_budget', 'team_size',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($business->$field)) {
                $filled++;
            }
        }

        // Add related data
        $hasIntegrations = Integration::where('business_id', $business->id)->where('is_connected', true)->exists();
        $hasProblems = Problem::where('business_id', $business->id)->exists();
        $hasDreamBuyers = DreamBuyer::where('business_id', $business->id)->exists();
        $hasCompetitors = Competitor::where('business_id', $business->id)->exists();
        $hasMaturity = MaturityAssessment::where('business_id', $business->id)->exists();

        $totalChecks = count($fields) + 5;
        $passed = $filled + ($hasIntegrations ? 1 : 0) + ($hasProblems ? 1 : 0) +
                  ($hasDreamBuyers ? 1 : 0) + ($hasCompetitors ? 1 : 0) + ($hasMaturity ? 1 : 0);

        return (int) round(($passed / $totalChecks) * 100);
    }

    /**
     * Check if any integration of given types exists
     */
    protected function hasPlatformType(Collection $integrations, array $platforms): bool
    {
        foreach ($platforms as $platform) {
            if ($integrations->contains('platform', $platform)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get formatted data for AI analysis prompt
     */
    public function getFormattedForAI(Business $business): string
    {
        $data = $this->aggregateForBusiness($business);

        $formatted = "## BIZNES PROFILI\n";
        $formatted .= "Nomi: {$data['business']['name']}\n";
        $formatted .= "Soha: {$data['business']['industry']}\n";
        $formatted .= "Biznes turi: {$data['business']['business_type']}\n";
        $formatted .= "Biznes modeli: {$data['business']['business_model']}\n";
        $formatted .= "Kompaniya hajmi: {$data['business']['company_size']}\n";
        $formatted .= "Oylik daromad: {$data['business']['monthly_revenue']}\n";
        $formatted .= "Marketing budget: {$data['business']['marketing_budget']}\n";
        $formatted .= "Jamoa hajmi: {$data['business']['team_size']}\n";
        $formatted .= "Maqsadli auditoriya: {$data['business']['target_audience']}\n";
        $formatted .= "Asosiy mahsulotlar: {$data['business']['main_products']}\n";
        $formatted .= "Noyob qiymat: {$data['business']['unique_value']}\n";

        $formatted .= "\n## MATURITY BAHOLASH\n";
        $formatted .= "Umumiy ball: {$data['maturity']['overall_score']}/100\n";
        $formatted .= "Daraja: {$data['maturity']['level_label']}\n";
        if (isset($data['maturity']['categories'])) {
            foreach ($data['maturity']['categories'] as $category => $info) {
                $formatted .= ucfirst($category) . " ball: {$info['score']}/100\n";
            }
        }

        $formatted .= "\n## INTEGRATSIYALAR\n";
        $formatted .= "Ulangan platformalar: " . count($data['integrations']['platforms']) . "\n";
        $formatted .= "Platformalar: " . implode(', ', $data['integrations']['platforms']) . "\n";
        $formatted .= "Ijtimoiy tarmoqlar: " . ($data['integrations']['has_social_media'] ? 'Ha' : 'Yo\'q') . "\n";
        $formatted .= "CRM: " . ($data['integrations']['has_crm'] ? 'Ha' : 'Yo\'q') . "\n";
        $formatted .= "Analitika: " . ($data['integrations']['has_analytics'] ? 'Ha' : 'Yo\'q') . "\n";

        $formatted .= "\n## MUAMMOLAR\n";
        $formatted .= "Jami muammolar: {$data['problems']['total']}\n";
        $formatted .= "Yuqori prioritet: {$data['problems']['high_priority']}\n";
        foreach ($data['problems']['details'] as $problem) {
            $formatted .= "- [{$problem['priority']}] {$problem['title']}: {$problem['description']}\n";
        }

        $formatted .= "\n## IDEAL MIJOZLAR\n";
        $formatted .= "Jami personalar: {$data['dream_buyers']['total']}\n";
        foreach ($data['dream_buyers']['personas'] as $persona) {
            $formatted .= "- {$persona['name']}: {$persona['age_range']}, {$persona['occupation']}\n";
            $formatted .= "  Og'riq nuqtalari: " . ($persona['pain_points'] ?? 'Belgilanmagan') . "\n";
        }

        $formatted .= "\n## RAQOBATCHILAR\n";
        $formatted .= "Jami raqobatchilar: {$data['competitors']['total']}\n";
        foreach ($data['competitors']['competitors'] as $competitor) {
            $formatted .= "- {$competitor['name']} ({$competitor['type']})\n";
            $formatted .= "  Kuchli tomonlar: " . ($competitor['strengths'] ?? 'Belgilanmagan') . "\n";
            $formatted .= "  Zaif tomonlar: " . ($competitor['weaknesses'] ?? 'Belgilanmagan') . "\n";
        }

        $formatted .= "\n## GIPOTEZALAR\n";
        $formatted .= "Jami: {$data['hypotheses']['total']}\n";
        $formatted .= "Tasdiqlangan: {$data['hypotheses']['validated']}\n";
        $formatted .= "Rad etilgan: {$data['hypotheses']['invalidated']}\n";
        $formatted .= "Kutilmoqda: {$data['hypotheses']['pending']}\n";

        $formatted .= "\n## XULOSA\n";
        $formatted .= "Ma'lumot to'liqligi: {$data['summary']['data_completeness']}%\n";
        $formatted .= "Onboarding tugallangan: " . ($data['summary']['onboarding_complete'] ? 'Ha' : 'Yo\'q') . "\n";

        return $formatted;
    }
}
