<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreHypothesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hypothesis_type' => 'required|in:channel,content,offer,audience,funnel',
            'if_statement' => 'required|string|max:500',
            'then_statement' => 'required|string|max:500',
            'because_statement' => 'required|string|max:500',
            'test_method' => 'nullable|in:a_b_test,pilot,survey,mvp',
            'success_metric' => 'nullable|string|max:255',
            'target_value' => 'nullable|numeric|min:0',
            'baseline_value' => 'nullable|numeric|min:0',
            'test_duration_days' => 'nullable|integer|min:1|max:365',
            'sample_size_needed' => 'nullable|integer|min:1',
            'confidence_level' => 'nullable|in:low,medium,high',
        ];
    }

    public function messages(): array
    {
        return [
            'hypothesis_type.required' => 'Gipoteza turi tanlanishi shart',
            'hypothesis_type.in' => 'Noto\'g\'ri gipoteza turi',
            'if_statement.required' => '"AGAR" qismi kiritilishi shart',
            'then_statement.required' => '"U HOLDA" qismi kiritilishi shart',
            'because_statement.required' => '"CHUNKI" qismi kiritilishi shart',
            'test_method.in' => 'Noto\'g\'ri test usuli',
            'confidence_level.in' => 'Noto\'g\'ri ishonch darajasi',
        ];
    }
}
