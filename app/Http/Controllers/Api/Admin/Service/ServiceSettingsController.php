<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Models\Bot\Service\ServiceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = ServiceSetting::getForBusiness(session('current_business_id'));

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'auto_assign_master' => ['nullable', 'boolean'],
            'allow_master_choice' => ['nullable', 'boolean'],
            'require_cost_approval' => ['nullable', 'boolean'],
            'show_master_location' => ['nullable', 'boolean'],
            'max_images' => ['nullable', 'integer', 'min:1', 'max:20'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.from' => ['required_with:working_hours', 'string'],
            'working_hours.*.to' => ['required_with:working_hours', 'string'],
            'service_area' => ['nullable', 'array'],
        ]);

        $settings = ServiceSetting::getForBusiness(session('current_business_id'));
        $settings->update($data);

        return response()->json(['settings' => $settings]);
    }
}
