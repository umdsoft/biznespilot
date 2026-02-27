<?php

namespace App\Http\Requests\Bot\Queue;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'duration_min' => ['required', 'integer', 'min:1'],
            'duration_max' => ['required', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'requires_branch' => ['nullable', 'boolean'],
        ];
    }
}
