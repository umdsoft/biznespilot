<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use App\Models\Task;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * LayoutStatsController — bitta endpoint BusinessLayout sidebar badge'larini
 * oladi. 5-6 parallel API call'ni 1 ta'ga qisqartiradi, bu framework boot
 * overhead'ini (dev'da ~500ms per request) dramatik kamaytiradi.
 *
 * Response 30 sekund cachelangan — badge raqamlari real-time bo'lishi shart
 * emas, UX uchun kichik kechikish qabul qilinadi.
 */
class LayoutStatsController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness();

        if (!$business || !$user) {
            return response()->json($this->emptyPayload());
        }

        $cacheKey = "layout_stats:u={$user->id}:b={$business->id}";

        $data = Cache::remember($cacheKey, 30, function () use ($user, $business) {
            return [
                'inbox_unread' => $this->safeCount(fn () => $this->inboxUnread($business->id)),
                'new_leads' => $this->safeCount(fn () => Lead::where('business_id', $business->id)->where('status', 'new')->count()),
                'tasks' => $this->taskStats($business->id),
                'todos' => $this->todoStats($user->id, $business->id),
                'pending_orders' => $this->safeCount(fn () => $this->pendingOrders($business->id)),
                'notifications_unread' => $this->safeCount(fn () => Notification::where('user_id', $user->id)->whereNull('read_at')->count()),
            ];
        });

        return response()->json($data);
    }

    private function inboxUnread(string $businessId): int
    {
        // UnifiedInbox — hozircha lightweight COUNT: chatbot conversations + telegram
        // Agar maxsus unified model yo'q bo'lsa, 0 qaytaramiz (graceful)
        if (!class_exists(\App\Models\ChatbotConversation::class)) {
            return 0;
        }
        return \App\Models\ChatbotConversation::where('business_id', $businessId)
            ->where('is_resolved', false)
            ->count();
    }

    private function taskStats(string $businessId): array
    {
        try {
            $today = now()->toDateString();
            $result = Task::where('business_id', $businessId)
                ->where('status', 'pending')
                ->selectRaw(
                    "COUNT(*) as total,
                     SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) as overdue",
                    [$today]
                )
                ->first();
            return [
                'total' => (int) ($result->total ?? 0),
                'overdue' => (int) ($result->overdue ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'overdue' => 0];
        }
    }

    private function todoStats(string $userId, string $businessId): array
    {
        try {
            if (!class_exists(Todo::class)) {
                return ['total_today' => 0, 'overdue' => 0];
            }
            $today = now()->toDateString();
            $result = Todo::where('business_id', $businessId)
                ->where('status', '!=', 'completed')
                ->selectRaw(
                    "SUM(CASE WHEN DATE(due_date) = ? THEN 1 ELSE 0 END) as total_today,
                     SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) as overdue",
                    [$today, $today]
                )
                ->first();
            return [
                'total_today' => (int) ($result->total_today ?? 0),
                'overdue' => (int) ($result->overdue ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['total_today' => 0, 'overdue' => 0];
        }
    }

    private function pendingOrders(string $businessId): int
    {
        $storeIds = TelegramStore::where('business_id', $businessId)->pluck('id');
        if ($storeIds->isEmpty()) {
            return 0;
        }
        return StoreOrder::whereIn('store_id', $storeIds)
            ->whereIn('status', [StoreOrder::STATUS_PENDING, StoreOrder::STATUS_CONFIRMED])
            ->count();
    }

    private function safeCount(callable $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function emptyPayload(): array
    {
        return [
            'inbox_unread' => 0,
            'new_leads' => 0,
            'tasks' => ['total' => 0, 'overdue' => 0],
            'todos' => ['total_today' => 0, 'overdue' => 0],
            'pending_orders' => 0,
            'notifications_unread' => 0,
        ];
    }
}
