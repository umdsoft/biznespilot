<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaturityAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Revenue
            'monthly_revenue_range' => 'required|in:none,under_5m,5m_20m,20m_50m,50m_100m,100m_500m,500m_1b,over_1b',

            // Challenges
            'main_challenges' => 'required|array|min:1',
            'main_challenges.*' => 'string',

            // Infrastructure
            'has_website' => 'boolean',
            'has_crm' => 'boolean',
            'uses_analytics' => 'boolean',
            'has_automation' => 'boolean',
            'current_tools' => 'nullable|array',
            'current_tools.*' => 'string',

            // Processes
            'has_documented_processes' => 'boolean',
            'has_sales_process' => 'boolean',
            'has_support_process' => 'boolean',
            'has_marketing_process' => 'boolean',

            // Marketing
            'marketing_channels' => 'nullable|array',
            'marketing_channels.*' => 'string',
            'has_marketing_budget' => 'boolean',
            'tracks_marketing_metrics' => 'boolean',
            'has_dedicated_marketing' => 'boolean',

            // Goals
            'primary_goals' => 'nullable|array',
            'primary_goals.*' => 'string',
            'growth_target' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'monthly_revenue_range.required' => 'Oylik daromad ko\'rsatilishi shart',
            'monthly_revenue_range.in' => 'Noto\'g\'ri daromad diapazoni',
            'main_challenges.required' => 'Kamida bitta qiyinchilik tanlanishi shart',
            'main_challenges.min' => 'Kamida bitta qiyinchilik tanlanishi shart',
        ];
    }
}
