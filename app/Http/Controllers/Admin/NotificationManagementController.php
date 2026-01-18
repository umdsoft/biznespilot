<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationManagementController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display notification list
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $type = $request->input('type');
        $scope = $request->input('scope'); // all, broadcast, personal
        $search = $request->input('search');

        $query = Notification::with(['user:id,name,email', 'business:id,name'])
            ->orderBy('created_at', 'desc');

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($scope === 'broadcast') {
            $query->whereNull('user_id');
        } elseif ($scope === 'personal') {
            $query->whereNotNull('user_id');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->paginate($perPage);

        // Get statistics
        $stats = $this->getStats();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'filters' => [
                'type' => $type,
                'scope' => $scope,
                'search' => $search,
            ],
            'types' => $this->getNotificationTypes(),
        ]);
    }

    /**
     * Get notification statistics
     */
    protected function getStats(): array
    {
        return [
            'total' => Notification::count(),
            'unread' => Notification::unread()->count(),
            'read' => Notification::read()->count(),
            'broadcast' => Notification::whereNull('user_id')->count(),
            'personal' => Notification::whereNotNull('user_id')->count(),
            'today' => Notification::whereDate('created_at', today())->count(),
            'this_week' => Notification::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'by_type' => Notification::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];
    }

    /**
     * Get notification types
     */
    protected function getNotificationTypes(): array
    {
        return [
            'system' => ['label' => 'Tizim xabari', 'icon' => 'cog', 'color' => 'gray'],
            'update' => ['label' => 'Yangilanish', 'icon' => 'refresh', 'color' => 'green'],
            'announcement' => ['label' => 'E\'lon', 'icon' => 'speakerphone', 'color' => 'indigo'],
            'alert' => ['label' => 'Ogohlantirish', 'icon' => 'exclamation', 'color' => 'red'],
            'celebration' => ['label' => 'Tabrik', 'icon' => 'sparkles', 'color' => 'yellow'],
            'insight' => ['label' => 'Maslahat', 'icon' => 'lightbulb', 'color' => 'blue'],
        ];
    }

    /**
     * Show create notification form
     */
    public function create()
    {
        $users = User::select('id', 'name', 'email')
            ->whereHas('businesses')
            ->orderBy('name')
            ->get();

        $businesses = Business::select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Notifications/Create', [
            'users' => $users,
            'businesses' => $businesses,
            'types' => $this->getNotificationTypes(),
        ]);
    }

    /**
     * Send notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:system,update,announcement,alert,celebration,insight',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'target' => 'required|string|in:all,businesses,users',
            'business_ids' => 'required_if:target,businesses|array',
            'business_ids.*' => 'uuid|exists:businesses,id',
            'user_ids' => 'required_if:target,users|array',
            'user_ids.*' => 'uuid|exists:users,id',
        ]);

        // Get icon for notification type
        $icons = [
            'system' => 'cog',
            'update' => 'refresh',
            'announcement' => 'speakerphone',
            'alert' => 'bell-alert',
            'celebration' => 'trophy',
            'insight' => 'light-bulb',
        ];

        $count = 0;

        if ($validated['target'] === 'all') {
            // Send to all businesses (broadcast)
            $businesses = Business::where('status', 'active')->get();

            foreach ($businesses as $business) {
                $this->notificationService->send(
                    $business,
                    null, // null user_id = broadcast to all users in business
                    $validated['type'],
                    $validated['title'],
                    $validated['message'],
                    [
                        'icon' => $icons[$validated['type']] ?? 'bell',
                        'action_url' => $validated['action_url'],
                        'action_text' => $validated['action_text'],
                    ]
                );
                $count++;
            }
        } elseif ($validated['target'] === 'businesses') {
            // Send to specific businesses
            $businesses = Business::whereIn('id', $validated['business_ids'])->get();

            foreach ($businesses as $business) {
                $this->notificationService->send(
                    $business,
                    null,
                    $validated['type'],
                    $validated['title'],
                    $validated['message'],
                    [
                        'icon' => $icons[$validated['type']] ?? 'bell',
                        'action_url' => $validated['action_url'],
                        'action_text' => $validated['action_text'],
                    ]
                );
                $count++;
            }
        } elseif ($validated['target'] === 'users') {
            // Send to specific users
            $users = User::whereIn('id', $validated['user_ids'])->get();

            foreach ($users as $user) {
                $this->notificationService->sendToUser(
                    $user,
                    $validated['type'],
                    $validated['title'],
                    $validated['message'],
                    [
                        'icon' => $icons[$validated['type']] ?? 'bell',
                        'action_url' => $validated['action_url'],
                        'action_text' => $validated['action_text'],
                    ]
                );
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} ta bildirishnoma yuborildi",
            'count' => $count,
        ]);
    }

    /**
     * Show single notification
     */
    public function show(Notification $notification)
    {
        $notification->load(['user:id,name,email', 'business:id,name']);

        return Inertia::render('Admin/Notifications/Show', [
            'notification' => $notification,
            'types' => $this->getNotificationTypes(),
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bildirishnoma o\'chirildi',
        ]);
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:in_app_notifications,id',
        ]);

        $count = Notification::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} ta bildirishnoma o'chirildi",
            'count' => $count,
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request)
    {
        $period = $request->input('period', '30'); // days

        // Notifications by type over time
        $byType = Notification::select(
            'type',
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('type', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('type');

        // Read rate
        $readRate = [
            'read' => Notification::read()->where('created_at', '>=', now()->subDays($period))->count(),
            'unread' => Notification::unread()->where('created_at', '>=', now()->subDays($period))->count(),
        ];

        // Most notified businesses
        $topBusinesses = Notification::select('business_id', DB::raw('COUNT(*) as count'))
            ->with('business:id,name')
            ->groupBy('business_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Daily notifications
        $dailyNotifications = Notification::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'by_type' => $byType,
            'read_rate' => $readRate,
            'top_businesses' => $topBusinesses,
            'daily_notifications' => $dailyNotifications,
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * Get users for select dropdown
     */
    public function getUsers(Request $request)
    {
        $search = $request->input('search');

        $query = User::select('id', 'name', 'email')
            ->whereHas('businesses');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'users' => $query->limit(50)->get(),
        ]);
    }

    /**
     * Get businesses for select dropdown
     */
    public function getBusinesses(Request $request)
    {
        $search = $request->input('search');

        $query = Business::select('id', 'name')
            ->where('status', 'active');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'businesses' => $query->limit(50)->get(),
        ]);
    }
}
