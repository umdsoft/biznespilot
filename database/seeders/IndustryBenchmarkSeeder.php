<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustryBenchmarkSeeder extends Seeder
{
    public function run(): void
    {
        // Get industry IDs
        $industries = Industry::whereNull('parent_id')->pluck('id')->toArray();

        // Default benchmarks for all industries
        $defaultBenchmarks = [
            // Engagement metrics
            [
                'metric_code' => 'engagement_rate',
                'metric_name_uz' => 'Engagement Rate',
                'metric_name_en' => 'Engagement Rate',
                'poor_threshold' => 1.0,
                'average_value' => 2.5,
                'good_threshold' => 3.5,
                'excellent_threshold' => 5.0,
                'unit' => 'percent',
                'direction' => 'higher_better',
            ],
            [
                'metric_code' => 'follower_growth_rate',
                'metric_name_uz' => 'Obunachilar o\'sishi',
                'metric_name_en' => 'Follower Growth Rate',
                'poor_threshold' => 0.5,
                'average_value' => 2.0,
                'good_threshold' => 5.0,
                'excellent_threshold' => 10.0,
                'unit' => 'percent',
                'direction' => 'higher_better',
            ],
            // Cost metrics
            [
                'metric_code' => 'cpl',
                'metric_name_uz' => 'Lead narxi',
                'metric_name_en' => 'Cost Per Lead',
                'poor_threshold' => 500000,
                'average_value' => 250000,
                'good_threshold' => 150000,
                'excellent_threshold' => 80000,
                'unit' => 'currency',
                'direction' => 'lower_better',
            ],
            [
                'metric_code' => 'cac',
                'metric_name_uz' => 'Mijoz jalb qilish narxi',
                'metric_name_en' => 'Customer Acquisition Cost',
                'poor_threshold' => 1500000,
                'average_value' => 800000,
                'good_threshold' => 400000,
                'excellent_threshold' => 200000,
                'unit' => 'currency',
                'direction' => 'lower_better',
            ],
            [
                'metric_code' => 'cpc',
                'metric_name_uz' => 'Click narxi',
                'metric_name_en' => 'Cost Per Click',
                'poor_threshold' => 15000,
                'average_value' => 8000,
                'good_threshold' => 4000,
                'excellent_threshold' => 2000,
                'unit' => 'currency',
                'direction' => 'lower_better',
            ],
            // Conversion metrics
            [
                'metric_code' => 'conversion_rate',
                'metric_name_uz' => 'Konversiya',
                'metric_name_en' => 'Lead to Customer Conversion Rate',
                'poor_threshold' => 5,
                'average_value' => 12,
                'good_threshold' => 18,
                'excellent_threshold' => 25,
                'unit' => 'percent',
                'direction' => 'higher_better',
            ],
            [
                'metric_code' => 'ctr',
                'metric_name_uz' => 'Click through rate',
                'metric_name_en' => 'Click Through Rate',
                'poor_threshold' => 0.5,
                'average_value' => 1.5,
                'good_threshold' => 2.5,
                'excellent_threshold' => 4.0,
                'unit' => 'percent',
                'direction' => 'higher_better',
            ],
            // ROI metrics
            [
                'metric_code' => 'roas',
                'metric_name_uz' => 'Reklama daromadi',
                'metric_name_en' => 'Return on Ad Spend',
                'poor_threshold' => 1.5,
                'average_value' => 3.0,
                'good_threshold' => 5.0,
                'excellent_threshold' => 8.0,
                'unit' => 'number',
                'direction' => 'higher_better',
            ],
            [
                'metric_code' => 'ltv_cac_ratio',
                'metric_name_uz' => 'LTV/CAC nisbati',
                'metric_name_en' => 'LTV/CAC Ratio',
                'poor_threshold' => 1.5,
                'average_value' => 3.0,
                'good_threshold' => 4.0,
                'excellent_threshold' => 6.0,
                'unit' => 'number',
                'direction' => 'higher_better',
            ],
            // Retention metrics
            [
                'metric_code' => 'churn_rate',
                'metric_name_uz' => 'Churn rate',
                'metric_name_en' => 'Monthly Churn Rate',
                'poor_threshold' => 15,
                'average_value' => 8,
                'good_threshold' => 5,
                'excellent_threshold' => 2,
                'unit' => 'percent',
                'direction' => 'lower_better',
            ],
            [
                'metric_code' => 'repeat_purchase_rate',
                'metric_name_uz' => 'Qayta xarid',
                'metric_name_en' => 'Repeat Purchase Rate',
                'poor_threshold' => 10,
                'average_value' => 25,
                'good_threshold' => 40,
                'excellent_threshold' => 60,
                'unit' => 'percent',
                'direction' => 'higher_better',
            ],
            // Response metrics
            [
                'metric_code' => 'avg_response_time',
                'metric_name_uz' => 'Javob vaqti',
                'metric_name_en' => 'Average Response Time',
                'poor_threshold' => 24,
                'average_value' => 4,
                'good_threshold' => 1,
                'excellent_threshold' => 0.25,
                'unit' => 'hours',
                'direction' => 'lower_better',
            ],
            // Content metrics
            [
                'metric_code' => 'content_frequency',
                'metric_name_uz' => 'Kontent chastotasi',
                'metric_name_en' => 'Content Posts Per Week',
                'poor_threshold' => 2,
                'average_value' => 5,
                'good_threshold' => 7,
                'excellent_threshold' => 14,
                'unit' => 'number',
                'direction' => 'higher_better',
            ],
            // Funnel metrics
            [
                'metric_code' => 'funnel_conversion',
                'metric_name_uz' => 'Funnel konversiyasi',
                'metric_name_en' => 'Funnel Conversion Rate',
                'poor_threshold' => 1,
                'average_value' => 3,
                'good_threshold' => 5,
                'excellent_threshold' => 8,
                'unit' => 'percent',
                'direction' => 'higher_better',
            ],
            // Sales metrics
            [
                'metric_code' => 'sales_cycle_days',
                'metric_name_uz' => 'Sotuv davri',
                'metric_name_en' => 'Sales Cycle Days',
                'poor_threshold' => 60,
                'average_value' => 30,
                'good_threshold' => 14,
                'excellent_threshold' => 7,
                'unit' => 'days',
                'direction' => 'lower_better',
            ],
        ];

        // Insert benchmarks for each industry
        foreach ($industries as $industryId) {
            foreach ($defaultBenchmarks as $benchmark) {
                DB::table('industry_benchmarks')->updateOrInsert(
                    [
                        'industry_id' => $industryId,
                        'metric_code' => $benchmark['metric_code'],
                    ],
                    [
                        ...$benchmark,
                        'industry_id' => $industryId,
                        'is_active' => true,
                        'source' => 'Industry Standards 2024',
                        'valid_from' => now()->startOfYear(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
