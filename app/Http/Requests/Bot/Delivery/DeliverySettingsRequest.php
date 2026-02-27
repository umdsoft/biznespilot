<?php

namespace App\Http\Requests\Bot\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class DeliverySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'free_delivery_from' => ['nullable', 'numeric', 'min:0'],
            'service_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'estimated_delivery_min' => ['nullable', 'integer', 'min:1'],
            'estimated_delivery_max' => ['nullable', 'integer', 'min:1'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.from' => ['required_with:working_hours', 'string'],
            'working_hours.*.to' => ['required_with:working_hours', 'string'],
            'delivery_zones' => ['nullable', 'array'],
            'auto_accept_orders' => ['nullable', 'boolean'],
            'order_notifications' => ['nullable', 'array'],
        ];
    }
}
