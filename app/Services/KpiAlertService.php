<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KpiAlertService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check all KPI alerts for a business.
     */
    public function checkAlerts(Business $business): array
    {
        $triggeredAlerts = [];

        // Get active alert rules for this business
        $rules = AlertRule::active()
            ->forBusiness($business->id)
            ->where('alert_type', 'kpi')
            ->get();

        foreach ($rules as $rule) {
            $alert = $this->checkRule($business, $rule);
            if ($alert) {
                $triggeredAlerts[] = $alert;
            }
        }

        return $triggeredAlerts;
    }

    /**
     * Check a single alert rule.
     */
    protected function checkRule(Business $business, AlertRule $rule): ?Alert
    {
        $currentValue = $this->getCurrentValue($business, $rule->metric_code);
        $previousValue = $this->getPreviousValue($business, $rule->metric_code, $rule->comparison_period);

        // Check if condition is met
        if (!$rule->checkCondition($currentValue, $previousValue)) {
            return null;
        }

        // Check if similar alert already exists today
        $existingAlert = Alert::where('business_id', $business->id)
            ->where('type', 'kpi_alert')
            ->where('data->rule_id', $rule->id)
            ->whereDate('created_at', today())
            ->first();

        if ($existingAlert) {
            return null; // Don't create duplicate alert
        }

        // Create alert
        $alert = $this->createAlert($business, $rule, $currentValue, $previousValue);

        // Send notifications
        $this->sendNotifications($business, $alert, $rule);

        return $alert;
    }

    /**
     * Get current KPI value.
     */
    protected function getCurrentValue(Business $business, string $metricCode): float
    {
        $kpi = KpiDailyActual::allBusinesses()
            ->where('business_id', $business->id)
            ->where('kpi_code', $metricCode)
            ->whereDate('date', today())
            ->first();

        return $kpi ? (float) $kpi->actual_value : 0;
    }

    /**
     * Get previous period KPI value.
     */
    protected function getPreviousValue(Business $business, string $metricCode, ?string $period): float
    {
        $date = match ($period) {
            'yesterday' => Carbon::yesterday(),
            'last_week' => Carbon::now()->subWeek(),
            'last_month' => Carbon::now()->subMonth(),
            default => Carbon::yesterday(),
        };

        $kpi = KpiDailyActual::allBusinesses()
            ->where('business_id', $business->id)
            ->where('kpi_code', $metricCode)
            ->whereDate('date', $date)
            ->first();

        return $kpi ? (float) $kpi->actual_value : 0;
    }

    /**
     * Create alert record.
     */
    protected function createAlert(
        Business $business,
        AlertRule $rule,
        float $currentValue,
        float $previousValue
    ): Alert {
        $messageData = [
            'metric_name' => $this->getMetricName($rule->metric_code),
            'current_value' => number_format($currentValue, 0),
            'previous_value' => number_format($previousValue, 0),
            'threshold' => number_format($rule->threshold_value ?? 0, 0),
            'threshold_percent' => $rule->threshold_percent ?? 0,
            'change_percent' => $previousValue > 0
                ? round((($currentValue - $previousValue) / $previousValue) * 100, 1)
                : 0,
        ];

        $message = $rule->formatMessage($messageData);

        return Alert::create([
            'business_id' => $business->id,
            'type' => 'kpi_alert',
            'category' => 'kpi',
            'title' => $rule->getName(),
            'message' => $message,
            'severity' => $rule->severity ?? 'medium',
            'status' => 'new',
            'data' => [
                'rule_id' => $rule->id,
                'metric_code' => $rule->metric_code,
                'current_value' => $currentValue,
                'previous_value' => $previousValue,
                'threshold' => $rule->threshold_value,
                'condition' => $rule->condition,
            ],
            'action_url' => '/sales/kpi',
        ]);
    }

    /**
     * Send notifications for alert.
     */
    protected function sendNotifications(Business $business, Alert $alert, AlertRule $rule): void
    {
        try {
            // Send in-app notification
            $this->notificationService->sendAlert($alert);

            // Log alert
            Log::info('KPI alert triggered', [
                'business_id' => $business->id,
                'rule_id' => $rule->id,
                'alert_id' => $alert->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send KPI alert notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get human-readable metric name.
     */
    protected function getMetricName(string $code): string
    {
        $names = [
            'leads_count' => 'Lidlar soni',
            'sales_count' => 'Sotuvlar soni',
            'revenue' => 'Daromad',
            'conversion_rate' => 'Konversiya foizi',
            'average_check' => 'O\'rtacha chek',
            'calls_count' => 'Qo\'ng\'iroqlar soni',
            'hot_leads' => 'Issiq lidlar',
            'new_customers' => 'Yangi mijozlar',
            'repeat_sales' => 'Takroriy sotuvlar',
            'profit' => 'Foyda',
            'expenses' => 'Xarajatlar',
        ];

        return $names[$code] ?? $code;
    }

    /**
     * Create default alert rules for a new business.
     */
    public function createDefaultRules(Business $business): array
    {
        $defaultRules = [
            [
                'rule_code' => 'leads_drop',
                'rule_name_uz' => 'Lidlar soni kamaydi',
                'rule_name_en' => 'Leads count dropped',
                'description_uz' => 'Lidlar soni oldingi kunga nisbatan 30% dan ko\'p kamaydi',
                'alert_type' => 'kpi',
                'metric_code' => 'leads_count',
                'condition' => 'change_down',
                'threshold_percent' => 30,
                'comparison_period' => 'yesterday',
                'severity' => 'high',
                'message_template_uz' => '{metric_name} {change_percent}% ga kamaydi. Hozirgi: {current_value}, Oldingi: {previous_value}',
                'message_template_en' => '{metric_name} dropped by {change_percent}%. Current: {current_value}, Previous: {previous_value}',
                'action_suggestion_uz' => 'Marketing faoliyatini tekshiring va lidlar manbalarini tahlil qiling.',
                'is_active' => true,
            ],
            [
                'rule_code' => 'sales_target_reached',
                'rule_name_uz' => 'Sotuvlar maqsadiga yetildi',
                'rule_name_en' => 'Sales target reached',
                'description_uz' => 'Kunlik sotuv maqsadi bajarildi',
                'alert_type' => 'kpi',
                'metric_code' => 'sales_count',
                'condition' => 'greater_than',
                'threshold_value' => 10,
                'severity' => 'info',
                'message_template_uz' => 'Tabriklaymiz! Bugun {current_value} ta sotuv amalga oshirildi.',
                'message_template_en' => 'Congratulations! {current_value} sales completed today.',
                'is_active' => true,
            ],
            [
                'rule_code' => 'conversion_drop',
                'rule_name_uz' => 'Konversiya pasaydi',
                'rule_name_en' => 'Conversion rate dropped',
                'description_uz' => 'Konversiya foizi pasaydi',
                'alert_type' => 'kpi',
                'metric_code' => 'conversion_rate',
                'condition' => 'less_than',
                'threshold_value' => 5,
                'severity' => 'medium',
                'message_template_uz' => 'Konversiya foizi {current_value}% ga tushdi (minimal: {threshold}%).',
                'message_template_en' => 'Conversion rate dropped to {current_value}% (minimum: {threshold}%).',
                'action_suggestion_uz' => 'Sotuv jarayonini va takliflarni qayta ko\'rib chiqing.',
                'is_active' => true,
            ],
            [
                'rule_code' => 'no_calls',
                'rule_name_uz' => 'Qo\'ng\'iroqlar yo\'q',
                'rule_name_en' => 'No calls made',
                'description_uz' => 'Bugun qo\'ng\'iroq qilinmadi',
                'alert_type' => 'kpi',
                'metric_code' => 'calls_count',
                'condition' => 'equals',
                'threshold_value' => 0,
                'severity' => 'high',
                'message_template_uz' => 'Bugun hali birorta ham qo\'ng\'iroq qilinmadi!',
                'message_template_en' => 'No calls have been made today!',
                'action_suggestion_uz' => 'Operatorlarning faolligini tekshiring.',
                'is_active' => true,
            ],
            [
                'rule_code' => 'hot_leads_spike',
                'rule_name_uz' => 'Ko\'p issiq lidlar',
                'rule_name_en' => 'Hot leads spike',
                'description_uz' => 'Issiq lidlar soni oshdi',
                'alert_type' => 'kpi',
                'metric_code' => 'hot_leads',
                'condition' => 'change_up',
                'threshold_percent' => 50,
                'comparison_period' => 'yesterday',
                'severity' => 'info',
                'message_template_uz' => 'Issiq lidlar soni {change_percent}% ga oshdi. Hozirgi: {current_value}',
                'message_template_en' => 'Hot leads increased by {change_percent}%. Current: {current_value}',
                'is_active' => true,
            ],
        ];

        $createdRules = [];
        foreach ($defaultRules as $ruleData) {
            $createdRules[] = AlertRule::create(array_merge($ruleData, [
                'business_id' => $business->id,
            ]));
        }

        return $createdRules;
    }

    /**
     * Get all active alerts for a business.
     */
    public function getActiveAlerts(Business $business): Collection
    {
        return Alert::where('business_id', $business->id)
            ->where('type', 'kpi_alert')
            ->unresolved()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get alert statistics for a business.
     */
    public function getAlertStats(Business $business, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $alerts = Alert::where('business_id', $business->id)
            ->where('type', 'kpi_alert')
            ->where('created_at', '>=', $since)
            ->get();

        return [
            'total' => $alerts->count(),
            'by_severity' => $alerts->groupBy('severity')->map->count(),
            'by_status' => $alerts->groupBy('status')->map->count(),
            'by_metric' => $alerts->groupBy('data.metric_code')->map->count(),
            'average_per_day' => round($alerts->count() / $days, 1),
        ];
    }

    /**
     * Dismiss an alert.
     */
    public function dismissAlert(Alert $alert): void
    {
        $alert->dismiss();
    }

    /**
     * Check KPI thresholds and return warnings.
     */
    public function getKpiWarnings(Business $business): array
    {
        $warnings = [];

        // Get today's KPI data
        $todayKpis = KpiDailyActual::allBusinesses()
            ->where('business_id', $business->id)
            ->whereDate('date', today())
            ->get()
            ->keyBy('kpi_code');

        // Get yesterday's KPI data
        $yesterdayKpis = KpiDailyActual::allBusinesses()
            ->where('business_id', $business->id)
            ->whereDate('date', Carbon::yesterday())
            ->get()
            ->keyBy('kpi_code');

        // Check each metric
        foreach ($todayKpis as $code => $kpi) {
            $yesterdayKpi = $yesterdayKpis->get($code);
            if (!$yesterdayKpi) {
                continue;
            }

            $current = (float) $kpi->actual_value;
            $previous = (float) $yesterdayKpi->actual_value;

            if ($previous > 0) {
                $changePercent = round((($current - $previous) / $previous) * 100, 1);

                // Significant decrease warning
                if ($changePercent <= -20) {
                    $warnings[] = [
                        'metric_code' => $code,
                        'metric_name' => $this->getMetricName($code),
                        'current_value' => $current,
                        'previous_value' => $previous,
                        'change_percent' => $changePercent,
                        'severity' => $changePercent <= -50 ? 'critical' : 'warning',
                        'message' => "{$this->getMetricName($code)} {$changePercent}% ga kamaydi",
                    ];
                }

                // Significant increase notification
                if ($changePercent >= 50) {
                    $warnings[] = [
                        'metric_code' => $code,
                        'metric_name' => $this->getMetricName($code),
                        'current_value' => $current,
                        'previous_value' => $previous,
                        'change_percent' => $changePercent,
                        'severity' => 'success',
                        'message' => "{$this->getMetricName($code)} {$changePercent}% ga oshdi!",
                    ];
                }
            }
        }

        return $warnings;
    }
}
