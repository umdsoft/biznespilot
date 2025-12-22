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
            'monthly_revenue_range' => 'nullable|in:none,under_5m,5m_20m,20m_50m,50m_100m,100m_500m,500m_1b,over_1b',

            // Challenges
            'main_challenges' => 'nullable|array',
            'main_challenges.*' => 'string',

            // Infrastructure
            'has_website' => 'nullable|boolean',
            'has_crm' => 'nullable|boolean',
            'uses_analytics' => 'nullable|boolean',
            'has_automation' => 'nullable|boolean',
            'current_tools' => 'nullable|array',
            'current_tools.*' => 'string',

            // Processes
            'has_documented_processes' => 'nullable|boolean',
            'has_sales_process' => 'nullable|boolean',
            'has_support_process' => 'nullable|boolean',
            'has_marketing_process' => 'nullable|boolean',

            // Marketing
            'marketing_channels' => 'nullable|array',
            'marketing_channels.*' => 'string',
            'has_marketing_budget' => 'nullable|boolean',
            'tracks_marketing_metrics' => 'nullable|boolean',
            'has_dedicated_marketing' => 'nullable|boolean',

            // Goals
            'primary_goals' => 'nullable|array',
            'primary_goals.*' => 'string',
            'growth_target' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'monthly_revenue_range.in' => 'Noto\'g\'ri daromad diapazoni',
        ];
    }
}
