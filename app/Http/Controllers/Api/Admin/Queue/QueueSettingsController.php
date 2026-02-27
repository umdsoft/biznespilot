<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Queue\QueueSettingsRequest;
use App\Models\Bot\Queue\QueueSetting;
use Illuminate\Http\JsonResponse;

class QueueSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = QueueSetting::getForBusiness(session('current_business_id'));

        return response()->json(['settings' => $settings]);
    }

    public function update(QueueSettingsRequest $request): JsonResponse
    {
        $settings = QueueSetting::getForBusiness(session('current_business_id'));
        $settings->update($request->validated());

        return response()->json(['settings' => $settings]);
    }
}
