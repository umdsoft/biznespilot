<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreCustomerAddress;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /profile — Return customer info with saved addresses.
     */
    public function show(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->getDisplayName(),
                    'phone' => $customer->phone,
                    'addresses' => $customer->addresses()
                        ->orderByDesc('is_default')
                        ->orderByDesc('updated_at')
                        ->get()
                        ->map(fn ($addr) => [
                            'id' => $addr->id,
                            'label' => $addr->label,
                            'city' => $addr->city,
                            'district' => $addr->district,
                            'street' => $addr->street,
                            'apartment' => $addr->apartment,
                            'entrance' => $addr->entrance,
                            'floor' => $addr->floor,
                            'full_address' => $addr->full_address,
                            'latitude' => $addr->latitude ? (float) $addr->latitude : null,
                            'longitude' => $addr->longitude ? (float) $addr->longitude : null,
                            'instructions' => $addr->instructions,
                            'is_default' => $addr->is_default,
                        ]),
                ],
            ],
        ]);
    }

    /**
     * POST /profile/addresses — Save a new address.
     */
    public function storeAddress(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'street' => 'required|string|max:500',
            'apartment' => 'nullable|string|max:100',
            'entrance' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'instructions' => 'nullable|string|max:500',
            'is_default' => 'boolean',
        ]);

        // Build full_address from components
        $parts = array_filter([
            $validated['city'] ?? '',
            $validated['district'] ?? '',
            $validated['street'] ?? '',
            ! empty($validated['apartment']) ? 'kv. '.$validated['apartment'] : null,
        ]);
        $validated['full_address'] = implode(', ', $parts);

        try {
            // If this is set as default, unset previous defaults
            if (! empty($validated['is_default'])) {
                $customer->addresses()->update(['is_default' => false]);
            }

            // If this is the first address, make it default
            if ($customer->addresses()->count() === 0) {
                $validated['is_default'] = true;
            }

            $customer->addresses()->create($validated);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Address save failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Manzilni saqlashda xatolik: '.$e->getMessage(),
            ], 500);
        }

        // Return updated addresses list
        return response()->json([
            'success' => true,
            'message' => 'Manzil saqlandi',
            'addresses' => $customer->addresses()
                ->orderByDesc('is_default')
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn ($addr) => [
                    'id' => $addr->id,
                    'label' => $addr->label,
                    'city' => $addr->city,
                    'district' => $addr->district,
                    'street' => $addr->street,
                    'apartment' => $addr->apartment,
                    'entrance' => $addr->entrance,
                    'floor' => $addr->floor,
                    'full_address' => $addr->full_address,
                    'latitude' => $addr->latitude ? (float) $addr->latitude : null,
                    'longitude' => $addr->longitude ? (float) $addr->longitude : null,
                    'instructions' => $addr->instructions,
                    'is_default' => $addr->is_default,
                ]),
        ], 201);
    }

    /**
     * DELETE /profile/addresses/{address} — Delete a saved address.
     */
    public function deleteAddress(Request $request, TelegramStore $store, string $address): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $addr = $customer->addresses()->where('id', $address)->first();

        if (! $addr) {
            return response()->json(['success' => false, 'message' => 'Manzil topilmadi'], 404);
        }

        $wasDefault = $addr->is_default;
        $addr->delete();

        // If deleted address was default, assign default to another
        if ($wasDefault) {
            $next = $customer->addresses()->latest()->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json(['success' => true, 'message' => "Manzil o'chirildi"]);
    }

    /**
     * PUT /profile/addresses/{address}/default — Set address as default.
     */
    public function setDefault(Request $request, TelegramStore $store, string $address): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $addr = $customer->addresses()->where('id', $address)->first();

        if (! $addr) {
            return response()->json(['success' => false, 'message' => 'Manzil topilmadi'], 404);
        }

        $customer->addresses()->update(['is_default' => false]);
        $addr->update(['is_default' => true]);

        return response()->json(['success' => true, 'message' => 'Asosiy manzil belgilandi']);
    }
}
