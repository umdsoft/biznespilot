<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Business;
use App\Models\KpiDailySnapshot;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AlertService
{
    public function checkAlerts(Business $business): Collection
    {
        $triggeredAlerts = collect();
        $rules = AlertRule::active()->get();

        $todaySnapshot = $this->getTodaySnapshot($business);
        $yesterdaySnapshot = $this->getYesterdaySnapshot($business);

        if (! $todaySnapshot) {
            return $triggeredAlerts;
        }

        foreach ($rules as $rule) {
            if ($this->shouldTrigger($rule, $todaySnapshot, $yesterdaySnapshot)) {
                $alert = $this->createAlert($business, $rule, $todaySnapshot, $yesterdaySnapshot);
                if ($alert) {
                    $triggeredAlerts->push($alert);
                }
            }
        }

        return $triggeredAlerts;
    }

    protected function shouldTrigger(AlertRule $rule, KpiDailySnapshot $today, ?KpiDailySnapshot $yesterday): bool
    {
        $metric = $rule->metric;
        $currentValue = $today->{$metric} ?? 0;
        $previousValue = $yesterday->{$metric} ?? null;

        // Check if already alerted recently for this rule
        $recentAlert = Alert::where('business_id', $today->business_id)
            ->where('alert_rule_id', $rule->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotIn('status', ['resolved', 'dismissed'])
            ->exists();

        if ($recentAlert) {
            return false;
        }

        return $rule->checkCondition($currentValue, $previousValue);
    }

    public function createAlert(
        Business $business,
        AlertRule $rule,
        KpiDailySnapshot $today,
        ?KpiDailySnapshot $yesterday
    ): ?Alert {
        $metric = $rule->metric;
        $currentValue = $today->{$metric} ?? 0;
        $previousValue = $yesterday->{$metric} ?? null;

        try {
            $alert = Alert::create([
                'business_id' => $business->id,
                'alert_rule_id' => $rule->id,
                'type' => $rule->type,
                'severity' => $rule->severity,
                'title' => $rule->name,
                'message' => $rule->formatMessage($currentValue, $previousValue),
                'metric' => $metric,
                'current_value' => $currentValue,
                'threshold_value' => $rule->threshold_value,
                'previous_value' => $previousValue,
                'triggered_at' => now(),
                'status' => 'active',
            ]);

            // Send notification
            $this->sendAlertNotification($alert);

            return $alert;
        } catch (\Exception $e) {
            Log::error('Failed to create alert', [
                'business_id' => $business->id,
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function triggerManualAlert(
        Business $business,
        string $type,
        string $severity,
        string $title,
        string $message,
        ?string $metric = null,
        ?float $currentValue = null
    ): Alert {
        $alert = Alert::create([
            'business_id' => $business->id,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'metric' => $metric,
            'current_value' => $currentValue,
            'triggered_at' => now(),
            'status' => 'active',
        ]);

        $this->sendAlertNotification($alert);

        return $alert;
    }

    public function acknowledgeAlert(Alert $alert, int $userId): void
    {
        $alert->acknowledge($userId);
    }

    public function resolveAlert(Alert $alert, int $userId, ?string $resolution = null): void
    {
        $alert->resolve($userId, $resolution);
    }

    public function snoozeAlert(Alert $alert, int $hours = 24): void
    {
        $alert->snooze($hours);
    }

    public function dismissAlert(Alert $alert): void
    {
        $alert->dismiss();
    }

    public function getActiveAlerts(Business $business, ?string $severity = null): Collection
    {
        $query = Alert::where('business_id', $business->id)
            ->active()
            ->unresolved()
            ->notSnoozed()
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low', 'info')")
            ->orderBy('triggered_at', 'desc');

        if ($severity) {
            $query->where('severity', $severity);
        }

        return $query->get();
    }

    public function getAlertStats(Business $business, int $days = 30): array
    {
        $alerts = Alert::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total' => $alerts->count(),
            'active' => $alerts->where('status', 'active')->count(),
            'acknowledged' => $alerts->where('status', 'acknowledged')->count(),
            'resolved' => $alerts->where('status', 'resolved')->count(),
            'dismissed' => $alerts->where('status', 'dismissed')->count(),
            'by_severity' => [
                'critical' => $alerts->where('severity', 'critical')->count(),
                'high' => $alerts->where('severity', 'high')->count(),
                'medium' => $alerts->where('severity', 'medium')->count(),
                'low' => $alerts->where('severity', 'low')->count(),
            ],
            'avg_resolution_time' => $this->calculateAvgResolutionTime($alerts),
        ];
    }

    protected function calculateAvgResolutionTime(Collection $alerts): ?float
    {
        $resolvedAlerts = $alerts->filter(fn ($a) => $a->resolved_at !== null);

        if ($resolvedAlerts->isEmpty()) {
            return null;
        }

        $totalMinutes = $resolvedAlerts->sum(function ($alert) {
            return $alert->triggered_at->diffInMinutes($alert->resolved_at);
        });

        return round($totalMinutes / $resolvedAlerts->count(), 1);
    }

    protected function sendAlertNotification(Alert $alert): void
    {
        // Create in-app notification
        Notification::create([
            'business_id' => $alert->business_id,
            'type' => 'alert',
            'channel' => 'in_app',
            'title' => $alert->title,
            'message' => $alert->message,
            'action_url' => "/alerts/{$alert->id}",
            'action_text' => 'Ko\'rish',
            'related_type' => Alert::class,
            'related_id' => $alert->id,
            'priority' => $alert->severity,
        ]);

        // TODO: Send email/telegram notifications based on business preferences
    }

    protected function getTodaySnapshot(Business $business): ?KpiDailySnapshot
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', Carbon::today())
            ->first();
    }

    protected function getYesterdaySnapshot(Business $business): ?KpiDailySnapshot
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', Carbon::yesterday())
            ->first();
    }
}
