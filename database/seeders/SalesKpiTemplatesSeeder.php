<?php

namespace Database\Seeders;

use App\Models\SalesKpiTemplateSet;
use Illuminate\Database\Seeder;

class SalesKpiTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            $this->getB2BSalesTemplate(),
            $this->getB2CRetailTemplate(),
            $this->getEdTechTemplate(),
            $this->getServicesTemplate(),
            $this->getRealEstateTemplate(),
        ];

        foreach ($templates as $template) {
            SalesKpiTemplateSet::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }

        $this->command->info('Sales KPI templates seeded successfully!');
    }

    /**
     * B2B Sotuv shabloni
     */
    protected function getB2BSalesTemplate(): array
    {
        return [
            'code' => 'b2b_sales',
            'name' => 'B2B Sotuv',
            'description' => 'Bizneslar orasidagi savdo uchun mo\'ljallangan. Uzoq sotish sikli, yuqori qiymatli bitimlar, ko\'p bosqichli jarayon.',
            'industry' => 'it_services',
            'icon' => 'building-office',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 1,
            'kpi_settings' => [
                [
                    'kpi_type' => 'leads_converted',
                    'name' => 'Sotuvga o\'tgan lidlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 25,
                    'target_min' => 5,
                    'target_good' => 8,
                    'target_excellent' => 12,
                ],
                [
                    'kpi_type' => 'revenue',
                    'name' => 'Umumiy sotuv summasi',
                    'measurement_unit' => 'currency',
                    'calculation_method' => 'sum',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 30,
                    'target_min' => 50000000,
                    'target_good' => 80000000,
                    'target_excellent' => 120000000,
                ],
                [
                    'kpi_type' => 'calls_made',
                    'name' => 'Qilingan qo\'ng\'iroqlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'calls',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 100,
                    'target_good' => 150,
                    'target_excellent' => 200,
                ],
                [
                    'kpi_type' => 'meetings_held',
                    'name' => 'O\'tkazilgan uchrashuvlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'tasks',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 10,
                    'target_good' => 15,
                    'target_excellent' => 20,
                ],
                [
                    'kpi_type' => 'conversion_rate',
                    'name' => 'Konversiya foizi',
                    'measurement_unit' => 'percentage',
                    'calculation_method' => 'rate',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 15,
                    'target_good' => 25,
                    'target_excellent' => 35,
                ],
            ],
            'bonus_settings' => [
                [
                    'bonus_type' => 'kpi_achievement',
                    'name' => 'KPI bonus',
                    'calculation_type' => 'percentage',
                    'base_amount' => 1000000,
                    'percentage' => 10,
                    'min_kpi_score' => 80,
                    'requires_approval' => true,
                    'tiers' => [
                        ['min' => 80, 'max' => 99, 'multiplier' => 1.0, 'name' => 'Standard'],
                        ['min' => 100, 'max' => 119, 'multiplier' => 1.2, 'name' => 'Excellent'],
                        ['min' => 120, 'max' => null, 'multiplier' => 1.5, 'name' => 'Accelerator'],
                    ],
                ],
                [
                    'bonus_type' => 'deal_commission',
                    'name' => 'Bitim komissiyasi',
                    'calculation_type' => 'percentage',
                    'base_amount' => 0,
                    'percentage' => 3,
                    'min_kpi_score' => 0,
                    'requires_approval' => true,
                ],
            ],
            'penalty_rules' => [
                [
                    'trigger_event' => 'lead_not_contacted_24h',
                    'name' => '24 soat ichida bog\'lanilmagan',
                    'category' => 'activity',
                    'severity' => 'medium',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['hours' => 24],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 50000,
                    'warning_threshold' => 2,
                ],
                [
                    'trigger_event' => 'crm_not_filled',
                    'name' => 'CRM to\'ldirilmagan',
                    'category' => 'quality',
                    'severity' => 'low',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['required_fields' => ['name', 'phone', 'region']],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 25000,
                    'warning_threshold' => 3,
                ],
                [
                    'trigger_event' => 'task_overdue',
                    'name' => 'Muddati o\'tgan vazifa',
                    'category' => 'activity',
                    'severity' => 'medium',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['overdue_days' => 1],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 30000,
                    'warning_threshold' => 2,
                ],
            ],
            'recommended_targets' => [
                'leads_converted' => ['junior' => 3, 'middle' => 5, 'senior' => 8],
                'revenue' => ['junior' => 30000000, 'middle' => 50000000, 'senior' => 80000000],
                'calls_made' => ['junior' => 120, 'middle' => 100, 'senior' => 80],
            ],
            'onboarding_tips' => [
                'B2B sotuv sikli odatda uzoq (1-6 oy) bo\'ladi',
                'Uchrashuvlar soni ko\'pincha qo\'ng\'iroqlardan muhimroq',
                'Konversiya foizi 15-25% oralig\'ida bo\'lishi normal',
                'Katta bitimlar uchun alohida komissiya belgilash tavsiya etiladi',
            ],
        ];
    }

    /**
     * B2C Retail shabloni
     */
    protected function getB2CRetailTemplate(): array
    {
        return [
            'code' => 'b2c_retail',
            'name' => 'B2C Chakana savdo',
            'description' => 'Yakuniy iste\'molchilarga sotish uchun. Tez sotish sikli, ko\'p sonli tranzaksiyalar, yuqori hajm.',
            'industry' => 'retail',
            'icon' => 'shopping-cart',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 2,
            'kpi_settings' => [
                [
                    'kpi_type' => 'deals_count',
                    'name' => 'Yopilgan bitimlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 30,
                    'target_min' => 30,
                    'target_good' => 45,
                    'target_excellent' => 60,
                ],
                [
                    'kpi_type' => 'revenue',
                    'name' => 'Umumiy sotuv',
                    'measurement_unit' => 'currency',
                    'calculation_method' => 'sum',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 25,
                    'target_min' => 20000000,
                    'target_good' => 30000000,
                    'target_excellent' => 45000000,
                ],
                [
                    'kpi_type' => 'calls_made',
                    'name' => 'Qilingan qo\'ng\'iroqlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'calls',
                    'period_type' => 'monthly',
                    'weight' => 20,
                    'target_min' => 200,
                    'target_good' => 300,
                    'target_excellent' => 400,
                ],
                [
                    'kpi_type' => 'avg_deal_size',
                    'name' => 'O\'rtacha chek',
                    'measurement_unit' => 'currency',
                    'calculation_method' => 'average',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 500000,
                    'target_good' => 700000,
                    'target_excellent' => 1000000,
                ],
                [
                    'kpi_type' => 'response_time',
                    'name' => 'Javob vaqti (daqiqa)',
                    'measurement_unit' => 'minutes',
                    'calculation_method' => 'average',
                    'data_source' => 'auto',
                    'period_type' => 'monthly',
                    'weight' => 10,
                    'target_min' => 30,
                    'target_good' => 15,
                    'target_excellent' => 5,
                ],
            ],
            'bonus_settings' => [
                [
                    'bonus_type' => 'kpi_achievement',
                    'name' => 'KPI bonus',
                    'calculation_type' => 'fixed',
                    'base_amount' => 500000,
                    'min_kpi_score' => 80,
                    'requires_approval' => false,
                    'tiers' => [
                        ['min' => 80, 'max' => 99, 'multiplier' => 1.0, 'name' => 'Standard'],
                        ['min' => 100, 'max' => 119, 'multiplier' => 1.3, 'name' => 'Super'],
                        ['min' => 120, 'max' => null, 'multiplier' => 1.6, 'name' => 'Champion'],
                    ],
                ],
                [
                    'bonus_type' => 'daily_target',
                    'name' => 'Kunlik maqsad',
                    'calculation_type' => 'fixed',
                    'base_amount' => 30000,
                    'min_kpi_score' => 100,
                    'requires_approval' => false,
                ],
            ],
            'penalty_rules' => [
                [
                    'trigger_event' => 'lead_not_contacted_24h',
                    'name' => 'Kechikkan javob',
                    'category' => 'activity',
                    'severity' => 'high',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['hours' => 2],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 20000,
                    'warning_threshold' => 1,
                ],
                [
                    'trigger_event' => 'task_overdue',
                    'name' => 'Vazifa kechiktirildi',
                    'category' => 'activity',
                    'severity' => 'medium',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['overdue_days' => 1],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 15000,
                    'warning_threshold' => 2,
                ],
            ],
            'recommended_targets' => [
                'deals_count' => ['junior' => 20, 'middle' => 30, 'senior' => 45],
                'revenue' => ['junior' => 15000000, 'middle' => 20000000, 'senior' => 30000000],
            ],
            'onboarding_tips' => [
                'B2C savdoda tezlik juda muhim - 5 daqiqa ichida javob bering',
                'Ko\'proq qo\'ng\'iroq = ko\'proq sotuv',
                'O\'rtacha chekni oshirish uchun qo\'shimcha mahsulotlar taklif qiling',
                'Kunlik maqsadlarni kuzatib boring',
            ],
        ];
    }

    /**
     * EdTech shabloni
     */
    protected function getEdTechTemplate(): array
    {
        return [
            'code' => 'edtech',
            'name' => 'EdTech / Ta\'lim',
            'description' => 'Ta\'lim xizmatlari va kurslar sotish uchun. O\'rtacha sotish sikli, yuqori qaytish qiymati.',
            'industry' => 'education',
            'icon' => 'academic-cap',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 3,
            'kpi_settings' => [
                [
                    'kpi_type' => 'leads_converted',
                    'name' => 'Ro\'yxatdan o\'tganlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 30,
                    'target_min' => 15,
                    'target_good' => 25,
                    'target_excellent' => 35,
                ],
                [
                    'kpi_type' => 'revenue',
                    'name' => 'Kurs sotuvlari',
                    'measurement_unit' => 'currency',
                    'calculation_method' => 'sum',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 25,
                    'target_min' => 30000000,
                    'target_good' => 50000000,
                    'target_excellent' => 75000000,
                ],
                [
                    'kpi_type' => 'calls_made',
                    'name' => 'Konsultatsiya qo\'ng\'iroqlari',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'calls',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 150,
                    'target_good' => 200,
                    'target_excellent' => 250,
                ],
                [
                    'kpi_type' => 'conversion_rate',
                    'name' => 'Konversiya',
                    'measurement_unit' => 'percentage',
                    'calculation_method' => 'rate',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 20,
                    'target_min' => 20,
                    'target_good' => 30,
                    'target_excellent' => 40,
                ],
                [
                    'kpi_type' => 'tasks_completed',
                    'name' => 'Follow-up vazifalar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'tasks',
                    'period_type' => 'monthly',
                    'weight' => 10,
                    'target_min' => 50,
                    'target_good' => 75,
                    'target_excellent' => 100,
                ],
            ],
            'bonus_settings' => [
                [
                    'bonus_type' => 'kpi_achievement',
                    'name' => 'Oylik bonus',
                    'calculation_type' => 'percentage',
                    'base_amount' => 800000,
                    'percentage' => 5,
                    'min_kpi_score' => 80,
                    'requires_approval' => true,
                    'tiers' => [
                        ['min' => 80, 'max' => 99, 'multiplier' => 1.0, 'name' => 'Yaxshi'],
                        ['min' => 100, 'max' => 119, 'multiplier' => 1.25, 'name' => 'A\'lo'],
                        ['min' => 120, 'max' => null, 'multiplier' => 1.5, 'name' => 'Ustoz'],
                    ],
                ],
                [
                    'bonus_type' => 'referral',
                    'name' => 'Tavsiya bonus',
                    'calculation_type' => 'fixed',
                    'base_amount' => 100000,
                    'min_kpi_score' => 0,
                    'requires_approval' => false,
                ],
            ],
            'penalty_rules' => [
                [
                    'trigger_event' => 'lead_not_contacted_48h',
                    'name' => '48 soat ichida bog\'lanilmagan',
                    'category' => 'activity',
                    'severity' => 'medium',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['hours' => 48],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 40000,
                    'warning_threshold' => 2,
                ],
                [
                    'trigger_event' => 'crm_not_filled',
                    'name' => 'Ma\'lumot to\'liq emas',
                    'category' => 'quality',
                    'severity' => 'low',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['required_fields' => ['name', 'phone', 'course_interest']],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 20000,
                    'warning_threshold' => 3,
                ],
            ],
            'recommended_targets' => [
                'leads_converted' => ['junior' => 10, 'middle' => 15, 'senior' => 25],
                'revenue' => ['junior' => 20000000, 'middle' => 30000000, 'senior' => 50000000],
            ],
            'onboarding_tips' => [
                'Ta\'lim sotuvida ishonch qurish muhim - maslahat bering, sotmang',
                'Demo darslar konversiyani 2x oshiradi',
                'Follow-up qo\'ng\'iroqlar 3-5 kun oralig\'ida bo\'lsin',
                'Ota-onalar bilan ham suhbatlashing (agar o\'quvchi kichik bo\'lsa)',
            ],
        ];
    }

    /**
     * Xizmatlar shabloni
     */
    protected function getServicesTemplate(): array
    {
        return [
            'code' => 'services',
            'name' => 'Professional Xizmatlar',
            'description' => 'Konsalting, yuridik, buxgalteriya va boshqa professional xizmatlar uchun.',
            'industry' => 'it_services',
            'icon' => 'briefcase',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 4,
            'kpi_settings' => [
                [
                    'kpi_type' => 'leads_converted',
                    'name' => 'Yangi mijozlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 25,
                    'target_min' => 5,
                    'target_good' => 8,
                    'target_excellent' => 12,
                ],
                [
                    'kpi_type' => 'revenue',
                    'name' => 'Shartnoma summasi',
                    'measurement_unit' => 'currency',
                    'calculation_method' => 'sum',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 30,
                    'target_min' => 40000000,
                    'target_good' => 60000000,
                    'target_excellent' => 90000000,
                ],
                [
                    'kpi_type' => 'meetings_held',
                    'name' => 'Konsultatsiyalar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'tasks',
                    'period_type' => 'monthly',
                    'weight' => 20,
                    'target_min' => 15,
                    'target_good' => 25,
                    'target_excellent' => 35,
                ],
                [
                    'kpi_type' => 'proposals_sent',
                    'name' => 'Yuborilgan takliflar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'tasks',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 10,
                    'target_good' => 15,
                    'target_excellent' => 20,
                ],
                [
                    'kpi_type' => 'conversion_rate',
                    'name' => 'Taklif qabul foizi',
                    'measurement_unit' => 'percentage',
                    'calculation_method' => 'rate',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 10,
                    'target_min' => 30,
                    'target_good' => 45,
                    'target_excellent' => 60,
                ],
            ],
            'bonus_settings' => [
                [
                    'bonus_type' => 'kpi_achievement',
                    'name' => 'KPI bonus',
                    'calculation_type' => 'percentage',
                    'base_amount' => 1500000,
                    'percentage' => 8,
                    'min_kpi_score' => 80,
                    'requires_approval' => true,
                    'tiers' => [
                        ['min' => 80, 'max' => 99, 'multiplier' => 1.0, 'name' => 'Standard'],
                        ['min' => 100, 'max' => 119, 'multiplier' => 1.2, 'name' => 'Professional'],
                        ['min' => 120, 'max' => null, 'multiplier' => 1.5, 'name' => 'Expert'],
                    ],
                ],
            ],
            'penalty_rules' => [
                [
                    'trigger_event' => 'lead_not_contacted_24h',
                    'name' => 'Javob kechiktirildi',
                    'category' => 'activity',
                    'severity' => 'medium',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['hours' => 24],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 50000,
                    'warning_threshold' => 2,
                ],
            ],
            'recommended_targets' => [
                'leads_converted' => ['junior' => 3, 'middle' => 5, 'senior' => 8],
            ],
            'onboarding_tips' => [
                'Professional xizmatlarda sifat miqdordan muhim',
                'Har bir konsultatsiyani yaxshi tayyorlang',
                'Taklifnomalar professional va batafsil bo\'lsin',
            ],
        ];
    }

    /**
     * Ko'chmas mulk shabloni
     */
    protected function getRealEstateTemplate(): array
    {
        return [
            'code' => 'real_estate',
            'name' => 'Ko\'chmas mulk',
            'description' => 'Kvartira, uy va tijorat ko\'chmas mulkini sotish uchun.',
            'industry' => 'real_estate',
            'icon' => 'home',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 5,
            'kpi_settings' => [
                [
                    'kpi_type' => 'deals_count',
                    'name' => 'Yopilgan bitimlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 25,
                    'target_min' => 2,
                    'target_good' => 4,
                    'target_excellent' => 6,
                ],
                [
                    'kpi_type' => 'revenue',
                    'name' => 'Sotuv summasi',
                    'measurement_unit' => 'currency',
                    'calculation_method' => 'sum',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 30,
                    'target_min' => 500000000,
                    'target_good' => 800000000,
                    'target_excellent' => 1200000000,
                ],
                [
                    'kpi_type' => 'calls_made',
                    'name' => 'Qo\'ng\'iroqlar',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'calls',
                    'period_type' => 'monthly',
                    'weight' => 15,
                    'target_min' => 150,
                    'target_good' => 200,
                    'target_excellent' => 300,
                ],
                [
                    'kpi_type' => 'meetings_held',
                    'name' => 'Ko\'rishlar (showings)',
                    'measurement_unit' => 'count',
                    'calculation_method' => 'count',
                    'data_source' => 'tasks',
                    'period_type' => 'monthly',
                    'weight' => 20,
                    'target_min' => 20,
                    'target_good' => 30,
                    'target_excellent' => 45,
                ],
                [
                    'kpi_type' => 'conversion_rate',
                    'name' => 'Ko\'rishdan sotuvga',
                    'measurement_unit' => 'percentage',
                    'calculation_method' => 'rate',
                    'data_source' => 'leads',
                    'period_type' => 'monthly',
                    'weight' => 10,
                    'target_min' => 5,
                    'target_good' => 10,
                    'target_excellent' => 15,
                ],
            ],
            'bonus_settings' => [
                [
                    'bonus_type' => 'deal_commission',
                    'name' => 'Komissiya',
                    'calculation_type' => 'percentage',
                    'percentage' => 1,
                    'min_kpi_score' => 0,
                    'requires_approval' => true,
                ],
                [
                    'bonus_type' => 'kpi_achievement',
                    'name' => 'KPI bonus',
                    'calculation_type' => 'fixed',
                    'base_amount' => 2000000,
                    'min_kpi_score' => 80,
                    'requires_approval' => true,
                    'tiers' => [
                        ['min' => 80, 'max' => 99, 'multiplier' => 1.0, 'name' => 'Standard'],
                        ['min' => 100, 'max' => 119, 'multiplier' => 1.3, 'name' => 'Pro'],
                        ['min' => 120, 'max' => null, 'multiplier' => 1.6, 'name' => 'Top Agent'],
                    ],
                ],
            ],
            'penalty_rules' => [
                [
                    'trigger_event' => 'lead_not_contacted_24h',
                    'name' => 'Tez javob bermagan',
                    'category' => 'activity',
                    'severity' => 'high',
                    'trigger_type' => 'auto',
                    'trigger_conditions' => ['hours' => 4],
                    'penalty_type' => 'fixed',
                    'penalty_amount' => 100000,
                    'warning_threshold' => 1,
                ],
            ],
            'recommended_targets' => [
                'deals_count' => ['junior' => 1, 'middle' => 2, 'senior' => 4],
            ],
            'onboarding_tips' => [
                'Ko\'chmas mulk sotuvida har bir lid juda qimmat',
                'Birinchi 4 soat ichida albatta bog\'laning',
                'Ko\'rishlarni professional o\'tkazing',
                'Qaror qabul qilishga vaqt bering, lekin kuzatib boring',
            ],
        ];
    }
}
