<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\HRAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Alerts API Controller
 *
 * HR ogohlantirishlari uchun API.
 * Real-time bildirishnomalarni qo'llab-quvvatlaydi.
 */
class AlertsController extends Controller
{
    /**
     * Barcha alertlar ro'yxati
     */
    public function index(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $status = $request->input('status'); // new, seen, acknowledged, resolved
        $priority = $request->input('priority'); // critical, high, medium, low
        $type = $request->input('type');

        $query = HRAlert::where('business_id', $businessId)
            ->with('relatedUser:id,name')
            ->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc');

        // User ga tegishli alertlar (agar user_id ko'rsatilgan bo'lsa)
        if ($request->input('my_alerts')) {
            $query->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $alerts = $query->paginate($perPage);

        $alerts->getCollection()->transform(function ($alert) {
            return $this->formatAlert($alert);
        });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Ko'rilmagan alertlar soni
     */
    public function unreadCount(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $count = HRAlert::where('business_id', $businessId)
            ->where('status', HRAlert::STATUS_NEW)
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            })
            ->count();

        $criticalCount = HRAlert::where('business_id', $businessId)
            ->whereIn('status', [HRAlert::STATUS_NEW, HRAlert::STATUS_SEEN])
            ->where('priority', 'critical')
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
                'critical_count' => $criticalCount,
            ],
        ]);
    }

    /**
     * Bitta alert tafsilotlari
     */
    public function show(Request $request, string $businessId, string $alertId): JsonResponse
    {
        $alert = HRAlert::where('business_id', $businessId)
            ->where('id', $alertId)
            ->with(['relatedUser:id,name,email', 'resolvedBy:id,name'])
            ->first();

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert topilmadi',
            ], 404);
        }

        // Alertni "seen" qilish
        if ($alert->status === HRAlert::STATUS_NEW) {
            $alert->markAsSeen();
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatAlertDetailed($alert),
        ]);
    }

    /**
     * Alertni "acknowledged" qilish
     */
    public function acknowledge(Request $request, string $businessId, string $alertId): JsonResponse
    {
        $alert = HRAlert::where('business_id', $businessId)
            ->where('id', $alertId)
            ->first();

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert topilmadi',
            ], 404);
        }

        $alert->markAsAcknowledged();

        return response()->json([
            'success' => true,
            'message' => 'Alert tasdiqlandi',
        ]);
    }

    /**
     * Alertni "resolved" qilish
     */
    public function resolve(Request $request, string $businessId, string $alertId): JsonResponse
    {
        $alert = HRAlert::where('business_id', $businessId)
            ->where('id', $alertId)
            ->first();

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert topilmadi',
            ], 404);
        }

        $alert->markAsResolved(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Alert yechildi',
        ]);
    }

    /**
     * Bir nechta alertni "seen" qilish
     */
    public function markAllAsSeen(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $updated = HRAlert::where('business_id', $businessId)
            ->where('status', HRAlert::STATUS_NEW)
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            })
            ->update([
                'status' => HRAlert::STATUS_SEEN,
                'seen_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} ta alert ko'rildi",
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    /**
     * Alert statistikasi
     */
    public function statistics(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        // Status bo'yicha taqsimot
        $statusDistribution = HRAlert::where('business_id', $businessId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Priority bo'yicha taqsimot
        $priorityDistribution = HRAlert::where('business_id', $businessId)
            ->whereIn('status', [HRAlert::STATUS_NEW, HRAlert::STATUS_SEEN])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // Type bo'yicha taqsimot
        $typeDistribution = HRAlert::where('business_id', $businessId)
            ->whereIn('status', [HRAlert::STATUS_NEW, HRAlert::STATUS_SEEN])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->pluck('count', 'type')
            ->toArray();

        // Bugungi alertlar
        $todayAlerts = HRAlert::where('business_id', $businessId)
            ->whereDate('created_at', today())
            ->count();

        // Bu hafta yaratilgan alertlar
        $weeklyAlerts = HRAlert::where('business_id', $businessId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'status_distribution' => [
                    'new' => $statusDistribution['new'] ?? 0,
                    'seen' => $statusDistribution['seen'] ?? 0,
                    'acknowledged' => $statusDistribution['acknowledged'] ?? 0,
                    'resolved' => $statusDistribution['resolved'] ?? 0,
                ],
                'priority_distribution' => [
                    // 'urgent' va 'critical' ni birlashtirish
                    'critical' => ($priorityDistribution['critical'] ?? 0) + ($priorityDistribution['urgent'] ?? 0),
                    'high' => $priorityDistribution['high'] ?? 0,
                    'medium' => $priorityDistribution['medium'] ?? 0,
                    'low' => $priorityDistribution['low'] ?? 0,
                ],
                'top_alert_types' => $typeDistribution,
                'today_alerts' => $todayAlerts,
                'weekly_trend' => $weeklyAlerts,
            ],
        ]);
    }

    /**
     * Alert formatlash
     */
    protected function formatAlert(HRAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'type' => $alert->type,
            'type_label' => $this->getAlertTypeLabel($alert->type),
            'title' => $alert->title,
            'message' => $alert->message,
            'priority' => $alert->priority,
            'priority_label' => $this->getPriorityLabel($alert->priority),
            'priority_color' => $this->getPriorityColor($alert->priority),
            'status' => $alert->status,
            'status_label' => $this->getStatusLabel($alert->status),
            'is_celebration' => $alert->is_celebration ?? false,
            'related_user' => $alert->relatedUser ? [
                'id' => $alert->relatedUser->id,
                'name' => $alert->relatedUser->name,
            ] : null,
            'created_at' => $alert->created_at->format('d.m.Y H:i'),
            'created_ago' => $alert->created_at->diffForHumans(),
        ];
    }

    /**
     * Alert batafsil formatlash
     */
    protected function formatAlertDetailed(HRAlert $alert): array
    {
        $formatted = $this->formatAlert($alert);

        $formatted['data'] = $alert->data;
        $formatted['recommended_actions'] = $alert->recommended_actions;
        $formatted['seen_at'] = $alert->seen_at?->format('d.m.Y H:i');
        $formatted['acknowledged_at'] = $alert->acknowledged_at?->format('d.m.Y H:i');
        $formatted['resolved_at'] = $alert->resolved_at?->format('d.m.Y H:i');
        $formatted['resolved_by'] = $alert->resolvedBy ? [
            'id' => $alert->resolvedBy->id,
            'name' => $alert->resolvedBy->name,
        ] : null;

        return $formatted;
    }

    /**
     * Alert type label
     */
    protected function getAlertTypeLabel(string $type): string
    {
        return match ($type) {
            'engagement_low' => 'Past engagement',
            'flight_risk_high' => 'Yuqori ketish xavfi',
            'flight_risk_critical' => 'Kritik ketish xavfi',
            'onboarding_task_today' => 'Bugungi vazifa',
            'onboarding_task_overdue' => 'Kechikkan vazifa',
            'onboarding_milestone_completed' => 'Milestone yakunlandi',
            'onboarding_milestone_pending' => 'Milestone muddati',
            'work_anniversary' => 'Ish yilligi',
            'work_anniversary_upcoming' => 'Yaqin ish yilligi',
            'turnover_high' => 'Yuqori turnover',
            'turnover_regrettable' => 'Afsuslanarli ketish',
            'turnover_trend_negative' => 'Salbiy trend',
            'turnover_early' => 'Erta ketish',
            'turnover_report_ready' => 'Hisobot tayyor',
            default => $type,
        };
    }

    /**
     * Priority label
     */
    protected function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'urgent', 'critical' => 'Juda muhim',
            'high' => 'Yuqori',
            'medium' => "O'rtacha",
            'low' => 'Past',
            default => $priority,
        };
    }

    /**
     * Priority color
     */
    protected function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'urgent', 'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    /**
     * Status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'Yangi',
            'seen' => "Ko'rilgan",
            'acknowledged' => 'Tasdiqlangan',
            'resolved' => 'Yechilgan',
            default => $status,
        };
    }
}
