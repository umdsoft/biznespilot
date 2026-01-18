<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\Sale;
use Carbon\Carbon;

/**
 * Data Accuracy Engine
 *
 * Validates and ensures data accuracy for reliable predictions.
 * Implements multiple validation layers and anomaly detection.
 */
class DataAccuracyEngine extends AlgorithmEngine
{
    protected string $cachePrefix = 'data_accuracy_';

    protected int $cacheTTL = 3600; // 1 hour

    /**
     * Validation rules for each data type
     */
    protected array $validationRules = [
        'sales' => [
            'amount' => ['type' => 'numeric', 'min' => 0, 'max' => 1000000000],
            'created_at' => ['type' => 'date', 'max_future_days' => 1],
        ],
        'marketing' => [
            'monthly_budget' => ['type' => 'numeric', 'min' => 0, 'max' => 100000000],
        ],
        'leads' => [
            'status' => ['type' => 'enum', 'values' => ['new', 'contacted', 'qualified', 'converted', 'lost']],
        ],
    ];

    /**
     * Full data accuracy audit for a business
     */
    public function auditDataAccuracy(Business $business): array
    {
        $cacheKey = "business_{$business->id}_audit";

        return $this->cached($cacheKey, function () use ($business) {
            $audits = [
                'sales' => $this->auditSalesData($business),
                'marketing' => $this->auditMarketingData($business),
                'customers' => $this->auditCustomerData($business),
                'leads' => $this->auditLeadData($business),
            ];

            // Calculate overall accuracy
            $overallAccuracy = $this->calculateOverallAccuracy($audits);

            // Generate recommendations
            $recommendations = $this->generateAccuracyRecommendations($audits);

            // Data quality score
            $qualityScore = $this->calculateDataQualityScore($audits);

            return [
                'overall_accuracy' => $overallAccuracy,
                'quality_score' => $qualityScore,
                'audits' => $audits,
                'recommendations' => $recommendations,
                'audited_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Audit sales data
     */
    protected function auditSalesData(Business $business): array
    {
        $sales = Sale::where('business_id', $business->id)->get();

        if ($sales->isEmpty()) {
            return [
                'status' => 'no_data',
                'record_count' => 0,
                'accuracy' => 0,
                'issues' => ['Sotuvlar ma\'lumotlari mavjud emas'],
            ];
        }

        $issues = [];
        $validRecords = 0;
        $totalRecords = $sales->count();

        // Check for outliers
        $amounts = $sales->pluck('amount')->toArray();
        $outlierAnalysis = $this->detectOutliers($amounts);

        if (count($outlierAnalysis['outliers']) > 0) {
            $outlierCount = count($outlierAnalysis['outliers']);
            $issues[] = [
                'type' => 'outliers',
                'severity' => 'warning',
                'message' => "{$outlierCount} ta g'ayrioddiy qiymat topildi",
                'details' => $outlierAnalysis['outliers'],
            ];
        }

        // Check for data completeness
        $missingCustomer = $sales->whereNull('customer_id')->count();
        if ($missingCustomer > 0) {
            $percentage = round(($missingCustomer / $totalRecords) * 100, 1);
            $issues[] = [
                'type' => 'incomplete',
                'severity' => $percentage > 30 ? 'error' : 'warning',
                'message' => "{$missingCustomer} ta sotuvda ({$percentage}%) mijoz ma'lumoti yo'q",
            ];
        }

        // Check for negative values
        $negativeAmounts = $sales->where('amount', '<', 0)->count();
        if ($negativeAmounts > 0) {
            $issues[] = [
                'type' => 'invalid',
                'severity' => 'error',
                'message' => "{$negativeAmounts} ta sotuvda manfiy summa bor",
            ];
        }

        // Check for future dates
        $futureDates = $sales->filter(fn ($s) => Carbon::parse($s->created_at)->isFuture())->count();
        if ($futureDates > 0) {
            $issues[] = [
                'type' => 'invalid',
                'severity' => 'error',
                'message' => "{$futureDates} ta sotuvda kelajak sana bor",
            ];
        }

        // Check for duplicates (same amount, same day, same customer)
        $duplicates = $this->findSalesDuplicates($sales);
        if ($duplicates > 0) {
            $issues[] = [
                'type' => 'duplicates',
                'severity' => 'warning',
                'message' => "{$duplicates} ta potensial dublikat topildi",
            ];
        }

        // Calculate valid records
        $validRecords = $totalRecords - $negativeAmounts - $futureDates;
        $accuracy = ($validRecords / $totalRecords) * 100;

        // Statistical analysis
        $stats = $this->calculateSalesStats($sales);

        return [
            'status' => $accuracy >= 90 ? 'good' : ($accuracy >= 70 ? 'acceptable' : 'needs_attention'),
            'record_count' => $totalRecords,
            'valid_records' => $validRecords,
            'accuracy' => round($accuracy, 2),
            'issues' => $issues,
            'statistics' => $stats,
            'outlier_bounds' => $outlierAnalysis['bounds'],
        ];
    }

    /**
     * Audit marketing data
     */
    protected function auditMarketingData(Business $business): array
    {
        $channels = MarketingChannel::where('business_id', $business->id)->get();

        if ($channels->isEmpty()) {
            return [
                'status' => 'no_data',
                'record_count' => 0,
                'accuracy' => 0,
                'issues' => ['Marketing kanallari ma\'lumotlari mavjud emas'],
            ];
        }

        $issues = [];
        $totalRecords = $channels->count();
        $validRecords = $totalRecords;

        // Check for negative budgets
        $negativeBudgets = $channels->where('monthly_budget', '<', 0)->count();
        if ($negativeBudgets > 0) {
            $issues[] = [
                'type' => 'invalid',
                'severity' => 'error',
                'message' => "{$negativeBudgets} ta kanalda manfiy byudjet bor",
            ];
            $validRecords -= $negativeBudgets;
        }

        // Check for unrealistic budgets
        $unrealisticBudgets = $channels->where('monthly_budget', '>', 100000000)->count();
        if ($unrealisticBudgets > 0) {
            $issues[] = [
                'type' => 'suspicious',
                'severity' => 'warning',
                'message' => "{$unrealisticBudgets} ta kanalda g'ayrioddiy yuqori byudjet bor",
            ];
        }

        // Check active vs inactive ratio
        $activeCount = $channels->where('is_active', true)->count();
        $inactiveCount = $totalRecords - $activeCount;

        if ($inactiveCount > $activeCount * 2) {
            $issues[] = [
                'type' => 'attention',
                'severity' => 'info',
                'message' => "Ko'p kanallar faol emas ({$inactiveCount}/{$totalRecords})",
            ];
        }

        // Budget distribution analysis
        $budgets = $channels->where('monthly_budget', '>', 0)->pluck('monthly_budget')->toArray();
        if (! empty($budgets)) {
            $outlierAnalysis = $this->detectOutliers($budgets);
            if (count($outlierAnalysis['outliers']) > 0) {
                $issues[] = [
                    'type' => 'outliers',
                    'severity' => 'info',
                    'message' => 'Byudjet taqsimotida notekis qiymatlar bor',
                ];
            }
        }

        $accuracy = ($validRecords / $totalRecords) * 100;

        return [
            'status' => $accuracy >= 90 ? 'good' : ($accuracy >= 70 ? 'acceptable' : 'needs_attention'),
            'record_count' => $totalRecords,
            'valid_records' => $validRecords,
            'accuracy' => round($accuracy, 2),
            'issues' => $issues,
            'statistics' => [
                'total_budget' => $channels->sum('monthly_budget'),
                'avg_budget' => round($channels->avg('monthly_budget'), 2),
                'active_channels' => $activeCount,
                'inactive_channels' => $inactiveCount,
            ],
        ];
    }

    /**
     * Audit customer data
     */
    protected function auditCustomerData(Business $business): array
    {
        // Get unique customers from sales
        $customerIds = Sale::where('business_id', $business->id)
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        if ($customerIds->isEmpty()) {
            return [
                'status' => 'no_data',
                'record_count' => 0,
                'accuracy' => 0,
                'issues' => ['Mijozlar ma\'lumotlari mavjud emas'],
            ];
        }

        $issues = [];
        $totalRecords = $customerIds->count();

        // Check for customers with abnormal purchase patterns
        $customerStats = Sale::where('business_id', $business->id)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as purchase_count, SUM(amount) as total_spent')
            ->groupBy('customer_id')
            ->get();

        $purchaseCounts = $customerStats->pluck('purchase_count')->toArray();
        $outlierAnalysis = $this->detectOutliers($purchaseCounts);

        if (count($outlierAnalysis['outliers']) > 0) {
            $issues[] = [
                'type' => 'outliers',
                'severity' => 'info',
                'message' => count($outlierAnalysis['outliers'])." ta mijozda g'ayrioddiy xarid soni bor",
            ];
        }

        // Check for data recency
        $lastPurchaseDate = Sale::where('business_id', $business->id)->max('created_at');
        $daysSinceLastSale = $lastPurchaseDate ? Carbon::parse($lastPurchaseDate)->diffInDays(now()) : 365;

        if ($daysSinceLastSale > 30) {
            $issues[] = [
                'type' => 'stale',
                'severity' => 'warning',
                'message' => "Oxirgi sotuv {$daysSinceLastSale} kun oldin bo'lgan",
            ];
        }

        $accuracy = 100 - min(50, count($issues) * 10);

        return [
            'status' => $accuracy >= 90 ? 'good' : ($accuracy >= 70 ? 'acceptable' : 'needs_attention'),
            'record_count' => $totalRecords,
            'accuracy' => round($accuracy, 2),
            'issues' => $issues,
            'statistics' => [
                'unique_customers' => $totalRecords,
                'avg_purchase_count' => round($customerStats->avg('purchase_count'), 2),
                'avg_total_spent' => round($customerStats->avg('total_spent'), 2),
                'days_since_last_sale' => $daysSinceLastSale,
            ],
        ];
    }

    /**
     * Audit lead data
     */
    protected function auditLeadData(Business $business): array
    {
        $leads = Lead::where('business_id', $business->id)->get();

        if ($leads->isEmpty()) {
            return [
                'status' => 'no_data',
                'record_count' => 0,
                'accuracy' => 0,
                'issues' => ['Lead ma\'lumotlari mavjud emas'],
            ];
        }

        $issues = [];
        $totalRecords = $leads->count();
        $validRecords = $totalRecords;

        // Check for invalid statuses
        $validStatuses = ['new', 'contacted', 'qualified', 'converted', 'lost'];
        $invalidStatuses = $leads->whereNotIn('status', $validStatuses)->count();

        if ($invalidStatuses > 0) {
            $issues[] = [
                'type' => 'invalid',
                'severity' => 'error',
                'message' => "{$invalidStatuses} ta leadda noto'g'ri status bor",
            ];
            $validRecords -= $invalidStatuses;
        }

        // Check funnel distribution
        $statusDistribution = $leads->groupBy('status')->map->count();
        $newLeads = $statusDistribution->get('new', 0);
        $convertedLeads = $statusDistribution->get('converted', 0);

        if ($newLeads > $totalRecords * 0.8) {
            $issues[] = [
                'type' => 'attention',
                'severity' => 'warning',
                'message' => "Ko'p leadlar 'new' statusda qolgan (80%+)",
            ];
        }

        // Check for stale leads
        $staleLeads = $leads
            ->where('status', 'new')
            ->filter(fn ($l) => Carbon::parse($l->created_at)->diffInDays(now()) > 30)
            ->count();

        if ($staleLeads > 0) {
            $issues[] = [
                'type' => 'stale',
                'severity' => 'warning',
                'message' => "{$staleLeads} ta lead 30+ kundan beri 'new' statusda",
            ];
        }

        $accuracy = ($validRecords / $totalRecords) * 100;

        return [
            'status' => $accuracy >= 90 ? 'good' : ($accuracy >= 70 ? 'acceptable' : 'needs_attention'),
            'record_count' => $totalRecords,
            'valid_records' => $validRecords,
            'accuracy' => round($accuracy, 2),
            'issues' => $issues,
            'statistics' => [
                'status_distribution' => $statusDistribution->toArray(),
                'conversion_rate' => $totalRecords > 0 ? round(($convertedLeads / $totalRecords) * 100, 2) : 0,
                'stale_leads' => $staleLeads,
            ],
        ];
    }

    /**
     * Find potential duplicate sales
     */
    protected function findSalesDuplicates($sales): int
    {
        $grouped = $sales->groupBy(function ($sale) {
            return $sale->customer_id.'_'.Carbon::parse($sale->created_at)->format('Y-m-d').'_'.$sale->amount;
        });

        return $grouped->filter(fn ($group) => $group->count() > 1)->count();
    }

    /**
     * Calculate sales statistics
     */
    protected function calculateSalesStats($sales): array
    {
        $amounts = $sales->pluck('amount')->toArray();

        if (empty($amounts)) {
            return [];
        }

        return [
            'count' => count($amounts),
            'total' => array_sum($amounts),
            'mean' => round(array_sum($amounts) / count($amounts), 2),
            'median' => $this->percentile($amounts, 50),
            'std_dev' => round($this->standardDeviation($amounts), 2),
            'min' => min($amounts),
            'max' => max($amounts),
            'percentile_25' => $this->percentile($amounts, 25),
            'percentile_75' => $this->percentile($amounts, 75),
        ];
    }

    /**
     * Calculate overall accuracy
     */
    protected function calculateOverallAccuracy(array $audits): array
    {
        $totalAccuracy = 0;
        $totalWeight = 0;

        $weights = [
            'sales' => 0.35,
            'marketing' => 0.25,
            'customers' => 0.25,
            'leads' => 0.15,
        ];

        foreach ($audits as $key => $audit) {
            if ($audit['status'] !== 'no_data') {
                $weight = $weights[$key] ?? 0.1;
                $totalAccuracy += $audit['accuracy'] * $weight;
                $totalWeight += $weight;
            }
        }

        $overallScore = $totalWeight > 0 ? round($totalAccuracy / $totalWeight, 2) : 0;

        return [
            'score' => $overallScore,
            'level' => $overallScore >= 90 ? 'excellent' : ($overallScore >= 75 ? 'good' : ($overallScore >= 60 ? 'acceptable' : 'needs_improvement')),
            'label' => $overallScore >= 90 ? 'Ajoyib' : ($overallScore >= 75 ? 'Yaxshi' : ($overallScore >= 60 ? 'Qoniqarli' : 'Yaxshilash kerak')),
        ];
    }

    /**
     * Calculate data quality score
     */
    protected function calculateDataQualityScore(array $audits): array
    {
        $dimensions = [
            'completeness' => $this->calculateCompleteness($audits),
            'validity' => $this->calculateValidity($audits),
            'consistency' => $this->calculateConsistency($audits),
            'timeliness' => $this->calculateTimeliness($audits),
        ];

        $overallQuality = array_sum($dimensions) / count($dimensions);

        return [
            'overall' => round($overallQuality, 2),
            'dimensions' => $dimensions,
            'grade' => $this->getQualityGrade($overallQuality),
        ];
    }

    /**
     * Calculate data completeness
     */
    protected function calculateCompleteness(array $audits): float
    {
        $hasData = 0;
        $total = count($audits);

        foreach ($audits as $audit) {
            if ($audit['status'] !== 'no_data' && $audit['record_count'] > 0) {
                $hasData++;
            }
        }

        return ($hasData / $total) * 100;
    }

    /**
     * Calculate data validity
     */
    protected function calculateValidity(array $audits): float
    {
        $totalValid = 0;
        $totalRecords = 0;

        foreach ($audits as $audit) {
            if (isset($audit['valid_records']) && isset($audit['record_count'])) {
                $totalValid += $audit['valid_records'];
                $totalRecords += $audit['record_count'];
            }
        }

        return $totalRecords > 0 ? ($totalValid / $totalRecords) * 100 : 0;
    }

    /**
     * Calculate data consistency
     */
    protected function calculateConsistency(array $audits): float
    {
        $inconsistencies = 0;

        foreach ($audits as $audit) {
            if (isset($audit['issues'])) {
                foreach ($audit['issues'] as $issue) {
                    if (in_array($issue['type'], ['duplicates', 'inconsistent'])) {
                        $inconsistencies++;
                    }
                }
            }
        }

        // Start with 100 and subtract for inconsistencies
        return max(0, 100 - ($inconsistencies * 15));
    }

    /**
     * Calculate data timeliness
     */
    protected function calculateTimeliness(array $audits): float
    {
        $staleIssues = 0;

        foreach ($audits as $audit) {
            if (isset($audit['issues'])) {
                foreach ($audit['issues'] as $issue) {
                    if ($issue['type'] === 'stale') {
                        $staleIssues++;
                    }
                }
            }
        }

        return max(0, 100 - ($staleIssues * 20));
    }

    /**
     * Get quality grade
     */
    protected function getQualityGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'F';
    }

    /**
     * Generate accuracy recommendations
     */
    protected function generateAccuracyRecommendations(array $audits): array
    {
        $recommendations = [];

        foreach ($audits as $key => $audit) {
            if ($audit['status'] === 'no_data') {
                $recommendations[] = [
                    'priority' => 'high',
                    'module' => $key,
                    'recommendation' => $this->getModuleLabel($key)." ma'lumotlarini kiritish tavsiya etiladi",
                    'impact' => 'Tahlil aniqligini 25%+ ga oshiradi',
                ];

                continue;
            }

            if (isset($audit['issues'])) {
                foreach ($audit['issues'] as $issue) {
                    if ($issue['severity'] === 'error') {
                        $recommendations[] = [
                            'priority' => 'high',
                            'module' => $key,
                            'recommendation' => $issue['message'].' - tuzatish kerak',
                            'impact' => 'Ma\'lumotlar ishonchliligini oshiradi',
                        ];
                    } elseif ($issue['severity'] === 'warning' && count($recommendations) < 5) {
                        $recommendations[] = [
                            'priority' => 'medium',
                            'module' => $key,
                            'recommendation' => $issue['message']." - ko'rib chiqish tavsiya etiladi",
                            'impact' => 'Tahlil sifatini yaxshilaydi',
                        ];
                    }
                }
            }
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return array_slice($recommendations, 0, 5);
    }

    /**
     * Get module label
     */
    protected function getModuleLabel(string $key): string
    {
        $labels = [
            'sales' => 'Sotuvlar',
            'marketing' => 'Marketing',
            'customers' => 'Mijozlar',
            'leads' => 'Leadlar',
        ];

        return $labels[$key] ?? $key;
    }

    /**
     * Validate single data point
     */
    public function validateDataPoint(string $type, string $field, $value): array
    {
        $rules = $this->validationRules[$type][$field] ?? null;

        if (! $rules) {
            return ['valid' => true, 'message' => null];
        }

        // Type validation
        if ($rules['type'] === 'numeric' && ! is_numeric($value)) {
            return ['valid' => false, 'message' => 'Qiymat raqam bo\'lishi kerak'];
        }

        if ($rules['type'] === 'date') {
            try {
                $date = Carbon::parse($value);
                if (isset($rules['max_future_days']) && $date->isFuture()) {
                    $daysDiff = $date->diffInDays(now());
                    if ($daysDiff > $rules['max_future_days']) {
                        return ['valid' => false, 'message' => 'Sana kelajakda bo\'lishi mumkin emas'];
                    }
                }
            } catch (\Exception $e) {
                return ['valid' => false, 'message' => 'Noto\'g\'ri sana formati'];
            }
        }

        if ($rules['type'] === 'enum' && isset($rules['values'])) {
            if (! in_array($value, $rules['values'])) {
                return ['valid' => false, 'message' => 'Noto\'g\'ri qiymat'];
            }
        }

        // Range validation
        if (isset($rules['min']) && $value < $rules['min']) {
            return ['valid' => false, 'message' => "Qiymat {$rules['min']} dan kam bo'lishi mumkin emas"];
        }

        if (isset($rules['max']) && $value > $rules['max']) {
            return ['valid' => false, 'message' => "Qiymat {$rules['max']} dan ko'p bo'lishi mumkin emas"];
        }

        return ['valid' => true, 'message' => null];
    }
}
