<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDreamBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',

            // Demographics
            'age_range' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female,all',
            'location' => 'nullable|string|max:255',
            'income_level' => 'nullable|string|max:100',
            'education' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:255',

            // Sabri Suby 9 questions
            'where_spend_time' => 'nullable|string|max:2000',
            'info_sources' => 'nullable|string|max:2000',
            'frustrations' => 'nullable|string|max:2000',
            'dreams' => 'nullable|string|max:2000',
            'fears' => 'nullable|string|max:2000',
            'communication_preferences' => 'nullable|string|max:2000',
            'language_style' => 'nullable|string|max:2000',
            'daily_routine' => 'nullable|string|max:2000',
            'happiness_triggers' => 'nullable|string|max:2000',

            // Behavioral
            'buying_behavior' => 'nullable|string|max:2000',
            'decision_factors' => 'nullable|array',
            'decision_factors.*' => 'string',
            'objections' => 'nullable|array',
            'objections.*' => 'string',
            'pain_points' => 'nullable|array',
            'pain_points.*' => 'string',

            // Profile
            'avatar_url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'gender.in' => 'Noto\'g\'ri jins tanlov',
            'avatar_url.url' => 'Noto\'g\'ri rasm URL',
        ];
    }
}
