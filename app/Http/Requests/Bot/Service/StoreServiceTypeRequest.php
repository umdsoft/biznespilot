<?php

namespace App\Http\Requests\Bot\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'uuid', 'exists:service_categories,id'],
            'description' => ['nullable', 'string'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['nullable', 'string', 'max:50'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
