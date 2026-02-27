<?php

namespace App\Http\Controllers\Api\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Delivery\DeliverySettingsRequest;
use App\Models\Bot\Delivery\DeliverySetting;
use Illuminate\Http\JsonResponse;

class DeliverySettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = DeliverySetting::getForBusiness(session('current_business_id'));

        return response()->json(['settings' => $settings]);
    }

    public function update(DeliverySettingsRequest $request): JsonResponse
    {
        $settings = DeliverySetting::getForBusiness(session('current_business_id'));
        $settings->update($request->validated());

        return response()->json(['settings' => $settings]);
    }
}
