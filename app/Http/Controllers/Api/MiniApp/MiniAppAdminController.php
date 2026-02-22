<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MiniAppAdminController extends Controller
{
    /**
     * Dashboard — buyurtmalar soni, daromad, statistika
     */
    public function dashboard(Request $request): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $today = now()->toDateString();
        $ordersToday = StoreOrder::where('store_id', $store->id)
            ->whereDate('created_at', $today)
            ->count();

        $revenueToday = StoreOrder::where('store_id', $store->id)
            ->whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total');

        $pendingOrders = StoreOrder::where('store_id', $store->id)
            ->whereIn('status', StoreOrder::ACTIVE_STATUSES)
            ->count();

        $totalOrders = StoreOrder::where('store_id', $store->id)->count();
        $totalRevenue = StoreOrder::where('store_id', $store->id)
            ->where('payment_status', 'paid')
            ->sum('total');

        return response()->json([
            'store_name' => $store->name,
            'store_type' => $store->store_type,
            'today' => [
                'orders' => $ordersToday,
                'revenue' => (float) $revenueToday,
            ],
            'pending_orders' => $pendingOrders,
            'total' => [
                'orders' => $totalOrders,
                'revenue' => (float) $totalRevenue,
            ],
        ]);
    }

    /**
     * Buyurtmalar ro'yxati
     */
    public function orders(Request $request): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $status = $request->input('status');
        $query = StoreOrder::where('store_id', $store->id)
            ->with(['customer:id,name,phone'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20);

        return response()->json([
            'orders' => $orders->items(),
            'has_more' => $orders->hasMorePages(),
            'total' => $orders->total(),
        ]);
    }

    /**
     * Buyurtma tafsiloti
     */
    public function orderDetail(Request $request, string $order): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $orderModel = StoreOrder::where('store_id', $store->id)
            ->where('id', $order)
            ->with(['customer', 'items', 'statusHistory'])
            ->firstOrFail();

        return response()->json($orderModel);
    }

    /**
     * Buyurtma statusini yangilash
     */
    public function updateOrderStatus(Request $request, string $order): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:confirmed,processing,shipped,delivered,cancelled',
        ]);

        $orderModel = StoreOrder::where('store_id', $store->id)
            ->where('id', $order)
            ->firstOrFail();

        // Validate transition using the model's built-in method
        if (!$orderModel->canTransitionTo($validated['status'])) {
            return response()->json([
                'error' => "Bu o'tish mumkin emas: {$orderModel->status} -> {$validated['status']}"
            ], 422);
        }

        // Use the model's transitionTo method which also creates status history
        $telegramUserData = $request->attributes->get('telegram_user_data');
        $changedBy = $telegramUserData['first_name'] ?? 'Admin';

        $orderModel->transitionTo($validated['status'], null, $changedBy);

        return response()->json([
            'success' => true,
            'order' => $orderModel->fresh(['customer', 'items', 'statusHistory']),
        ]);
    }

    /**
     * Katalog elementlari
     */
    public function catalog(Request $request): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $modelClass = $store->getCatalogModelClass();
        if (!$modelClass) {
            return response()->json(['items' => []]);
        }

        $items = $modelClass::where('store_id', $store->id)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'name', 'price', 'is_active', 'is_featured']);

        return response()->json(['items' => $items]);
    }

    /**
     * Katalog elementni yoqish/o'chirish
     */
    public function toggleCatalogItem(Request $request, string $id): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $modelClass = $store->getCatalogModelClass();
        if (!$modelClass) {
            return response()->json(['error' => 'Katalog topilmadi'], 404);
        }

        $item = $modelClass::where('store_id', $store->id)->where('id', $id)->firstOrFail();
        $item->update(['is_active' => !$item->is_active]);

        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    /**
     * Statistika — oxirgi 7 kunlik daromad va buyurtmalar
     */
    public function stats(Request $request): JsonResponse
    {
        $store = $request->route('store');

        if (!$this->isStoreAdmin($request, $store)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        // Last 7 days revenue
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $daily[] = [
                'date' => $date,
                'orders' => StoreOrder::where('store_id', $store->id)->whereDate('created_at', $date)->count(),
                'revenue' => (float) StoreOrder::where('store_id', $store->id)->whereDate('created_at', $date)->where('payment_status', 'paid')->sum('total'),
            ];
        }

        return response()->json(['daily' => $daily]);
    }

    /**
     * Check if the request user is a store admin.
     *
     * The telegram user's ID from initData is compared against:
     * 1. Business users' telegram_chat_id (system bot linkage)
     * 2. TelegramBot owner's telegram_id
     */
    protected function isStoreAdmin(Request $request, TelegramStore $store): bool
    {
        $telegramUserData = $request->attributes->get('telegram_user_data');
        $telegramId = $telegramUserData['id'] ?? null;

        if (!$telegramId) {
            return false;
        }

        // Check if this telegram user owns the bot or is listed as admin
        $bot = $store->telegramBot;
        if (!$bot) {
            return false;
        }

        // Business owner check — match telegram_chat_id on User model
        $business = $store->business;
        if (!$business) {
            return false;
        }

        // Check if any business team member has this telegram_chat_id
        // (System bot linking stores telegram chat ID = telegram user ID)
        $isAdmin = $business->users()
            ->where('telegram_chat_id', (string) $telegramId)
            ->exists();

        if ($isAdmin) {
            return true;
        }

        // Fallback: check store settings for admin telegram IDs
        $adminIds = $store->getSetting('admin_telegram_ids', []);
        if (in_array($telegramId, $adminIds)) {
            return true;
        }

        return false;
    }
}
