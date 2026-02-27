<?php

namespace App\Http\Requests\Bot\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'specializations' => ['nullable', 'array'],
            'specializations.*' => ['string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'bio' => ['nullable', 'string'],
            'warranty_months' => ['nullable', 'integer', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['uuid', 'exists:service_categories,id'],
        ];
    }
}
