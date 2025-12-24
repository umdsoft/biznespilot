<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AIDiagnostic;

$diagnosticId = '019b4bdd-b0d3-726d-9a28-75fde4251276';
$diagnostic = AIDiagnostic::find($diagnosticId);

if (!$diagnostic) {
    echo "Diagnostic not found!\n";
    exit(1);
}

// Update ROI calculations
$diagnostic->roi_calculations = [
    'summary' => [
        'total_investment' => [
            'time_hours' => 5,
            'time_value_uzs' => 250000,
            'money_uzs' => 0,
            'total_uzs' => 250000,
        ],
        'total_monthly_return' => 15000000,
        'overall_roi_percent' => 5900,
        'payback_days' => 1,
    ],
    'per_action' => [
        [
            'id' => 1,
            'action' => 'Ideal mijozni aniqlash',
            'priority' => 1,
            'investment' => [
                'time' => '30 daqiqa',
                'time_value' => 25000,
                'money' => 0,
                'total' => 25000,
            ],
            'expected_return' => [
                'metric' => 'Konversiya',
                'improvement' => '+45%',
                'monthly_gain' => 5000000,
                'description' => 'Aniq auditoriyaga moslashtirilgan marketing',
            ],
            'roi_percent' => 19900,
            'payback_days' => 1,
            'module_route' => '/onboarding/dream-buyer',
            'difficulty' => 'oson',
        ],
        [
            'id' => 2,
            'action' => 'Rad qilib bo\'lmas taklif yaratish',
            'priority' => 2,
            'investment' => [
                'time' => '45 daqiqa',
                'time_value' => 37500,
                'money' => 0,
                'total' => 37500,
            ],
            'expected_return' => [
                'metric' => 'Sotuvlar',
                'improvement' => '+60%',
                'monthly_gain' => 7000000,
                'description' => 'Raqobatchilardan farqlanadigan kuchli taklif',
            ],
            'roi_percent' => 18567,
            'payback_days' => 1,
            'module_route' => '/onboarding/offer',
            'difficulty' => 'o\'rta',
        ],
        [
            'id' => 3,
            'action' => 'Instagram AI ulash',
            'priority' => 3,
            'investment' => [
                'time' => '20 daqiqa',
                'time_value' => 16700,
                'money' => 0,
                'total' => 16700,
            ],
            'expected_return' => [
                'metric' => 'Javob tezligi',
                'improvement' => '+80%',
                'monthly_gain' => 3000000,
                'description' => '24/7 avtomatik javob berish',
            ],
            'roi_percent' => 17864,
            'payback_days' => 1,
            'module_route' => '/business/instagram-ai',
            'difficulty' => 'oson',
        ],
    ],
];

// Update cause_effect_matrix
$diagnostic->cause_effect_matrix = [
    [
        'id' => 1,
        'problem' => 'Ideal mijoz aniq emas',
        'current_impact' => 'Marketing xarajatlari samarasiz, konversiya past',
        'monthly_loss' => 5000000,
        'solution' => [
            'action' => '9 ta savolga javob bering',
            'module' => 'Dream Buyer',
            'module_route' => '/onboarding/dream-buyer',
            'time' => '30 daqiqa',
            'difficulty' => 'oson',
        ],
        'expected_result' => [
            'metric' => 'Konversiya',
            'improvement' => '+45%',
            'monthly_gain' => 5000000,
        ],
        'roi_percent' => 19900,
        'payback_days' => 1,
        'priority' => 1,
    ],
    [
        'id' => 2,
        'problem' => 'Taklif zaif',
        'current_impact' => 'Mijozlar qaror qila olmaydi, sotuvlar past',
        'monthly_loss' => 7000000,
        'solution' => [
            'action' => 'Rad qilib bo\'lmas taklif yarating',
            'module' => 'Taklif',
            'module_route' => '/onboarding/offer',
            'time' => '45 daqiqa',
            'difficulty' => 'o\'rta',
        ],
        'expected_result' => [
            'metric' => 'Sotuvlar',
            'improvement' => '+60%',
            'monthly_gain' => 7000000,
        ],
        'roi_percent' => 18567,
        'payback_days' => 1,
        'priority' => 2,
    ],
    [
        'id' => 3,
        'problem' => 'Mijozlarga javob sekin',
        'current_impact' => 'Leadlar yo\'qolmoqda, raqobatchilar olib ketmoqda',
        'monthly_loss' => 3000000,
        'solution' => [
            'action' => 'Instagram AI yoqing',
            'module' => 'Instagram AI',
            'module_route' => '/business/instagram-ai',
            'time' => '20 daqiqa',
            'difficulty' => 'oson',
        ],
        'expected_result' => [
            'metric' => 'Javob tezligi',
            'improvement' => '+80%',
            'monthly_gain' => 3000000,
        ],
        'roi_percent' => 17864,
        'payback_days' => 1,
        'priority' => 3,
    ],
];

// Update action_plan
$diagnostic->action_plan = [
    'total_steps' => 3,
    'total_time_hours' => 5,
    'total_potential_savings' => 15000000,
    'steps' => [
        ['order' => 1, 'title' => 'Ideal mijozni aniqlang', 'module_route' => '/onboarding/dream-buyer', 'module_name' => 'Dream Buyer', 'time_minutes' => 30, 'impact_stars' => 5, 'why' => 'Barcha marketing harakatlarining asosi', 'similar_business_result' => '+45% konversiya', 'timeline' => 'today'],
        ['order' => 2, 'title' => 'Rad qilib bo\'lmas taklif yarating', 'module_route' => '/onboarding/offer', 'module_name' => 'Taklif', 'time_minutes' => 45, 'impact_stars' => 5, 'why' => 'Mijozlarni jalb qilish uchun', 'similar_business_result' => '+60% sotuvlar', 'timeline' => 'today'],
        ['order' => 3, 'title' => 'Instagram AI ni sozlang', 'module_route' => '/business/instagram-ai', 'module_name' => 'Instagram AI', 'time_minutes' => 20, 'impact_stars' => 4, 'why' => 'Avtomatik javob berish', 'similar_business_result' => '+80% javob tezligi', 'timeline' => 'this_week'],
    ],
];

$diagnostic->save();

echo "Diagnostic updated successfully!\n";
echo "- ROI calculations: updated\n";
echo "- Cause effect matrix: updated\n";
echo "- Action plan: updated\n";
