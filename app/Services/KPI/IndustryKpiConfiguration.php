<?php

namespace App\Services\KPI;

/**
 * Industry-Specific KPI Configuration
 *
 * Har bir biznes kategoriyasi uchun maxsus KPI'lar va ularning ko'rsatkichlari
 */
class IndustryKpiConfiguration
{
    /**
     * Get KPIs for specific industry
     */
    public static function getIndustryKpis(string $industryCode): array
    {
        $allKpis = self::getAllKpiDefinitions();
        $industryConfig = self::getIndustryConfiguration($industryCode);

        return array_map(function($kpiCode) use ($allKpis) {
            return $allKpis[$kpiCode] ?? null;
        }, $industryConfig['priority_kpis']);
    }

    /**
     * Industry-specific configurations
     */
    protected static function getIndustryConfiguration(string $industryCode): array
    {
        $configurations = [
            // E-commerce / Online savdo
            'ecommerce' => [
                'name' => 'E-commerce',
                'priority_kpis' => [
                    'revenue', 'conversion_rate', 'avg_order_value', 'cart_abandonment',
                    'customer_acquisition_cost', 'customer_lifetime_value', 'repeat_purchase_rate',
                    'website_traffic', 'bounce_rate', 'roi'
                ],
                'dashboard_sections' => [
                    'Savdo Ko\'rsatkichlari' => ['revenue', 'avg_order_value', 'conversion_rate'],
                    'Mijozlar Tahlili' => ['customer_acquisition_cost', 'customer_lifetime_value', 'repeat_purchase_rate'],
                    'Veb-sayt Metrikasi' => ['website_traffic', 'bounce_rate', 'cart_abandonment'],
                    'Moliyaviy ROI' => ['roi', 'profit_margin']
                ]
            ],

            // Restaurant / Restoran
            'restaurant' => [
                'name' => 'Restoran',
                'priority_kpis' => [
                    'daily_revenue', 'avg_check_size', 'table_turnover', 'food_cost_percentage',
                    'labor_cost_percentage', 'customer_satisfaction', 'repeat_customer_rate',
                    'online_orders', 'delivery_orders', 'social_media_engagement'
                ],
                'dashboard_sections' => [
                    'Kunlik Savdo' => ['daily_revenue', 'avg_check_size', 'table_turnover'],
                    'Xarajatlar' => ['food_cost_percentage', 'labor_cost_percentage'],
                    'Mijozlar' => ['customer_satisfaction', 'repeat_customer_rate'],
                    'Online Buyurtmalar' => ['online_orders', 'delivery_orders']
                ]
            ],

            // Retail / Chakana savdo
            'retail' => [
                'name' => 'Chakana Savdo',
                'priority_kpis' => [
                    'sales_per_sqm', 'foot_traffic', 'conversion_rate', 'avg_transaction_value',
                    'inventory_turnover', 'stock_to_sales_ratio', 'shrinkage_rate',
                    'customer_retention', 'loyalty_program_signups', 'social_media_followers'
                ],
                'dashboard_sections' => [
                    'Savdo Samaradorligi' => ['sales_per_sqm', 'foot_traffic', 'conversion_rate'],
                    'Inventar Boshqaruvi' => ['inventory_turnover', 'stock_to_sales_ratio', 'shrinkage_rate'],
                    'Mijozlar Loyalligi' => ['customer_retention', 'loyalty_program_signups'],
                    'Marketing' => ['social_media_followers', 'email_open_rate']
                ]
            ],

            // Service / Xizmat ko'rsatish
            'service' => [
                'name' => 'Xizmat Ko\'rsatish',
                'priority_kpis' => [
                    'monthly_recurring_revenue', 'client_retention_rate', 'churn_rate',
                    'avg_project_value', 'utilization_rate', 'client_satisfaction_score',
                    'net_promoter_score', 'lead_conversion_rate', 'time_to_close'
                ],
                'dashboard_sections' => [
                    'Daromad' => ['monthly_recurring_revenue', 'avg_project_value'],
                    'Mijozlar' => ['client_retention_rate', 'churn_rate', 'client_satisfaction_score'],
                    'Savdo Quvuri' => ['lead_conversion_rate', 'time_to_close'],
                    'Operatsion' => ['utilization_rate', 'billable_hours']
                ]
            ],

            // SaaS / Dasturiy ta'minot
            'saas' => [
                'name' => 'SaaS',
                'priority_kpis' => [
                    'monthly_recurring_revenue', 'annual_recurring_revenue', 'churn_rate',
                    'customer_acquisition_cost', 'customer_lifetime_value', 'ltv_cac_ratio',
                    'active_users', 'user_engagement', 'feature_adoption', 'net_promoter_score'
                ],
                'dashboard_sections' => [
                    'SaaS Metrikasi' => ['monthly_recurring_revenue', 'annual_recurring_revenue', 'churn_rate'],
                    'Unit Economics' => ['customer_acquisition_cost', 'customer_lifetime_value', 'ltv_cac_ratio'],
                    'Foydalanuvchilar' => ['active_users', 'user_engagement', 'feature_adoption'],
                    'Sifat' => ['net_promoter_score', 'customer_satisfaction']
                ]
            ],

            // Beauty / Go'zallik saloni
            'beauty' => [
                'name' => 'Go\'zallik Saloni',
                'priority_kpis' => [
                    'daily_revenue', 'avg_service_price', 'client_retention_rate',
                    'rebooking_rate', 'service_utilization', 'product_sales_revenue',
                    'instagram_engagement', 'new_clients', 'client_satisfaction'
                ],
                'dashboard_sections' => [
                    'Savdo' => ['daily_revenue', 'avg_service_price', 'product_sales_revenue'],
                    'Mijozlar' => ['client_retention_rate', 'rebooking_rate', 'new_clients'],
                    'Operatsion' => ['service_utilization', 'staff_productivity'],
                    'Marketing' => ['instagram_engagement', 'referral_rate']
                ]
            ],

            // Fitness / Fitnes
            'fitness' => [
                'name' => 'Fitnes',
                'priority_kpis' => [
                    'monthly_memberships', 'membership_retention', 'avg_member_lifetime',
                    'class_attendance_rate', 'trainer_utilization', 'personal_training_revenue',
                    'new_member_signups', 'churn_rate', 'referral_rate'
                ],
                'dashboard_sections' => [
                    'A\'zolik' => ['monthly_memberships', 'membership_retention', 'new_member_signups'],
                    'Xizmatlar' => ['class_attendance_rate', 'personal_training_revenue', 'trainer_utilization'],
                    'Mijozlar' => ['avg_member_lifetime', 'churn_rate', 'referral_rate']
                ]
            ],

            // Default (umumiy)
            'default' => [
                'name' => 'Umumiy',
                'priority_kpis' => [
                    'revenue', 'profit_margin', 'customer_acquisition_cost',
                    'customer_lifetime_value', 'conversion_rate', 'customer_retention',
                    'social_media_engagement', 'website_traffic', 'lead_generation'
                ],
                'dashboard_sections' => [
                    'Moliyaviy' => ['revenue', 'profit_margin'],
                    'Mijozlar' => ['customer_acquisition_cost', 'customer_lifetime_value', 'customer_retention'],
                    'Marketing' => ['social_media_engagement', 'website_traffic', 'lead_generation']
                ]
            ]
        ];

        return $configurations[$industryCode] ?? $configurations['default'];
    }

    /**
     * All KPI definitions with display metadata
     */
    protected static function getAllKpiDefinitions(): array
    {
        return [
            // ============ MOLIYAVIY KPI'LAR ============
            'revenue' => [
                'code' => 'revenue',
                'name' => 'Daromad',
                'name_en' => 'Revenue',
                'category' => 'financial',
                'unit' => 'currency',
                'format' => 'money',
                'description' => 'Umumiy daromad',
                'icon' => '💰',
                'color' => 'green',
                'good_direction' => 'up',
                'benchmark_type' => 'absolute'
            ],

            'daily_revenue' => [
                'code' => 'daily_revenue',
                'name' => 'Kunlik Daromad',
                'name_en' => 'Daily Revenue',
                'category' => 'financial',
                'unit' => 'currency',
                'format' => 'money',
                'description' => 'Kunlik o\'rtacha daromad',
                'icon' => '💵',
                'color' => 'green',
                'good_direction' => 'up'
            ],

            'profit_margin' => [
                'code' => 'profit_margin',
                'name' => 'Foyda Margini',
                'name_en' => 'Profit Margin',
                'category' => 'financial',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Sof foyda foizi',
                'icon' => '📊',
                'color' => 'blue',
                'good_direction' => 'up',
                'benchmark_value' => 20
            ],

            'roi' => [
                'code' => 'roi',
                'name' => 'ROI',
                'name_en' => 'Return on Investment',
                'category' => 'financial',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Investitsiya qaytimi',
                'icon' => '📈',
                'color' => 'green',
                'good_direction' => 'up'
            ],

            // ============ MIJOZLAR KPI'LARI ============
            'customer_acquisition_cost' => [
                'code' => 'customer_acquisition_cost',
                'name' => 'Mijoz Olish Narxi (CAC)',
                'name_en' => 'Customer Acquisition Cost',
                'category' => 'customer',
                'unit' => 'currency',
                'format' => 'money',
                'description' => 'Bir mijozni jalb qilish uchun sarflangan xarajat',
                'icon' => '💸',
                'color' => 'orange',
                'good_direction' => 'down'
            ],

            'customer_lifetime_value' => [
                'code' => 'customer_lifetime_value',
                'name' => 'Mijoz Umr Bo\'yi Qiymati (LTV)',
                'name_en' => 'Customer Lifetime Value',
                'category' => 'customer',
                'unit' => 'currency',
                'format' => 'money',
                'description' => 'Bir mijozdan kutilayotgan umumiy daromad',
                'icon' => '💎',
                'color' => 'purple',
                'good_direction' => 'up'
            ],

            'customer_retention' => [
                'code' => 'customer_retention',
                'name' => 'Mijozlarni Ushlab Qolish',
                'name_en' => 'Customer Retention Rate',
                'category' => 'customer',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Qaytib kelgan mijozlar foizi',
                'icon' => '🔄',
                'color' => 'blue',
                'good_direction' => 'up',
                'benchmark_value' => 70
            ],

            'churn_rate' => [
                'code' => 'churn_rate',
                'name' => 'Mijozlar Ketish Sur\'ati',
                'name_en' => 'Churn Rate',
                'category' => 'customer',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Ketgan mijozlar foizi',
                'icon' => '❌',
                'color' => 'red',
                'good_direction' => 'down',
                'benchmark_value' => 5
            ],

            // ============ KONVERSIYA KPI'LARI ============
            'conversion_rate' => [
                'code' => 'conversion_rate',
                'name' => 'Konversiya Sur\'ati',
                'name_en' => 'Conversion Rate',
                'category' => 'conversion',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Mijozga aylanganlar foizi',
                'icon' => '🎯',
                'color' => 'green',
                'good_direction' => 'up',
                'benchmark_value' => 3
            ],

            'lead_conversion_rate' => [
                'code' => 'lead_conversion_rate',
                'name' => 'Lead Konversiya',
                'name_en' => 'Lead Conversion Rate',
                'category' => 'conversion',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Leadlardan sotuvga aylangan foiz',
                'icon' => '🎪',
                'color' => 'blue',
                'good_direction' => 'up'
            ],

            // ============ MARKETING KPI'LARI ============
            'social_media_engagement' => [
                'code' => 'social_media_engagement',
                'name' => 'Ijtimoiy Tarmoq Faolligi',
                'name_en' => 'Social Media Engagement',
                'category' => 'marketing',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Like, comment, share foizi',
                'icon' => '❤️',
                'color' => 'pink',
                'good_direction' => 'up',
                'benchmark_value' => 3
            ],

            'instagram_engagement' => [
                'code' => 'instagram_engagement',
                'name' => 'Instagram Engagement',
                'name_en' => 'Instagram Engagement',
                'category' => 'marketing',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Instagram\'da faollik darajasi',
                'icon' => '📱',
                'color' => 'gradient',
                'good_direction' => 'up'
            ],

            'website_traffic' => [
                'code' => 'website_traffic',
                'name' => 'Veb-sayt Trafigi',
                'name_en' => 'Website Traffic',
                'category' => 'marketing',
                'unit' => 'number',
                'format' => 'number',
                'description' => 'Oylik tashrif buyuruvchilar soni',
                'icon' => '🌐',
                'color' => 'blue',
                'good_direction' => 'up'
            ],

            // ============ E-COMMERCE MAXSUS ============
            'avg_order_value' => [
                'code' => 'avg_order_value',
                'name' => 'O\'rtacha Buyurtma Qiymati',
                'name_en' => 'Average Order Value',
                'category' => 'ecommerce',
                'unit' => 'currency',
                'format' => 'money',
                'description' => 'Bir buyurtmaning o\'rtacha qiymati',
                'icon' => '🛒',
                'color' => 'green',
                'good_direction' => 'up'
            ],

            'cart_abandonment' => [
                'code' => 'cart_abandonment',
                'name' => 'Tashlab Ketilgan Savatlar',
                'name_en' => 'Cart Abandonment Rate',
                'category' => 'ecommerce',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'To\'lovga o\'tmagan savatlar foizi',
                'icon' => '🛒',
                'color' => 'red',
                'good_direction' => 'down',
                'benchmark_value' => 30
            ],

            // ============ RESTAURANT MAXSUS ============
            'table_turnover' => [
                'code' => 'table_turnover',
                'name' => 'Stol Aylanishi',
                'name_en' => 'Table Turnover',
                'category' => 'restaurant',
                'unit' => 'number',
                'format' => 'decimal',
                'description' => 'Kunlik bitta stol aylanish soni',
                'icon' => '🪑',
                'color' => 'purple',
                'good_direction' => 'up'
            ],

            'food_cost_percentage' => [
                'code' => 'food_cost_percentage',
                'name' => 'Ovqat Xarajatlari %',
                'name_en' => 'Food Cost Percentage',
                'category' => 'restaurant',
                'unit' => 'percentage',
                'format' => 'percent',
                'description' => 'Daromaddan ovqat xarajatlari ulushi',
                'icon' => '🍽️',
                'color' => 'orange',
                'good_direction' => 'down',
                'benchmark_value' => 30
            ],

            // ============ SAAS MAXSUS ============
            'monthly_recurring_revenue' => [
                'code' => 'monthly_recurring_revenue',
                'name' => 'Oylik Qaytaruvchi Daromad (MRR)',
                'name_en' => 'Monthly Recurring Revenue',
                'category' => 'saas',
                'unit' => 'currency',
                'format' => 'money',
                'description' => 'Obuna to\'lovlaridan oylik daromad',
                'icon' => '🔄',
                'color' => 'green',
                'good_direction' => 'up'
            ],

            'ltv_cac_ratio' => [
                'code' => 'ltv_cac_ratio',
                'name' => 'LTV/CAC Nisbati',
                'name_en' => 'LTV to CAC Ratio',
                'category' => 'saas',
                'unit' => 'ratio',
                'format' => 'decimal',
                'description' => 'Mijoz qiymati va olish narxi nisbati',
                'icon' => '⚖️',
                'color' => 'purple',
                'good_direction' => 'up',
                'benchmark_value' => 3
            ]
        ];
    }

    /**
     * Get display color based on performance
     */
    public static function getPerformanceColor(float $actual, float $target, string $direction): string
    {
        $performance = ($actual / $target) * 100;

        if ($direction === 'up') {
            if ($performance >= 100) return 'green';
            if ($performance >= 80) return 'yellow';
            return 'red';
        } else {
            if ($performance <= 100) return 'green';
            if ($performance <= 120) return 'yellow';
            return 'red';
        }
    }

    /**
     * Get performance status text
     */
    public static function getPerformanceStatus(float $actual, float $target, string $direction): string
    {
        $performance = ($actual / $target) * 100;

        if ($direction === 'up') {
            if ($performance >= 100) return 'Maqsadga erishildi';
            if ($performance >= 80) return 'Yaxshi';
            if ($performance >= 50) return 'O\'rtacha';
            return 'Yomon';
        } else {
            if ($performance <= 100) return 'Maqsadga erishildi';
            if ($performance <= 120) return 'Yaxshi';
            if ($performance <= 150) return 'O\'rtacha';
            return 'Yomon';
        }
    }
}
