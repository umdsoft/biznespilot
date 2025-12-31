<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Get first business for seeding (or null for global rules)
        $businessId = DB::table('businesses')->value('id');

        if (!$businessId) {
            $this->command->warn('No business found. Please run BusinessSeeder first.');
            return;
        }

        $rules = [
            // Revenue Alerts
            [
                'name' => 'Daromad keskin tushishi ogohlantirishi',
                'metric' => 'revenue',
                'condition' => 'lt',
                'threshold' => -25.00,
                'severity' => 'critical',
                'notification_channels' => json_encode(['email', 'telegram']),
            ],
            [
                'name' => 'Daromad tushishi ogohlantirishi',
                'metric' => 'revenue',
                'condition' => 'lt',
                'threshold' => -15.00,
                'severity' => 'warning',
                'notification_channels' => json_encode(['email']),
            ],
            // Lead Alerts
            [
                'name' => 'Lidlar kamayishi ogohlantirishi',
                'metric' => 'leads',
                'condition' => 'lt',
                'threshold' => -30.00,
                'severity' => 'warning',
                'notification_channels' => json_encode(['telegram']),
            ],
            [
                'name' => 'Lidlar ko\'payishi xabari',
                'metric' => 'leads',
                'condition' => 'gt',
                'threshold' => 50.00,
                'severity' => 'info',
                'notification_channels' => json_encode(['email']),
            ],
            // CAC Alerts
            [
                'name' => 'CAC oshishi ogohlantirishi',
                'metric' => 'cac',
                'condition' => 'gt',
                'threshold' => 15.00,
                'severity' => 'warning',
                'notification_channels' => json_encode(['email']),
            ],
            // Engagement Alerts
            [
                'name' => 'Engagement pasayishi ogohlantirishi',
                'metric' => 'engagement_rate',
                'condition' => 'lt',
                'threshold' => -20.00,
                'severity' => 'warning',
                'notification_channels' => json_encode(['telegram']),
            ],
            // ROAS Alert
            [
                'name' => 'ROAS past ogohlantirishi',
                'metric' => 'roas',
                'condition' => 'lt',
                'threshold' => 2.00,
                'severity' => 'critical',
                'notification_channels' => json_encode(['email', 'telegram']),
            ],
            // Conversion Alert
            [
                'name' => 'Konversiya pasayishi ogohlantirishi',
                'metric' => 'conversion_rate',
                'condition' => 'lt',
                'threshold' => -15.00,
                'severity' => 'warning',
                'notification_channels' => json_encode(['email']),
            ],
            // Budget Alert
            [
                'name' => 'Byudjet oshib ketdi ogohlantirishi',
                'metric' => 'budget_spent',
                'condition' => 'gt',
                'threshold' => 100.00,
                'severity' => 'critical',
                'notification_channels' => json_encode(['email', 'telegram', 'sms']),
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('alert_rules')->insert([
                'id' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'name' => $rule['name'],
                'metric' => $rule['metric'],
                'condition' => $rule['condition'],
                'threshold' => $rule['threshold'],
                'severity' => $rule['severity'],
                'notification_channels' => $rule['notification_channels'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Alert rules seeded successfully!');
    }
}
