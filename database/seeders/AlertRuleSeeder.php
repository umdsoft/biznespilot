<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Revenue Alerts
            [
                'rule_code' => 'revenue_drop_critical',
                'rule_name_uz' => 'Daromad keskin tushishi',
                'rule_name_en' => 'Critical Revenue Drop',
                'description_uz' => 'Daromad 25% dan ko\'proq tushganda ogohlantirish',
                'alert_type' => 'metric_change',
                'metric_code' => 'revenue',
                'condition' => 'change_down',
                'threshold_percent' => 25,
                'comparison_period' => 'week',
                'severity' => 'critical',
                'message_template_uz' => 'Daromad {percent}% ga tushdi. Joriy: {current}, Oldingi: {previous}',
                'action_suggestion_uz' => 'Darhol savdo va marketing strategiyasini ko\'rib chiqing',
            ],
            [
                'rule_code' => 'revenue_drop_high',
                'rule_name_uz' => 'Daromad tushishi',
                'rule_name_en' => 'Revenue Drop',
                'description_uz' => 'Daromad 15% dan ko\'proq tushganda ogohlantirish',
                'alert_type' => 'metric_change',
                'metric_code' => 'revenue',
                'condition' => 'change_down',
                'threshold_percent' => 15,
                'comparison_period' => 'week',
                'severity' => 'high',
                'message_template_uz' => 'Daromad {percent}% ga tushdi. Joriy: {current}, Oldingi: {previous}',
                'action_suggestion_uz' => 'Savdo jarayonlarini tahlil qiling',
            ],

            // Lead Alerts
            [
                'rule_code' => 'leads_drop_high',
                'rule_name_uz' => 'Lidlar kamayishi',
                'rule_name_en' => 'Leads Drop',
                'description_uz' => 'Lidlar soni 30% dan ko\'proq kamaysa',
                'alert_type' => 'metric_change',
                'metric_code' => 'leads',
                'condition' => 'change_down',
                'threshold_percent' => 30,
                'comparison_period' => 'week',
                'severity' => 'high',
                'message_template_uz' => 'Lidlar {percent}% ga kamaydi. Joriy: {current}, Oldingi: {previous}',
                'action_suggestion_uz' => 'Marketing kanallarini tekshiring',
            ],
            [
                'rule_code' => 'leads_surge',
                'rule_name_uz' => 'Lidlar ko\'payishi',
                'rule_name_en' => 'Leads Surge',
                'description_uz' => 'Lidlar soni 50% dan ko\'proq oshsa',
                'alert_type' => 'metric_change',
                'metric_code' => 'leads',
                'condition' => 'change_up',
                'threshold_percent' => 50,
                'comparison_period' => 'week',
                'severity' => 'low',
                'message_template_uz' => 'Ajoyib! Lidlar {percent}% ga oshdi. Joriy: {current}',
                'action_suggestion_uz' => 'Muvaffaqiyatli kampaniyani tahlil qiling va davom ettiring',
            ],

            // CAC Alerts
            [
                'rule_code' => 'cac_increase_high',
                'rule_name_uz' => 'CAC oshishi',
                'rule_name_en' => 'CAC Increase',
                'description_uz' => 'Mijoz jalb qilish narxi 15% dan oshganda',
                'alert_type' => 'metric_change',
                'metric_code' => 'cac',
                'condition' => 'change_up',
                'threshold_percent' => 15,
                'comparison_period' => 'week',
                'severity' => 'high',
                'message_template_uz' => 'CAC {percent}% ga oshdi. Joriy: {current} UZS',
                'action_suggestion_uz' => 'Reklama xarajatlarini optimallashtiring',
            ],

            // Engagement Alerts
            [
                'rule_code' => 'engagement_drop',
                'rule_name_uz' => 'Engagement pasayishi',
                'rule_name_en' => 'Engagement Drop',
                'description_uz' => 'Engagement rate 20% ga tushganda',
                'alert_type' => 'metric_change',
                'metric_code' => 'engagement_rate',
                'condition' => 'change_down',
                'threshold_percent' => 20,
                'comparison_period' => 'week',
                'severity' => 'medium',
                'message_template_uz' => 'Engagement {percent}% ga tushdi. Joriy: {current}%',
                'action_suggestion_uz' => 'Kontent strategiyasini qayta ko\'rib chiqing',
            ],

            // ROAS Alerts
            [
                'rule_code' => 'roas_low',
                'rule_name_uz' => 'ROAS past',
                'rule_name_en' => 'Low ROAS',
                'description_uz' => 'ROAS 2x dan past bo\'lganda',
                'alert_type' => 'threshold',
                'metric_code' => 'roas',
                'condition' => 'less_than',
                'threshold_value' => 2,
                'comparison_period' => 'week',
                'severity' => 'high',
                'message_template_uz' => 'ROAS {current}x — juda past. Minimal: 2x',
                'action_suggestion_uz' => 'Reklama kampaniyalarini optimallashtiring yoki to\'xtating',
            ],

            // Goal Achievement
            [
                'rule_code' => 'weekly_goal_achieved',
                'rule_name_uz' => 'Haftalik maqsad bajarildi',
                'rule_name_en' => 'Weekly Goal Achieved',
                'description_uz' => 'Haftalik maqsad 100% bajarilganda',
                'alert_type' => 'goal',
                'metric_code' => 'weekly_target',
                'condition' => 'greater_than',
                'threshold_percent' => 100,
                'comparison_period' => 'week',
                'severity' => 'low',
                'message_template_uz' => 'Tabriklaymiz! Haftalik maqsad {percent}% bajarildi!',
                'action_suggestion_uz' => 'Jamoani tabriklang va muvaffaqiyatni tahlil qiling',
            ],
            [
                'rule_code' => 'monthly_goal_achieved',
                'rule_name_uz' => 'Oylik maqsad bajarildi',
                'rule_name_en' => 'Monthly Goal Achieved',
                'description_uz' => 'Oylik maqsad 100% bajarilganda',
                'alert_type' => 'goal',
                'metric_code' => 'monthly_target',
                'condition' => 'greater_than',
                'threshold_percent' => 100,
                'comparison_period' => 'month',
                'severity' => 'low',
                'message_template_uz' => 'Ajoyib! Oylik maqsad {percent}% bajarildi!',
                'action_suggestion_uz' => 'Natijalarni hujjatlashtiring va keyingi oy uchun maqsadlarni belgilang',
            ],

            // Funnel Alerts
            [
                'rule_code' => 'conversion_drop',
                'rule_name_uz' => 'Konversiya pasayishi',
                'rule_name_en' => 'Conversion Drop',
                'description_uz' => 'Konversiya rate 15% ga tushganda',
                'alert_type' => 'metric_change',
                'metric_code' => 'conversion_rate',
                'condition' => 'change_down',
                'threshold_percent' => 15,
                'comparison_period' => 'week',
                'severity' => 'high',
                'message_template_uz' => 'Konversiya {percent}% ga tushdi. Joriy: {current}%',
                'action_suggestion_uz' => 'Savdo jarayonini va funnel bosqichlarini tekshiring',
            ],

            // Budget Alert
            [
                'rule_code' => 'budget_overspend',
                'rule_name_uz' => 'Byudjet oshib ketdi',
                'rule_name_en' => 'Budget Overspend',
                'description_uz' => 'Byudjet 100% dan oshganda',
                'alert_type' => 'threshold',
                'metric_code' => 'budget_spent',
                'condition' => 'greater_than',
                'threshold_percent' => 100,
                'comparison_period' => 'month',
                'severity' => 'critical',
                'message_template_uz' => 'Byudjet {percent}% ga oshib ketdi!',
                'action_suggestion_uz' => 'Darhol xarajatlarni to\'xtating va qayta taqsimlang',
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('alert_rules')->insert(array_merge($rule, [
                'id' => Str::uuid(),
                'business_id' => null, // System default
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
