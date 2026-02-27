<?php

namespace App\Http\Requests\Bot\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'uuid', 'exists:delivery_categories,id'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'preparation_time' => ['nullable', 'integer', 'min:1'],
            'calories' => ['nullable', 'integer', 'min:0'],
            'is_popular' => ['nullable', 'boolean'],
            'is_available' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.group_name' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price_modifier' => ['nullable', 'numeric'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['required_with:addons', 'string', 'max:255'],
            'addons.*.price' => ['required_with:addons', 'numeric', 'min:0'],
            'addons.*.is_available' => ['nullable', 'boolean'],
        ];
    }
}
