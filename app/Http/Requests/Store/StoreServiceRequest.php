<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'compare_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'duration_minutes' => 'nullable|integer|min:1',
            'max_capacity' => 'nullable|integer|min:1',
            'requires_staff' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'image_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Xizmat nomi kiritilishi shart',
            'name.max' => 'Xizmat nomi 255 belgidan oshmasligi kerak',
            'price.required' => 'Narx kiritilishi shart',
            'price.numeric' => 'Narx raqam bo\'lishi kerak',
            'price.min' => 'Narx 0 dan kam bo\'lmasligi kerak',
            'compare_price.numeric' => 'Taqqoslash narxi raqam bo\'lishi kerak',
            'compare_price.min' => 'Taqqoslash narxi 0 dan kam bo\'lmasligi kerak',
            'category_id.exists' => 'Tanlangan kategoriya topilmadi',
            'duration_minutes.integer' => 'Davomiyligi butun son bo\'lishi kerak',
            'duration_minutes.min' => 'Davomiyligi kamida 1 daqiqa bo\'lishi kerak',
            'max_capacity.integer' => 'Maksimal sig\'im butun son bo\'lishi kerak',
            'max_capacity.min' => 'Maksimal sig\'im kamida 1 bo\'lishi kerak',
        ];
    }
}
