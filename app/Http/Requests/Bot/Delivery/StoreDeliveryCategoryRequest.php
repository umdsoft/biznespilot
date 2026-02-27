<?php

namespace App\Http\Requests\Bot\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryCategoryRequest extends FormRequest
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
            'icon' => ['nullable', 'string', 'max:100'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'uuid', 'exists:delivery_categories,id'],
        ];
    }
}
