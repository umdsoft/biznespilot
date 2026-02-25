<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use App\Services\Store\StoreOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StoreOrderController extends Controller
{
    use HasCurrentBusiness, HasStorePanelType;

    public function __construct(
        protected StoreOrderService $orderService
    ) {}

    /**
     * Get the store for the current business
     */
    protected function getStore(): ?TelegramStore
    {
        $business = $this->getCurrentBusiness();

        return TelegramStore::where('business_id', $business->id)->first();
    }

    /**
     * List orders with status filters and pagination
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

        $query = StoreOrder::where('store_id', $store->id)
            ->with(['customer', 'items']);

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereIn('status', StoreOrder::ACTIVE_STATUSES);
            } elseif ($request->status === 'terminal') {
                $query->whereIn('status', StoreOrder::TERMINAL_STATUSES);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by order number or customer name/phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['order_number', 'total', 'status', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $orders = $query->paginate(20)->through(fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->getStatusLabel(),
            'payment_status' => $order->payment_status,
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'discount_amount' => $order->discount_amount,
            'total' => $order->total,
            'items_count' => $order->items->count(),
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->getDisplayName(),
                'phone' => $order->customer->phone,
            ] : null,
            'is_paid' => $order->isPaid(),
            'is_active' => $order->isActive(),
            'is_cancellable' => $order->isCancellable(),
            'created_at' => $order->created_at?->toISOString(),
        ]);

        // Order statistics
        $stats = $this->orderService->getStats($store, 'month');

        return Inertia::render('Business/Store/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'payment_status', 'date_from', 'date_to', 'sort', 'direction']),
            'statusOptions' => [
                ['value' => '', 'label' => 'Barchasi'],
                ['value' => 'active', 'label' => 'Faol'],
                ['value' => StoreOrder::STATUS_PENDING, 'label' => 'Kutilmoqda'],
                ['value' => StoreOrder::STATUS_CONFIRMED, 'label' => 'Tasdiqlangan'],
                ['value' => StoreOrder::STATUS_PROCESSING, 'label' => 'Tayyorlanmoqda'],
                ['value' => StoreOrder::STATUS_SHIPPED, 'label' => 'Yetkazilmoqda'],
                ['value' => StoreOrder::STATUS_DELIVERED, 'label' => 'Yetkazildi'],
                ['value' => StoreOrder::STATUS_CANCELLED, 'label' => 'Bekor qilingan'],
                ['value' => StoreOrder::STATUS_REFUNDED, 'label' => 'Qaytarilgan'],
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Show order details
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

        $order = StoreOrder::where('store_id', $store->id)
            ->with([
                'customer',
                'items.product' => function ($q) {
                    $q->with('primaryImage');
                },
                'statusHistory.changedByUser',
                'paymentTransaction',
            ])
            ->findOrFail($id);

        // Determine allowed transitions for current status
        $allowedTransitions = StoreOrder::STATUS_TRANSITIONS[$order->status] ?? [];

        $transitionLabels = [];
        foreach ($allowedTransitions as $status) {
            $transitionLabels[] = [
                'value' => $status,
                'label' => match ($status) {
                    StoreOrder::STATUS_CONFIRMED => 'Tasdiqlash',
                    StoreOrder::STATUS_PROCESSING => 'Tayyorlashni boshlash',
                    StoreOrder::STATUS_SHIPPED => 'Yetkazishga berish',
                    StoreOrder::STATUS_DELIVERED => 'Yetkazildi deb belgilash',
                    StoreOrder::STATUS_CANCELLED => 'Bekor qilish',
                    StoreOrder::STATUS_REFUNDED => 'Qaytarish',
                    default => $status,
                },
            ];
        }

        return Inertia::render('Business/Store/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'discount_amount' => $order->discount_amount,
                'total' => $order->total,
                'delivery_address' => $order->delivery_address,
                'notes' => $order->notes,
                'promo_code' => $order->promo_code,
                'is_paid' => $order->isPaid(),
                'is_active' => $order->isActive(),
                'is_cancellable' => $order->isCancellable(),
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'name' => $order->customer->getDisplayName(),
                    'phone' => $order->customer->phone,
                    'telegram_username' => $order->customer->telegram_username,
                    'orders_count' => $order->customer->orders_count,
                    'total_spent' => $order->customer->total_spent,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'product_image' => $item->product?->primaryImage?->image_url,
                    'product_id' => $item->product_id,
                ]),
                'status_history' => $order->statusHistory->map(fn ($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'comment' => $history->comment,
                    'changed_by' => $history->changedByUser?->name,
                    'created_at' => $history->created_at?->toISOString(),
                ]),
                'payment_transaction' => $order->paymentTransaction ? [
                    'provider' => $order->paymentTransaction->provider,
                    'amount' => $order->paymentTransaction->amount,
                    'status' => $order->paymentTransaction->status,
                    'paid_at' => $order->paymentTransaction->paid_at?->format('d.m.Y H:i'),
                ] : null,
                'paid_at' => $order->paid_at?->toISOString(),
                'confirmed_at' => $order->confirmed_at?->toISOString(),
                'shipped_at' => $order->shipped_at?->toISOString(),
                'delivered_at' => $order->delivered_at?->toISOString(),
                'cancelled_at' => $order->cancelled_at?->toISOString(),
                'created_at' => $order->created_at?->toISOString(),
            ],
            'allowedTransitions' => $transitionLabels,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $order = StoreOrder::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', [
                StoreOrder::STATUS_CONFIRMED,
                StoreOrder::STATUS_PROCESSING,
                StoreOrder::STATUS_SHIPPED,
                StoreOrder::STATUS_DELIVERED,
                StoreOrder::STATUS_CANCELLED,
                StoreOrder::STATUS_REFUNDED,
            ]),
            'comment' => 'nullable|string|max:500',
        ]);

        if (! $order->canTransitionTo($validated['status'])) {
            return back()->with('error', 'Bu statusga o\'tish mumkin emas.');
        }

        $changedBy = Auth::id();
        $result = $this->orderService->updateStatus(
            $order,
            $validated['status'],
            $validated['comment'] ?? null,
            $changedBy
        );

        if (! $result) {
            return back()->with('error', 'Buyurtma statusini yangilashda xatolik yuz berdi.');
        }

        return $this->storeRedirect('orders.show', [$id])
            ->with('success', 'Buyurtma statusi yangilandi.');
    }

    /**
     * Pending orders count (sidebar badge uchun)
     */
    public function pendingCount()
    {
        $store = $this->getStore();

        if (! $store) {
            return response()->json(['count' => 0]);
        }

        $count = StoreOrder::where('store_id', $store->id)
            ->where('status', StoreOrder::STATUS_PENDING)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Export orders to Excel (SpreadsheetML)
     */
    public function export(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $query = StoreOrder::where('store_id', $store->id)
            ->with(['customer', 'items']);

        // Apply same filters as index
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereIn('status', StoreOrder::ACTIVE_STATUSES);
            } elseif ($request->status === 'terminal') {
                $query->whereIn('status', StoreOrder::TERMINAL_STATUSES);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->get();

        $filename = 'buyurtmalar_' . $store->slug . '_' . now()->format('Y-m-d') . '.xls';

        $columns = [
            'Buyurtma raqami',
            'Sana',
            'Mijoz',
            'Telefon',
            'Status',
            "To'lov holati",
            'Mahsulotlar soni',
            'Subtotal (so\'m)',
            "Yetkazish (so'm)",
            "Chegirma (so'm)",
            "Jami (so'm)",
            'Promo kod',
            'Izoh',
        ];

        $rows = $orders->map(fn ($order) => [
            $order->order_number,
            $order->created_at?->format('d.m.Y H:i') ?? '',
            $order->customer?->getDisplayName() ?? '-',
            $order->customer?->phone ?? '-',
            $order->getStatusLabel(),
            $order->payment_status === 'paid' ? "To'langan" : 'Kutilmoqda',
            $order->items->count(),
            $order->subtotal ?? 0,
            $order->delivery_fee ?? 0,
            $order->discount_amount ?? 0,
            $order->total ?? 0,
            $order->promo_code ?? '-',
            $order->notes ?? '-',
        ]);

        $xml = $this->buildExcelXml($columns, $rows);

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Build SpreadsheetML XML for Excel
     */
    private function buildExcelXml(array $columns, $rows): string
    {
        $escapeXml = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $headerCells = collect($columns)->map(
            fn ($col) => '<Cell><Data ss:Type="String">' . $escapeXml($col) . '</Data></Cell>'
        )->implode('');

        $dataRows = $rows->map(function ($row) use ($escapeXml) {
            $cells = collect($row)->map(function ($val) use ($escapeXml) {
                $type = is_numeric($val) ? 'Number' : 'String';
                return '<Cell><Data ss:Type="' . $type . '">' . $escapeXml($val) . '</Data></Cell>';
            })->implode('');
            return '<Row>' . $cells . '</Row>';
        })->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<?mso-application progid="Excel.Sheet"?>' . "\n" .
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n" .
            '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n" .
            '<Styles><Style ss:ID="Header"><Font ss:Bold="1"/></Style></Styles>' . "\n" .
            '<Worksheet ss:Name="Buyurtmalar"><Table>' . "\n" .
            '<Row>' . $headerCells . '</Row>' . "\n" .
            $dataRows . "\n" .
            '</Table></Worksheet></Workbook>';
    }
}
