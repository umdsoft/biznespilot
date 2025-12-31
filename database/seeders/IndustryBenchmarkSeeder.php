<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndustryBenchmarkSeeder extends Seeder
{
    public function run(): void
    {
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
                        'id' => Str::uuid()->toString(),
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
}
