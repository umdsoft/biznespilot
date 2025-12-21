<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreProblemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|in:sales,marketing,operations,finance,hr,product,customer_service,technology',
            'impact_level' => 'required|in:low,medium,high,critical',
            'frequency' => 'nullable|in:daily,weekly,monthly,occasionally',
            'current_solution' => 'nullable|string|max:1000',
            'desired_outcome' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Muammo nomi kiritilishi shart',
            'category.required' => 'Kategoriya tanlanishi shart',
            'category.in' => 'Noto\'g\'ri kategoriya',
            'impact_level.required' => 'Ta\'sir darajasi tanlanishi shart',
            'impact_level.in' => 'Noto\'g\'ri ta\'sir darajasi',
            'frequency.in' => 'Noto\'g\'ri chastota',
        ];
    }
}
