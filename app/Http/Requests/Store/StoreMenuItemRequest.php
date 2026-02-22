<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'preparation_time_minutes' => 'nullable|integer|min:1',
            'calories' => 'nullable|integer|min:0',
            'portion_size' => 'nullable|string|max:100',
            'allergens' => 'nullable|array',
            'allergens.*' => 'string|max:100',
            'dietary_tags' => 'nullable|array',
            'dietary_tags.*' => 'string|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'image_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',

            // Modifiers (optional nested creation)
            'modifiers' => 'nullable|array',
            'modifiers.*.name' => 'required_with:modifiers|string|max:255',
            'modifiers.*.price' => 'nullable|numeric|min:0',
            'modifiers.*.is_required' => 'nullable|boolean',
            'modifiers.*.sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Taom nomi kiritilishi shart',
            'name.max' => 'Taom nomi 255 belgidan oshmasligi kerak',
            'price.required' => 'Narx kiritilishi shart',
            'price.numeric' => 'Narx raqam bo\'lishi kerak',
            'price.min' => 'Narx 0 dan kam bo\'lmasligi kerak',
            'category_id.exists' => 'Tanlangan kategoriya topilmadi',
            'preparation_time_minutes.integer' => 'Tayyorlash vaqti butun son bo\'lishi kerak',
            'preparation_time_minutes.min' => 'Tayyorlash vaqti kamida 1 daqiqa bo\'lishi kerak',
            'calories.integer' => 'Kaloriya butun son bo\'lishi kerak',
            'calories.min' => 'Kaloriya 0 dan kam bo\'lmasligi kerak',
            'modifiers.*.name.required_with' => 'Modifikator nomi kiritilishi shart',
        ];
    }
}
