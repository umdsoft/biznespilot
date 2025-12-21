<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompetitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
            'competitor_type' => 'nullable|in:direct,indirect,potential',
            'market_position' => 'nullable|in:leader,challenger,follower,nicher',

            // Social media
            'instagram_url' => 'nullable|url|max:500',
            'telegram_url' => 'nullable|url|max:500',
            'facebook_url' => 'nullable|url|max:500',
            'instagram_followers' => 'nullable|integer|min:0',
            'telegram_subscribers' => 'nullable|integer|min:0',

            // Analysis
            'strengths' => 'nullable|array',
            'strengths.*' => 'string',
            'weaknesses' => 'nullable|array',
            'weaknesses.*' => 'string',
            'key_differentiators' => 'nullable|array',
            'key_differentiators.*' => 'string',

            // Pricing
            'price_range' => 'nullable|string|max:100',
            'pricing_model' => 'nullable|string|max:255',

            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Raqobatchi nomi kiritilishi shart',
            'website.url' => 'Noto\'g\'ri web-sayt manzili',
            'instagram_url.url' => 'Noto\'g\'ri Instagram manzili',
            'telegram_url.url' => 'Noto\'g\'ri Telegram manzili',
            'facebook_url.url' => 'Noto\'g\'ri Facebook manzili',
            'competitor_type.in' => 'Noto\'g\'ri raqobatchi turi',
            'market_position.in' => 'Noto\'g\'ri bozor pozitsiyasi',
        ];
    }
}
