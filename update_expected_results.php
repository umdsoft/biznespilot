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

// Update expected_results with correct format
$diagnostic->expected_results = [
    'now' => ['score' => $diagnostic->overall_score, 'leads_weekly' => 10, 'conversion' => 2, 'revenue_change' => 0],
    '30_days' => [
        'health_score_improvement' => 15,
        'conversion_improvement' => 3,
        'description' => 'Ideal mijoz aniqlanadi, taklif optimallashtiriladi, dastlabki natijalar ko\'rinadi',
    ],
    '60_days' => [
        'health_score_improvement' => 25,
        'revenue_improvement' => 40,
        'description' => 'Marketing kanallari yaxshilanadi, sotuvlar barqarorlashadi, mijozlar soni oshadi',
    ],
    '90_days' => [
        'health_score_improvement' => 35,
        'total_revenue_increase' => 8000000,
        'description' => 'To\'liq tizim ishlaydi, avtomatlashtirish tugallanadi, barqaror o\'sish boshlandi',
    ],
];

$diagnostic->save();

echo "Expected results updated successfully!\n";
echo "New expected_results:\n";
print_r($diagnostic->expected_results);
