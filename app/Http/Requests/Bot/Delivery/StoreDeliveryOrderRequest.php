<?php

namespace App\Http\Requests\Bot\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'telegram_user_id' => ['required', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['required_if:delivery_type,delivery', 'nullable', 'string'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_lat' => ['nullable', 'numeric'],
            'delivery_lng' => ['nullable', 'numeric'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'payment_method' => ['required', 'in:cash,card,click,payme'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'uuid', 'exists:delivery_menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.variant_id' => ['nullable', 'uuid'],
            'items.*.addon_ids' => ['nullable', 'array'],
            'items.*.addon_ids.*' => ['uuid'],
            'items.*.special_instructions' => ['nullable', 'string', 'max:300'],
        ];
    }
}
