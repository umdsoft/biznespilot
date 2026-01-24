<?php

namespace App\Services\Reports;

use App\Models\Business;

class InsightEngineService
{
    public function generate(Business $business, array $metrics, array $trends): array
    {
        $insights = [];
        $recommendations = [];

        // Sales insights
        if (isset($metrics['sales'])) {
            $sales = $metrics['sales'];

            if ($sales['total_count'] > 0) {
                $insights[] = [
                    'type' => 'sales',
                    'title' => 'Sotuv ko\'rsatkichlari',
                    'description' => sprintf(
                        'Tanlangan davrda %d ta sotuv amalga oshirildi, umumiy daromad %s so\'m.',
                        $sales['total_count'],
                        number_format($sales['total_revenue'], 0, '.', ' ')
                    ),
                    'priority' => 'info',
                ];
            } else {
                $insights[] = [
                    'type' => 'sales',
                    'title' => 'Sotuv yo\'q',
                    'description' => 'Tanlangan davrda sotuvlar qayd etilmagan.',
                    'priority' => 'warning',
                ];

                $recommendations[] = [
                    'type' => 'action',
                    'title' => 'Sotuvlarni kuzatish',
                    'description' => 'Sotuvlarni tizimga kiritishni boshlang yoki mavjud sotuvlarni import qiling.',
                    'action_url' => '/business/sales',
                    'priority' => 'high',
                ];
            }
        }

        // Lead insights
        if (isset($metrics['leads'])) {
            $leads = $metrics['leads'];

            if ($leads['conversion_rate'] > 0) {
                $insights[] = [
                    'type' => 'leads',
                    'title' => 'Lead konversiyasi',
                    'description' => sprintf(
                        'Lead konversiya darajasi: %.1f%%. Jami %d ta leaddan %d tasi mijozga aylandi.',
                        $leads['conversion_rate'],
                        $leads['total_leads'],
                        $leads['converted_leads']
                    ),
                    'priority' => $leads['conversion_rate'] >= 10 ? 'success' : 'warning',
                ];
            }
        }

        // Trend insights
        if (isset($trends['period_comparison'])) {
            $comparison = $trends['period_comparison'];

            if ($comparison['trend'] === 'up') {
                $insights[] = [
                    'type' => 'trend',
                    'title' => 'O\'sish tendensiyasi',
                    'description' => sprintf(
                        'Sotuv hajmi oldingi davrga nisbatan %.1f%% ga oshgan.',
                        $comparison['change_percent']
                    ),
                    'priority' => 'success',
                ];
            } elseif ($comparison['trend'] === 'down') {
                $insights[] = [
                    'type' => 'trend',
                    'title' => 'Pasayish tendensiyasi',
                    'description' => sprintf(
                        'Sotuv hajmi oldingi davrga nisbatan %.1f%% ga kamaygan.',
                        abs($comparison['change_percent'])
                    ),
                    'priority' => 'danger',
                ];

                $recommendations[] = [
                    'type' => 'analysis',
                    'title' => 'Sabablarni tahlil qiling',
                    'description' => 'Sotuv pasayishi sabablarini aniqlash uchun raqobatchilar va bozor holatini tahlil qiling.',
                    'priority' => 'high',
                ];
            }
        }

        // Default recommendation if empty
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'general',
                'title' => 'Ma\'lumotlarni boyiting',
                'description' => 'Yanada aniqroq tavsiyalar olish uchun ko\'proq ma\'lumot kiriting.',
                'priority' => 'medium',
            ];
        }

        return [
            'insights' => $insights,
            'recommendations' => $recommendations,
        ];
    }
}
