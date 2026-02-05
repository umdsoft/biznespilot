<?php

namespace Database\Seeders;

use App\Models\KpiTemplate;
use Illuminate\Database\Seeder;

class KpiTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        KpiTemplate::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $kpis = [
            // ==================== UNIVERSAL / MARKETING KPIs ====================
            ['name' => 'Instagram Follower Growth Rate', 'description' => 'Instagram followerlar oylik o\'sish foizi', 'category' => 'marketing', 'measurement_unit' => '%', 'target_value' => 10, 'frequency' => 'monthly'],
            ['name' => 'Instagram Engagement Rate', 'description' => 'Kontent bilan muloqot qiladigan followerlar foizi', 'category' => 'marketing', 'measurement_unit' => '%', 'target_value' => 5, 'frequency' => 'daily'],
            ['name' => 'DM Response Time', 'description' => 'Direct messagelarga javob berish o\'rtacha vaqti (daqiqada)', 'category' => 'marketing', 'measurement_unit' => 'daqiqa', 'target_value' => 15, 'frequency' => 'daily'],
            ['name' => 'Lead Volume', 'description' => 'Yaratilgan lidlar soni (DM + Izoh + Qo\'ng\'iroq + Forma)', 'category' => 'marketing', 'measurement_unit' => 'dona', 'target_value' => 50, 'frequency' => 'daily'],
            ['name' => 'Google Review Rating', 'description' => 'Google sharhlari soni va o\'rtacha reyting', 'category' => 'marketing', 'measurement_unit' => 'dona', 'target_value' => 4.5, 'frequency' => 'monthly'],

            // ==================== SALES KPIs ====================
            ['name' => 'Conversion Rate (Lead to Customer)', 'description' => 'To\'lovchi mijozga aylanadigan lidlar foizi', 'category' => 'sales', 'measurement_unit' => '%', 'target_value' => 20, 'frequency' => 'weekly'],
            ['name' => 'Average Check Size', 'description' => 'Har bir mijoz tranzaksiyasi uchun o\'rtacha daromad', 'category' => 'sales', 'measurement_unit' => 'UZS', 'target_value' => 150000, 'frequency' => 'daily'],
            ['name' => 'Booking Conversion Rate', 'description' => 'Tasdiqlangan rezervatsiyaga aylanadigan so\'rovlar foizi', 'category' => 'sales', 'measurement_unit' => '%', 'target_value' => 60, 'frequency' => 'weekly'],
            ['name' => 'Pre-Order Conversion Rate', 'description' => 'Oldindan buyurtma beriladigan mahsulotlar foizi', 'category' => 'sales', 'measurement_unit' => '%', 'target_value' => 30, 'frequency' => 'monthly'],
            ['name' => 'Average Order Value', 'description' => 'Har bir buyurtma uchun o\'rtacha xarajat', 'category' => 'sales', 'measurement_unit' => 'UZS', 'target_value' => 200000, 'frequency' => 'weekly'],
            ['name' => 'Website Conversion Rate', 'description' => 'Saytga kirganlardan xarid qilganlar foizi', 'category' => 'sales', 'measurement_unit' => '%', 'target_value' => 3, 'frequency' => 'daily'],
            ['name' => 'Revenue Per Visitor', 'description' => 'Tashrif buyuruvchi uchun o\'rtacha daromad', 'category' => 'sales', 'measurement_unit' => 'UZS', 'target_value' => 5000, 'frequency' => 'daily'],

            // ==================== FINANCIAL KPIs ====================
            ['name' => 'Customer Acquisition Cost (CAC)', 'description' => 'Umumiy marketing xarajat / yangi mijozlar soni', 'category' => 'financial', 'measurement_unit' => 'UZS', 'target_value' => 50000, 'frequency' => 'monthly'],
            ['name' => 'Monthly Revenue', 'description' => 'Oyda yaratilgan umumiy daromad', 'category' => 'financial', 'measurement_unit' => 'UZS', 'target_value' => 50000000, 'frequency' => 'monthly'],

            // ==================== OPERATIONAL KPIs ====================
            ['name' => 'Table Turnover Rate', 'description' => 'Xizmat davrida stolning band bo\'lish o\'rtacha soni', 'category' => 'operational', 'measurement_unit' => 'marta', 'target_value' => 3, 'frequency' => 'daily'],
            ['name' => 'No-Show Rate', 'description' => 'Mijozlar kelmaydigan tasdiqlangan rezervatsiyalar foizi', 'category' => 'operational', 'measurement_unit' => '%', 'target_value' => 5, 'frequency' => 'weekly'],
            ['name' => 'Cart Abandonment Rate', 'description' => 'Savatni tashlab ketgan foydalanuvchilar foizi', 'category' => 'operational', 'measurement_unit' => '%', 'target_value' => 30, 'frequency' => 'daily'],
            ['name' => 'Collection Sell-Through Rate', 'description' => 'Mavsumda sotilgan kolleksiya mahsulotlari foizi', 'category' => 'operational', 'measurement_unit' => '%', 'target_value' => 70, 'frequency' => 'monthly'],
            ['name' => 'Product Return Rate', 'description' => 'Qaytarilgan sotilgan mahsulotlar foizi', 'category' => 'operational', 'measurement_unit' => '%', 'target_value' => 5, 'frequency' => 'monthly'],
            ['name' => 'Checkout Completion Rate', 'description' => 'Checkout boshlagan va tugatganlar foizi', 'category' => 'operational', 'measurement_unit' => '%', 'target_value' => 70, 'frequency' => 'daily'],

            // ==================== RETENTION KPIs ====================
            ['name' => 'Repeat Visit Rate', 'description' => '3 oy ichida qaytib keladigan mijozlar foizi', 'category' => 'retention', 'measurement_unit' => '%', 'target_value' => 40, 'frequency' => 'monthly'],
            ['name' => 'Repeat Purchase Rate', 'description' => 'Bir necha marta xarid qiladigan mijozlar foizi', 'category' => 'retention', 'measurement_unit' => '%', 'target_value' => 30, 'frequency' => 'monthly'],
            ['name' => 'Customer Lifetime Value', 'description' => 'Mijozdan kutilayotgan umumiy daromad', 'category' => 'retention', 'measurement_unit' => 'UZS', 'target_value' => 2000000, 'frequency' => 'quarterly'],
            ['name' => 'Membership Retention Rate', 'description' => 'A\'zolikni saqlab qolgan mijozlar foizi', 'category' => 'retention', 'measurement_unit' => '%', 'target_value' => 80, 'frequency' => 'monthly'],
            ['name' => 'Loyalty Program Sign-ups', 'description' => 'Yangi sodiqlik dasturi a\'zolari soni', 'category' => 'retention', 'measurement_unit' => 'dona', 'target_value' => 20, 'frequency' => 'weekly'],
        ];

        foreach ($kpis as $kpi) {
            $kpi['is_active'] = true;
            KpiTemplate::create($kpi);
        }

        $this->command->info('KPI Templates seeded: ' . count($kpis) . ' templates');
    }
}
