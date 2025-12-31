<?php

namespace Database\Seeders;

use App\Models\StepDefinition;
use Illuminate\Database\Seeder;

class StepDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            // PROFILE CATEGORY (3 steps)
            [
                'code' => 'business_basic',
                'phase' => 1,
                'category' => 'profile',
                'name_uz' => 'Asosiy ma\'lumotlar',
                'name_en' => 'Basic Information',
                'description_uz' => 'Biznes nomi, sohasi va turi haqida ma\'lumotlar',
                'description_en' => 'Business name, industry and type information',
                'is_required' => true,
                'required_fields' => ['name', 'industry_id', 'business_type', 'business_model'],
                'icon' => 'building-office',
                'estimated_time_minutes' => 3,
                'sort_order' => 1,
            ],
            [
                'code' => 'business_details',
                'phase' => 1,
                'category' => 'profile',
                'name_uz' => 'Biznes tafsilotlari',
                'name_en' => 'Business Details',
                'description_uz' => 'Jamoa, lokatsiya va bosqich haqida',
                'description_en' => 'Team, location and stage information',
                'is_required' => true,
                'depends_on' => ['business_basic'],
                'required_fields' => ['team_size', 'city', 'business_stage'],
                'icon' => 'users',
                'estimated_time_minutes' => 3,
                'sort_order' => 2,
            ],
            [
                'code' => 'business_maturity',
                'phase' => 1,
                'category' => 'profile',
                'name_uz' => 'Biznes holati',
                'name_en' => 'Business Maturity',
                'description_uz' => 'Daromad, byudjet va asosiy qiyinchiliklar',
                'description_en' => 'Revenue, budget and main challenges',
                'is_required' => true,
                'depends_on' => ['business_details'],
                'required_fields' => ['monthly_revenue_range', 'monthly_marketing_budget_range', 'main_challenges'],
                'icon' => 'chart-bar',
                'estimated_time_minutes' => 5,
                'sort_order' => 3,
            ],

            // INTEGRATION CATEGORY (4 steps)
            [
                'code' => 'integration_instagram',
                'phase' => 1,
                'category' => 'integration',
                'name_uz' => 'Instagram ulash',
                'name_en' => 'Connect Instagram',
                'description_uz' => 'Instagram biznes akkauntingizni ulang',
                'description_en' => 'Connect your Instagram business account',
                'is_required' => true,
                'required_fields' => ['instagram_connected'],
                'icon' => 'camera',
                'estimated_time_minutes' => 5,
                'sort_order' => 4,
            ],
            [
                'code' => 'integration_telegram',
                'phase' => 1,
                'category' => 'integration',
                'name_uz' => 'Telegram ulash',
                'name_en' => 'Connect Telegram',
                'description_uz' => 'Telegram kanalingiz yoki botingizni ulang',
                'description_en' => 'Connect your Telegram channel or bot',
                'is_required' => true,
                'required_fields' => ['telegram_connected'],
                'icon' => 'paper-airplane',
                'estimated_time_minutes' => 5,
                'sort_order' => 5,
            ],
            [
                'code' => 'integration_amocrm',
                'phase' => 1,
                'category' => 'integration',
                'name_uz' => 'AmoCRM ulash',
                'name_en' => 'Connect AmoCRM',
                'description_uz' => 'AmoCRM bilan integratsiya (ixtiyoriy)',
                'description_en' => 'AmoCRM integration (optional)',
                'is_required' => false,
                'required_fields' => [],
                'icon' => 'document-text',
                'estimated_time_minutes' => 10,
                'sort_order' => 6,
            ],
            [
                'code' => 'integration_google_ads',
                'phase' => 1,
                'category' => 'integration',
                'name_uz' => 'Google Ads ulash',
                'name_en' => 'Connect Google Ads',
                'description_uz' => 'Google Ads integratsiya (ixtiyoriy)',
                'description_en' => 'Google Ads integration (optional)',
                'is_required' => false,
                'required_fields' => [],
                'icon' => 'currency-dollar',
                'estimated_time_minutes' => 10,
                'sort_order' => 7,
            ],

            // KPI CATEGORY (2 steps)
            [
                'code' => 'kpi_sales',
                'phase' => 1,
                'category' => 'kpi',
                'name_uz' => 'Sotuv ko\'rsatkichlari',
                'name_en' => 'Sales Metrics',
                'description_uz' => 'Lidlar, konversiya, sotuv hajmi',
                'description_en' => 'Leads, conversion, sales volume',
                'is_required' => false,
                'required_fields' => [],
                'icon' => 'currency-dollar',
                'estimated_time_minutes' => 5,
                'sort_order' => 8,
            ],
            [
                'code' => 'kpi_marketing',
                'phase' => 1,
                'category' => 'kpi',
                'name_uz' => 'Marketing ko\'rsatkichlari',
                'name_en' => 'Marketing Metrics',
                'description_uz' => 'Kanallar, byudjet, ROI',
                'description_en' => 'Channels, budget, ROI',
                'is_required' => false,
                'required_fields' => [],
                'icon' => 'chart-pie',
                'estimated_time_minutes' => 5,
                'sort_order' => 9,
            ],

            // FRAMEWORK CATEGORY (5 steps)
            [
                'code' => 'framework_problem',
                'phase' => 1,
                'category' => 'framework',
                'name_uz' => 'Muammo aniqlash',
                'name_en' => 'Problem Definition',
                'description_uz' => 'Asosiy muammo va maqsadlarni aniqlash',
                'description_en' => 'Define main problem and objectives',
                'is_required' => true,
                'required_fields' => ['problem_description', 'desired_outcome'],
                'icon' => 'exclamation-circle',
                'estimated_time_minutes' => 10,
                'sort_order' => 10,
            ],
            [
                'code' => 'framework_dream_buyer',
                'phase' => 1,
                'category' => 'framework',
                'name_uz' => 'Ideal mijoz profili',
                'name_en' => 'Ideal Customer Profile',
                'description_uz' => 'Ideal mijoz profilini yaratish (9 ta savol)',
                'description_en' => 'Create ideal customer profile (9 questions)',
                'is_required' => true,
                'depends_on' => ['framework_problem'],
                'required_fields' => [
                    'dream_buyer_created', 'q1_answered', 'q2_answered', 'q3_answered',
                    'q4_answered', 'q5_answered', 'q6_answered', 'q7_answered',
                    'q8_answered', 'q9_answered'
                ],
                'icon' => 'user-circle',
                'estimated_time_minutes' => 20,
                'sort_order' => 11,
            ],
            [
                'code' => 'framework_research',
                'phase' => 1,
                'category' => 'framework',
                'name_uz' => 'Tadqiqot ma\'lumotlari',
                'name_en' => 'Research Data',
                'description_uz' => 'Bozor tadqiqoti ma\'lumotlarini kiritish',
                'description_en' => 'Enter market research data',
                'is_required' => false,
                'depends_on' => ['framework_dream_buyer'],
                'required_fields' => [],
                'icon' => 'document-search',
                'estimated_time_minutes' => 15,
                'sort_order' => 12,
            ],
            [
                'code' => 'framework_competitors',
                'phase' => 1,
                'category' => 'framework',
                'name_uz' => 'Raqobatchilar tahlili',
                'name_en' => 'Competitor Analysis',
                'description_uz' => 'Kamida 2 ta raqobatchini kiritish',
                'description_en' => 'Add at least 2 competitors',
                'is_required' => true,
                'depends_on' => ['framework_dream_buyer'],
                'required_fields' => ['min_2_competitors'],
                'completion_rules' => [
                    'min_count' => 2,
                    'model' => 'Competitor'
                ],
                'icon' => 'user-group',
                'estimated_time_minutes' => 15,
                'sort_order' => 13,
            ],
            [
                'code' => 'framework_hypotheses',
                'phase' => 1,
                'category' => 'framework',
                'name_uz' => 'Marketing gipotezalari',
                'name_en' => 'Marketing Hypotheses',
                'description_uz' => 'Kamida 1 ta gipoteza yaratish',
                'description_en' => 'Create at least 1 hypothesis',
                'is_required' => true,
                'depends_on' => ['framework_competitors'],
                'required_fields' => ['min_1_hypothesis'],
                'completion_rules' => [
                    'min_count' => 1,
                    'model' => 'MarketingHypothesis'
                ],
                'icon' => 'light-bulb',
                'estimated_time_minutes' => 10,
                'sort_order' => 14,
            ],
        ];

        foreach ($steps as $step) {
            StepDefinition::updateOrCreate(
                ['code' => $step['code']],
                $step
            );
        }
    }
}
