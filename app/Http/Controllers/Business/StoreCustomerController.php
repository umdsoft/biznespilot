<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreCustomerController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    /**
     * List customers with search and pagination
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup('Avval do\'kon yarating.');
        }

        $query = StoreCustomer::where('store_id', $store->id)
            ->with('telegramUser');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('telegramUser', function ($tq) use ($search) {
                        $tq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Sort options
        $sortField = $request->input('sort', 'last_order_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'orders_count', 'total_spent', 'last_order_at', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('last_order_at');
        }

        $customers = $query->paginate(20)->through(fn ($customer) => [
            'id' => $customer->id,
            'name' => $customer->getDisplayName(),
            'phone' => $customer->phone,
            'orders_count' => $customer->orders_count,
            'total_spent' => $customer->total_spent,
            'last_order_at' => $customer->last_order_at?->format('d.m.Y H:i'),
            'telegram_username' => $customer->telegramUser?->username,
            'created_at' => $customer->created_at?->format('d.m.Y'),
        ]);

        // Customer statistics
        $totalCustomers = StoreCustomer::where('store_id', $store->id)->count();
        $activeCustomers = StoreCustomer::where('store_id', $store->id)
            ->where('last_order_at', '>=', now()->subDays(30))
            ->count();
        $totalRevenue = StoreCustomer::where('store_id', $store->id)->sum('total_spent');
        $avgOrderValue = StoreCustomer::where('store_id', $store->id)
            ->where('orders_count', '>', 0)
            ->avg('total_spent');

        return Inertia::render('Business/Store/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'sort', 'direction']),
            'stats' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'total_revenue' => round($totalRevenue, 2),
                'avg_customer_value' => $avgOrderValue ? round($avgOrderValue, 2) : 0,
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Show customer details with order history
     */
    public function show(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup();
        }

        $customer = StoreCustomer::where('store_id', $store->id)
            ->with('telegramUser')
            ->findOrFail($id);

        // Get customer's orders with pagination
        $orders = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->with('items')
            ->latest()
            ->paginate(15)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'payment_status' => $order->payment_status,
                'total' => $order->total,
                'items_count' => $order->items->count(),
                'is_paid' => $order->isPaid(),
                'created_at' => $order->created_at?->toISOString(),
            ]);

        // Customer order statistics
        $orderStats = [
            'total_orders' => $customer->orders_count,
            'total_spent' => $customer->total_spent,
            'completed_orders' => StoreOrder::where('customer_id', $customer->id)
                ->where('status', StoreOrder::STATUS_DELIVERED)
                ->count(),
            'cancelled_orders' => StoreOrder::where('customer_id', $customer->id)
                ->where('status', StoreOrder::STATUS_CANCELLED)
                ->count(),
            'avg_order_value' => $customer->orders_count > 0
                ? round($customer->total_spent / $customer->orders_count, 2)
                : 0,
            'first_order_at' => StoreOrder::where('customer_id', $customer->id)
                ->oldest()
                ->value('created_at')?->format('d.m.Y'),
        ];

        // Get reviews by this customer
        $reviews = $customer->reviews()
            ->with('product')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'product_name' => $review->product?->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'is_approved' => $review->is_approved,
                'created_at' => $review->created_at?->format('d.m.Y'),
            ]);

        // Format address from JSON/string
        $formattedAddress = '';
        $addr = $customer->address;
        if ($addr) {
            if (is_string($addr)) {
                $decoded = json_decode($addr, true);
                $addr = $decoded ?: $addr;
            }
            if (is_array($addr)) {
                $parts = array_filter([
                    $addr['city'] ?? null,
                    $addr['district'] ?? null,
                    $addr['street'] ?? null,
                    isset($addr['apartment']) ? "kv. {$addr['apartment']}" : null,
                ]);
                $formattedAddress = implode(', ', $parts);
                if (! empty($addr['comment'])) {
                    $formattedAddress .= " ({$addr['comment']})";
                }
            } else {
                $formattedAddress = (string) $addr;
            }
        }

        // Calculate real stats from orders (not relying on denormalized counters)
        $realOrdersCount = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->count();
        $realTotalSpent = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
            ->sum('total');
        $realAvgOrder = $realOrdersCount > 0 ? round($realTotalSpent / $realOrdersCount, 2) : 0;

        return Inertia::render('Business/Store/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->getDisplayName(),
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $formattedAddress,
                'orders_count' => $realOrdersCount,
                'total_spent' => $realTotalSpent,
                'avg_order' => $realAvgOrder,
                'last_order_at' => $customer->last_order_at?->toISOString(),
                'telegram_username' => $customer->telegramUser?->username,
                'created_at' => $customer->created_at?->toISOString(),
            ],
            'orders' => $orders,
            'orderStats' => $orderStats,
            'reviews' => $reviews,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }
}
