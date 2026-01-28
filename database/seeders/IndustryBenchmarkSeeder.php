<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\IndustryBenchmark;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndustryBenchmarkSeeder extends Seeder
{
    public function run(): void
    {
        // Seed algorithm benchmarks
        $this->seedAlgorithmBenchmarks();

        // Seed original benchmarks
        // Get industry IDs
        $industries = Industry::whereNull('parent_id')->get();

        // Default benchmarks for all industries (matched with migration schema)
        $defaultBenchmarks = [
            // Engagement metrics
            [
                'metric_name' => 'Engagement Rate',
                'metric_type' => 'engagement',
                'min_value' => 0.5,
                'max_value' => 10.0,
                'avg_value' => 2.5,
                'good_value' => 3.5,
                'excellent_value' => 5.0,
            ],
            [
                'metric_name' => 'Follower Growth Rate',
                'metric_type' => 'engagement',
                'min_value' => 0.1,
                'max_value' => 15.0,
                'avg_value' => 2.0,
                'good_value' => 5.0,
                'excellent_value' => 10.0,
            ],
            // Cost metrics
            [
                'metric_name' => 'Cost Per Lead',
                'metric_type' => 'cost',
                'min_value' => 50000,
                'max_value' => 1000000,
                'avg_value' => 250000,
                'good_value' => 150000,
                'excellent_value' => 80000,
            ],
            [
                'metric_name' => 'Customer Acquisition Cost',
                'metric_type' => 'cost',
                'min_value' => 100000,
                'max_value' => 3000000,
                'avg_value' => 800000,
                'good_value' => 400000,
                'excellent_value' => 200000,
            ],
            [
                'metric_name' => 'Cost Per Click',
                'metric_type' => 'cost',
                'min_value' => 1000,
                'max_value' => 30000,
                'avg_value' => 8000,
                'good_value' => 4000,
                'excellent_value' => 2000,
            ],
            // Conversion metrics
            [
                'metric_name' => 'Lead Conversion Rate',
                'metric_type' => 'conversion',
                'min_value' => 2,
                'max_value' => 40,
                'avg_value' => 12,
                'good_value' => 18,
                'excellent_value' => 25,
            ],
            [
                'metric_name' => 'Click Through Rate',
                'metric_type' => 'conversion',
                'min_value' => 0.2,
                'max_value' => 8.0,
                'avg_value' => 1.5,
                'good_value' => 2.5,
                'excellent_value' => 4.0,
            ],
            // ROI metrics
            [
                'metric_name' => 'Return on Ad Spend',
                'metric_type' => 'roi',
                'min_value' => 0.5,
                'max_value' => 15.0,
                'avg_value' => 3.0,
                'good_value' => 5.0,
                'excellent_value' => 8.0,
            ],
            [
                'metric_name' => 'LTV/CAC Ratio',
                'metric_type' => 'roi',
                'min_value' => 0.5,
                'max_value' => 10.0,
                'avg_value' => 3.0,
                'good_value' => 4.0,
                'excellent_value' => 6.0,
            ],
            // Retention metrics
            [
                'metric_name' => 'Monthly Churn Rate',
                'metric_type' => 'retention',
                'min_value' => 0.5,
                'max_value' => 25.0,
                'avg_value' => 8.0,
                'good_value' => 5.0,
                'excellent_value' => 2.0,
            ],
            [
                'metric_name' => 'Repeat Purchase Rate',
                'metric_type' => 'retention',
                'min_value' => 5,
                'max_value' => 80,
                'avg_value' => 25,
                'good_value' => 40,
                'excellent_value' => 60,
            ],
            // Response metrics
            [
                'metric_name' => 'Average Response Time (hours)',
                'metric_type' => 'response',
                'min_value' => 0.1,
                'max_value' => 48,
                'avg_value' => 4,
                'good_value' => 1,
                'excellent_value' => 0.25,
            ],
            // Content metrics
            [
                'metric_name' => 'Content Posts Per Week',
                'metric_type' => 'content',
                'min_value' => 1,
                'max_value' => 21,
                'avg_value' => 5,
                'good_value' => 7,
                'excellent_value' => 14,
            ],
            // Funnel metrics
            [
                'metric_name' => 'Funnel Conversion Rate',
                'metric_type' => 'funnel',
                'min_value' => 0.5,
                'max_value' => 15,
                'avg_value' => 3,
                'good_value' => 5,
                'excellent_value' => 8,
            ],
            // Sales metrics
            [
                'metric_name' => 'Sales Cycle Days',
                'metric_type' => 'sales',
                'min_value' => 3,
                'max_value' => 90,
                'avg_value' => 30,
                'good_value' => 14,
                'excellent_value' => 7,
            ],
        ];

        $currentYear = now()->year;

        // Insert benchmarks for each industry
        foreach ($industries as $industry) {
            foreach ($defaultBenchmarks as $benchmark) {
                DB::table('industry_benchmarks')->updateOrInsert(
                    [
                        'industry_id' => $industry->id,
                        'metric_name' => $benchmark['metric_name'],
                        'year' => $currentYear,
                    ],
                    [
                        'industry_id' => $industry->id,
                        'metric_name' => $benchmark['metric_name'],
                        'metric_type' => $benchmark['metric_type'],
                        'min_value' => $benchmark['min_value'],
                        'max_value' => $benchmark['max_value'],
                        'avg_value' => $benchmark['avg_value'],
                        'good_value' => $benchmark['good_value'],
                        'excellent_value' => $benchmark['excellent_value'],
                        'region' => 'Uzbekistan',
                        'year' => $currentYear,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Seed algorithm-specific benchmarks
     * Used by the DiagnosticAlgorithmService
     */
    protected function seedAlgorithmBenchmarks(): void
    {
        $benchmarks = $this->getAlgorithmBenchmarkData();

        foreach ($benchmarks as $data) {
            IndustryBenchmark::updateOrCreate(
                ['industry' => $data['industry']],
                $data
            );
        }

        $this->command->info('Algorithm benchmarks seeded: '.count($benchmarks).' industries');
    }

    /**
     * Get benchmark data for algorithm system
     */
    protected function getAlgorithmBenchmarkData(): array
    {
        return [
            // Default / General
            [
                'industry' => 'default',
                'sub_industry' => null,
                'avg_health_score' => 50,
                'avg_conversion_rate' => 2.5,
                'avg_engagement_rate' => 3.0,
                'avg_response_time_minutes' => 120,
                'avg_repeat_purchase_rate' => 25,
                'top_health_score' => 85,
                'top_conversion_rate' => 8,
                'top_engagement_rate' => 8,
                'top_response_time_minutes' => 15,
                'top_repeat_purchase_rate' => 45,
                'optimal_post_frequency_weekly' => 5,
                'optimal_stories_daily' => 5,
                'optimal_caption_length' => 150,
                'optimal_hashtag_count' => 15,
                'optimal_posting_times' => ['09:00', '12:00', '18:00', '21:00'],
                'proven_tactics' => ['Doimiy kontent', 'Mijozlar bilan muloqot', 'Aksiyalar'],
                'is_active' => true,
            ],

            // E-commerce
            [
                'industry' => 'ecommerce',
                'sub_industry' => 'online_store',
                'avg_health_score' => 52,
                'avg_conversion_rate' => 2.86,
                'avg_engagement_rate' => 2.5,
                'avg_response_time_minutes' => 60,
                'avg_repeat_purchase_rate' => 27,
                'top_health_score' => 88,
                'top_conversion_rate' => 10,
                'top_engagement_rate' => 6,
                'top_response_time_minutes' => 10,
                'top_repeat_purchase_rate' => 55,
                'optimal_post_frequency_weekly' => 7,
                'optimal_stories_daily' => 8,
                'optimal_caption_length' => 125,
                'optimal_hashtag_count' => 20,
                'optimal_posting_times' => ['10:00', '14:00', '19:00', '21:00'],
                'proven_tactics' => ['Mahsulot video', 'Flash sales', 'Mijoz sharhlari', 'Bepul yetkazish'],
                'is_active' => true,
            ],

            // Fashion
            [
                'industry' => 'fashion',
                'sub_industry' => 'clothing',
                'avg_health_score' => 55,
                'avg_conversion_rate' => 1.8,
                'avg_engagement_rate' => 4.5,
                'avg_response_time_minutes' => 90,
                'avg_repeat_purchase_rate' => 30,
                'top_health_score' => 90,
                'top_conversion_rate' => 6,
                'top_engagement_rate' => 10,
                'top_response_time_minutes' => 15,
                'top_repeat_purchase_rate' => 60,
                'optimal_post_frequency_weekly' => 10,
                'optimal_stories_daily' => 10,
                'optimal_caption_length' => 100,
                'optimal_hashtag_count' => 25,
                'optimal_posting_times' => ['11:00', '15:00', '19:00', '22:00'],
                'proven_tactics' => ['Influencer marketing', 'Lookbooks', 'Size guide', 'Customer photos'],
                'is_active' => true,
            ],

            // Food
            [
                'industry' => 'food',
                'sub_industry' => 'restaurant',
                'avg_health_score' => 48,
                'avg_conversion_rate' => 4.2,
                'avg_engagement_rate' => 5.5,
                'avg_response_time_minutes' => 30,
                'avg_repeat_purchase_rate' => 45,
                'top_health_score' => 85,
                'top_conversion_rate' => 12,
                'top_engagement_rate' => 12,
                'top_response_time_minutes' => 5,
                'top_repeat_purchase_rate' => 75,
                'optimal_post_frequency_weekly' => 7,
                'optimal_stories_daily' => 12,
                'optimal_caption_length' => 80,
                'optimal_hashtag_count' => 15,
                'optimal_posting_times' => ['08:00', '11:30', '17:30', '20:00'],
                'proven_tactics' => ['Food photography', 'Behind scenes', 'Daily specials', 'Customer reviews'],
                'is_active' => true,
            ],

            // Beauty
            [
                'industry' => 'beauty',
                'sub_industry' => 'cosmetics',
                'avg_health_score' => 54,
                'avg_conversion_rate' => 2.3,
                'avg_engagement_rate' => 5.0,
                'avg_response_time_minutes' => 60,
                'avg_repeat_purchase_rate' => 35,
                'top_health_score' => 92,
                'top_conversion_rate' => 8,
                'top_engagement_rate' => 11,
                'top_response_time_minutes' => 10,
                'top_repeat_purchase_rate' => 65,
                'optimal_post_frequency_weekly' => 8,
                'optimal_stories_daily' => 10,
                'optimal_caption_length' => 120,
                'optimal_hashtag_count' => 25,
                'optimal_posting_times' => ['10:00', '14:00', '18:00', '21:00'],
                'proven_tactics' => ['Tutorials', 'Before/After', 'Swatches', 'Influencer collabs'],
                'is_active' => true,
            ],

            // Health & Fitness
            [
                'industry' => 'health',
                'sub_industry' => 'fitness',
                'avg_health_score' => 50,
                'avg_conversion_rate' => 3.5,
                'avg_engagement_rate' => 4.8,
                'avg_response_time_minutes' => 45,
                'avg_repeat_purchase_rate' => 55,
                'top_health_score' => 88,
                'top_conversion_rate' => 10,
                'top_engagement_rate' => 10,
                'top_response_time_minutes' => 10,
                'top_repeat_purchase_rate' => 80,
                'optimal_post_frequency_weekly' => 6,
                'optimal_stories_daily' => 8,
                'optimal_caption_length' => 200,
                'optimal_hashtag_count' => 20,
                'optimal_posting_times' => ['06:00', '12:00', '17:00', '20:00'],
                'proven_tactics' => ['Transformation stories', 'Workout videos', 'Nutrition tips', 'Free trials'],
                'is_active' => true,
            ],

            // Education
            [
                'industry' => 'education',
                'sub_industry' => 'courses',
                'avg_health_score' => 45,
                'avg_conversion_rate' => 1.5,
                'avg_engagement_rate' => 3.5,
                'avg_response_time_minutes' => 120,
                'avg_repeat_purchase_rate' => 40,
                'top_health_score' => 85,
                'top_conversion_rate' => 5,
                'top_engagement_rate' => 8,
                'top_response_time_minutes' => 30,
                'top_repeat_purchase_rate' => 70,
                'optimal_post_frequency_weekly' => 5,
                'optimal_stories_daily' => 5,
                'optimal_caption_length' => 250,
                'optimal_hashtag_count' => 15,
                'optimal_posting_times' => ['09:00', '13:00', '19:00', '21:00'],
                'proven_tactics' => ['Free webinars', 'Student success', 'Mini-lessons', 'Early bird'],
                'is_active' => true,
            ],

            // Services
            [
                'industry' => 'services',
                'sub_industry' => 'professional',
                'avg_health_score' => 48,
                'avg_conversion_rate' => 2.0,
                'avg_engagement_rate' => 2.8,
                'avg_response_time_minutes' => 60,
                'avg_repeat_purchase_rate' => 50,
                'top_health_score' => 82,
                'top_conversion_rate' => 7,
                'top_engagement_rate' => 6,
                'top_response_time_minutes' => 15,
                'top_repeat_purchase_rate' => 80,
                'optimal_post_frequency_weekly' => 4,
                'optimal_stories_daily' => 4,
                'optimal_caption_length' => 180,
                'optimal_hashtag_count' => 12,
                'optimal_posting_times' => ['09:00', '12:00', '17:00', '20:00'],
                'proven_tactics' => ['Case studies', 'Testimonials', 'Free consultations', 'Portfolio'],
                'is_active' => true,
            ],

            // Technology
            [
                'industry' => 'technology',
                'sub_industry' => 'software',
                'avg_health_score' => 52,
                'avg_conversion_rate' => 2.2,
                'avg_engagement_rate' => 2.5,
                'avg_response_time_minutes' => 90,
                'avg_repeat_purchase_rate' => 60,
                'top_health_score' => 90,
                'top_conversion_rate' => 8,
                'top_engagement_rate' => 5,
                'top_response_time_minutes' => 20,
                'top_repeat_purchase_rate' => 85,
                'optimal_post_frequency_weekly' => 4,
                'optimal_stories_daily' => 3,
                'optimal_caption_length' => 200,
                'optimal_hashtag_count' => 10,
                'optimal_posting_times' => ['10:00', '14:00', '18:00', '21:00'],
                'proven_tactics' => ['Product demos', 'Feature updates', 'Tech tips', 'Free trials'],
                'is_active' => true,
            ],

            // Real Estate
            [
                'industry' => 'real_estate',
                'sub_industry' => 'residential',
                'avg_health_score' => 42,
                'avg_conversion_rate' => 0.8,
                'avg_engagement_rate' => 2.0,
                'avg_response_time_minutes' => 120,
                'avg_repeat_purchase_rate' => 15,
                'top_health_score' => 78,
                'top_conversion_rate' => 3,
                'top_engagement_rate' => 4,
                'top_response_time_minutes' => 30,
                'top_repeat_purchase_rate' => 35,
                'optimal_post_frequency_weekly' => 5,
                'optimal_stories_daily' => 6,
                'optimal_caption_length' => 200,
                'optimal_hashtag_count' => 15,
                'optimal_posting_times' => ['09:00', '12:00', '17:00', '20:00'],
                'proven_tactics' => ['Virtual tours', 'Neighborhood guides', 'Market updates', 'Open house'],
                'is_active' => true,
            ],

            // Travel
            [
                'industry' => 'travel',
                'sub_industry' => 'tourism',
                'avg_health_score' => 50,
                'avg_conversion_rate' => 1.8,
                'avg_engagement_rate' => 5.5,
                'avg_response_time_minutes' => 90,
                'avg_repeat_purchase_rate' => 35,
                'top_health_score' => 88,
                'top_conversion_rate' => 6,
                'top_engagement_rate' => 12,
                'top_response_time_minutes' => 20,
                'top_repeat_purchase_rate' => 60,
                'optimal_post_frequency_weekly' => 6,
                'optimal_stories_daily' => 8,
                'optimal_caption_length' => 180,
                'optimal_hashtag_count' => 25,
                'optimal_posting_times' => ['09:00', '13:00', '18:00', '21:00'],
                'proven_tactics' => ['Destination highlights', 'Traveler stories', 'Early booking', 'UGC'],
                'is_active' => true,
            ],
        ];
    }
}
