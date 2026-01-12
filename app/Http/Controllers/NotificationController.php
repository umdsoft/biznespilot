<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): Response
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $query = Notification::where('business_id', $business->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by read status
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        $notifications = $query->paginate(30);

        $stats = [
            'total' => Notification::where('business_id', $business->id)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhereNull('user_id');
                })->count(),
            'unread' => $this->notificationService->getUnreadCount($business, $user),
        ];

        return Inertia::render('Dashboard/Notifications/Index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'filters' => [
                'type' => $request->type ?? 'all',
                'status' => $request->status ?? 'all',
            ],
        ]);
    }

    public function getUnread()
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $notifications = $this->notificationService->getUnreadNotifications($business, $user, 10);
        $count = $this->notificationService->getUnreadCount($business, $user);

        return response()->json([
            'notifications' => $notifications,
            'count' => $count,
        ]);
    }

    public function markAsRead(string $id)
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $notification = Notification::where('business_id', $business->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->findOrFail($id);

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    public function markAsClicked(string $id)
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $notification = Notification::where('business_id', $business->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->findOrFail($id);

        $this->notificationService->markAsClicked($notification);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $count = $this->notificationService->markAllAsRead($business, $user);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    public function delete(string $id)
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $notification = Notification::where('business_id', $business->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['success' => true]);
    }

    public function getCount()
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        return response()->json([
            'count' => $this->notificationService->getUnreadCount($business, $user),
        ]);
    }
}
