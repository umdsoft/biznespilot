<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesAlert;
use App\Models\SalesAlertSetting;
use App\Services\Sales\AlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function __construct(
        private AlertService $alertService
    ) {}

    /**
     * Alertlar ro'yxati
     * Barcha rollar uchun bitta endpoint
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $business = $user->currentBusiness;
        $role = $this->getUserSalesRole($user, $business);

        $alerts = $this->alertService->getAlertsForUser($user, $business);
        $unreadCount = $this->alertService->getUnreadCount($user, $business);

        return Inertia::render('Sales/Alerts/Index', [
            'alerts' => $alerts,
            'role' => $role,
            'unreadCount' => $unreadCount,
            'alertTypes' => SalesAlert::TYPES,
            'priorities' => SalesAlert::PRIORITIES,
        ]);
    }

    /**
     * Faol alertlarni olish (API)
     */
    public function getActive(Request $request)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        $alerts = $this->alertService->getUrgentAlerts($user, $business, 5);
        $unreadCount = $this->alertService->getUnreadCount($user, $business);

        return response()->json([
            'alerts' => $alerts,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Bitta alertni ko'rish
     */
    public function show(Request $request, SalesAlert $alert)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        // Check access
        if ($alert->business_id !== $business->id) {
            abort(403);
        }

        $role = $this->getUserSalesRole($user, $business);
        if ($role === 'sales_operator' && $alert->user_id !== $user->id) {
            abort(403);
        }

        // Mark as read
        if ($alert->status === 'unread') {
            $alert->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['alert' => $alert->load('alertable', 'user')]);
        }

        return Inertia::render('Sales/Alerts/Show', [
            'alert' => $alert->load('alertable', 'user'),
        ]);
    }

    /**
     * Alert tasdiqlash
     */
    public function acknowledge(Request $request, SalesAlert $alert)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        if ($alert->business_id !== $business->id) {
            abort(403);
        }

        $alert->update([
            'status' => 'read',
            'read_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Alertni keyinga qoldirish
     */
    public function snooze(Request $request, SalesAlert $alert)
    {
        $validated = $request->validate([
            'minutes' => 'required|integer|min:5|max:1440', // 5 min to 24 hours
        ]);

        $user = $request->user();
        $business = $user->currentBusiness;

        if ($alert->business_id !== $business->id) {
            abort(403);
        }

        $alert->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addMinutes($validated['minutes']),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Alertni hal qilish
     */
    public function resolve(Request $request, SalesAlert $alert)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        if ($alert->business_id !== $business->id) {
            abort(403);
        }

        $alert->update([
            'status' => 'actioned',
            'actioned_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Alert o'qilgan deb belgilash
     */
    public function markAsRead(Request $request, SalesAlert $alert)
    {
        $this->authorize('update', $alert);

        $alert->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Alert yopish
     */
    public function dismiss(Request $request, SalesAlert $alert)
    {
        $this->authorize('update', $alert);

        $alert->dismiss();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Barcha alertlarni o'qilgan deb belgilash
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $business = $user->currentBusiness;
        $role = $this->getUserSalesRole($user, $business);

        $query = SalesAlert::forBusiness($business->id)->unread();

        if ($role === 'sales_operator') {
            $query->forUser($user->id);
        }

        $query->update([
            'status' => 'read',
            'read_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * O'qilmagan alertlar sonini olish (API)
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        $count = $this->alertService->getUnreadCount($user, $business);

        return response()->json(['count' => $count]);
    }

    /**
     * Alert sozlamalari (faqat ROP va Owner)
     */
    public function settings(Request $request): Response
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        $this->authorize('manageSalesSettings', $business);

        $settings = SalesAlertSetting::getAllForBusiness($business->id);

        return Inertia::render('Sales/Alerts/Settings', [
            'settings' => $settings,
            'alertTypes' => SalesAlert::TYPES,
            'channels' => SalesAlert::CHANNELS,
            'frequencies' => SalesAlertSetting::FREQUENCIES,
        ]);
    }

    /**
     * Alert sozlamalarini yangilash
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $business = $user->currentBusiness;

        $this->authorize('manageSalesSettings', $business);

        $validated = $request->validate([
            'alert_type' => 'required|string',
            'is_enabled' => 'boolean',
            'conditions' => 'nullable|array',
            'recipients' => 'nullable|array',
            'channels' => 'nullable|array',
            'frequency' => 'nullable|string|in:instant,hourly,daily',
            'schedule_time' => 'nullable|string',
        ]);

        $setting = SalesAlertSetting::getOrCreate($business->id, $validated['alert_type']);

        $setting->update([
            'is_enabled' => $validated['is_enabled'] ?? $setting->is_enabled,
            'conditions' => $validated['conditions'] ?? $setting->conditions,
            'recipients' => $validated['recipients'] ?? $setting->recipients,
            'channels' => $validated['channels'] ?? $setting->channels,
            'frequency' => $validated['frequency'] ?? $setting->frequency,
            'schedule_time' => $validated['schedule_time'] ?? $setting->schedule_time,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'setting' => $setting]);
        }

        return back()->with('success', 'Sozlamalar saqlandi');
    }

    /**
     * Foydalanuvchining sotuv rolini olish
     */
    protected function getUserSalesRole($user, $business): string
    {
        if ($business->owner_id === $user->id) {
            return 'owner';
        }

        $pivot = $user->teamBusinesses()
            ->where('businesses.id', $business->id)
            ->first()?->pivot;

        return $pivot?->department ?? 'guest';
    }
}
