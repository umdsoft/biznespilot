<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreOrder;
use App\Models\Store\StoreOrderItem;
use App\Models\Store\StoreServiceRequest;
use App\Models\Store\StoreStaff;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    /**
     * GET /service-requests — User's service requests list.
     *
     * Service requests = orders where at least one item has item_type = StoreServiceRequest.
     */
    public function index(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $orders = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->whereHas('items', fn ($q) => $q->where('item_type', StoreServiceRequest::class))
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        $items = $orders->getCollection()->map(function ($order) {
            $serviceItem = $order->items->first();
            $meta = $serviceItem?->item_metadata ?? [];
            return [
                'id' => $order->id,
                'service_name' => $serviceItem?->product_name ?? 'Xizmat',
                'description' => $order->notes ?? '',
                'status' => $order->status,
                'total_price' => $order->total,
                'address' => $order->delivery_address['street'] ?? null,
                'images' => $meta['images'] ?? [],
                'created_at' => $order->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'has_more' => $orders->hasMorePages(),
        ]);
    }

    /**
     * GET /service-requests/{id} — Service request detail.
     */
    public function show(Request $request, TelegramStore $store, string $id): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $order = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('id', $id)
            ->with('items')
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => "So'rov topilmadi"], 404);
        }

        $serviceItem = $order->items->first();
        $meta = $serviceItem?->item_metadata ?? [];

        // Build timeline from status history + stored data
        $timeline = $meta['timeline'] ?? [];
        if (empty($timeline)) {
            $timeline = [
                [
                    'title' => "So'rov yuborildi",
                    'description' => "Sizning so'rovingiz qabul qilindi",
                    'created_at' => $order->created_at->toISOString(),
                ],
            ];
            if ($order->confirmed_at) {
                $timeline[] = [
                    'title' => 'Tasdiqlandi',
                    'description' => 'So\'rov ko\'rib chiqildi va qabul qilindi',
                    'created_at' => $order->confirmed_at->toISOString(),
                ];
            }
            if (in_array($order->status, ['processing', 'shipped', 'delivered'])) {
                $timeline[] = [
                    'title' => 'Jarayonda',
                    'description' => 'Usta ishni boshladi',
                    'created_at' => $order->updated_at->toISOString(),
                ];
            }
            if ($order->status === 'delivered') {
                $timeline[] = [
                    'title' => 'Yakunlandi',
                    'description' => 'Ish muvaffaqiyatli bajarildi',
                    'created_at' => $order->delivered_at?->toISOString() ?? $order->updated_at->toISOString(),
                ];
            }
        }

        // Look up assigned staff
        $staffData = null;
        if (! empty($meta['staff_id'])) {
            $staff = StoreStaff::find($meta['staff_id']);
            if ($staff) {
                $staffData = [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'photo_url' => $staff->photo_url,
                    'position' => $staff->position,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'service_name' => $serviceItem?->product_name ?? 'Xizmat',
                'description' => $order->notes ?? '',
                'status' => $order->status,
                'total_price' => $order->total,
                'address' => $order->delivery_address['street'] ?? null,
                'phone' => $meta['phone'] ?? null,
                'preferred_date' => $meta['preferred_date'] ?? null,
                'images' => $meta['images'] ?? [],
                'timeline' => $timeline,
                'staff' => $staffData,
                'created_at' => $order->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * POST /service-requests — Create a new service request.
     */
    public function store(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $validated = $request->validate([
            'service_id' => 'required|uuid',
            'description' => 'required|string|max:5000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:2000',
            'preferred_date' => 'nullable|date|after_or_equal:today',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ]);

        $serviceTemplate = StoreServiceRequest::where('store_id', $store->id)
            ->where('id', $validated['service_id'])
            ->active()
            ->first();

        if (! $serviceTemplate) {
            return response()->json(['success' => false, 'message' => 'Xizmat topilmadi'], 404);
        }

        // Upload images
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("stores/{$store->id}/service-requests", 'public');
                $imageUrls[] = Storage::disk('public')->url($path);
            }
        }

        // Create order (using existing StoreOrder schema)
        $order = StoreOrder::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => 'SR-' . strtoupper(Str::random(8)),
            'status' => 'pending',
            'subtotal' => $serviceTemplate->base_price ?? 0,
            'total' => $serviceTemplate->base_price ?? 0,
            'notes' => $validated['description'],
            'delivery_address' => ! empty($validated['address']) ? ['street' => $validated['address']] : null,
        ]);

        // Create order item with service request metadata
        $order->items()->create([
            'item_type' => StoreServiceRequest::class,
            'item_id' => $serviceTemplate->id,
            'product_name' => $serviceTemplate->name,
            'quantity' => 1,
            'price' => $serviceTemplate->base_price ?? 0,
            'total' => $serviceTemplate->base_price ?? 0,
            'item_metadata' => [
                'phone' => $validated['phone'] ?? $customer->phone ?? null,
                'preferred_date' => $validated['preferred_date'] ?? null,
                'images' => $imageUrls,
                'pricing_type' => $serviceTemplate->pricing_type,
                'extra_notes' => $validated['notes'] ?? null,
                'timeline' => [
                    [
                        'title' => "So'rov yuborildi",
                        'description' => "Sizning so'rovingiz qabul qilindi va tez orada ko'rib chiqiladi",
                        'created_at' => now()->toISOString(),
                    ],
                ],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "So'rov yuborildi",
            'data' => [
                'request' => [
                    'id' => $order->id,
                    'service_name' => $serviceTemplate->name,
                    'status' => 'pending',
                ],
            ],
        ], 201);
    }

    /**
     * POST /service-requests/{id}/cancel — Cancel a service request.
     */
    public function cancel(Request $request, TelegramStore $store, string $id): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $order = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('id', $id)
            ->whereHas('items', fn ($q) => $q->where('item_type', StoreServiceRequest::class))
            ->whereIn('status', ['pending'])
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => "So'rov topilmadi yoki bekor qilib bo'lmaydi"], 404);
        }

        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "So'rov bekor qilindi",
            'data' => ['id' => $order->id, 'status' => 'cancelled'],
        ]);
    }

    /**
     * GET /masters — Masters (staff) list for service bot.
     */
    public function masters(Request $request, TelegramStore $store): JsonResponse
    {
        $staff = StoreStaff::where('store_id', $store->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'position' => $s->position,
                'photo_url' => $s->photo_url,
                'bio' => $s->bio,
                'specializations' => $s->specializations,
            ]);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * GET /masters/{id} — Master profile.
     */
    public function masterShow(Request $request, TelegramStore $store, string $id): JsonResponse
    {
        $staff = StoreStaff::where('store_id', $store->id)
            ->where('id', $id)
            ->active()
            ->first();

        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Usta topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'position' => $staff->position,
                'photo_url' => $staff->photo_url,
                'bio' => $staff->bio,
                'phone' => $staff->phone,
                'specializations' => $staff->specializations,
                'reviews' => [],
            ],
        ]);
    }
}
