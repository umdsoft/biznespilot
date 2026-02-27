<?php

namespace App\Http\Controllers\Api\Bot\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Bot\Delivery\DeliveryAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $telegramUserId = $request->input('telegram_user_id');

        $addresses = DeliveryAddress::forBusiness($businessId)
            ->byUser($telegramUserId)
            ->orderByDesc('is_default')
            ->get();

        return response()->json(['addresses' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => 'required|integer',
            'label' => 'required|string|max:50',
            'address' => 'required|string',
            'landmark' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        $businessId = $request->header('X-Business-Id');
        $data['business_id'] = $businessId;

        if (! empty($data['is_default'])) {
            DeliveryAddress::forBusiness($businessId)
                ->byUser($data['telegram_user_id'])
                ->update(['is_default' => false]);
        }

        $address = DeliveryAddress::create($data);

        return response()->json(['address' => $address], 201);
    }

    public function update(Request $request, DeliveryAddress $addr): JsonResponse
    {
        $data = $request->validate([
            'label' => 'sometimes|string|max:50',
            'address' => 'sometimes|string',
            'landmark' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        if (! empty($data['is_default'])) {
            DeliveryAddress::forBusiness($addr->business_id)
                ->byUser($addr->telegram_user_id)
                ->where('id', '!=', $addr->id)
                ->update(['is_default' => false]);
        }

        $addr->update($data);

        return response()->json(['address' => $addr]);
    }

    public function destroy(DeliveryAddress $addr): JsonResponse
    {
        $addr->delete();

        return response()->json(['message' => 'Manzil o\'chirildi.']);
    }
}
