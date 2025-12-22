<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_size' => 'nullable|in:1,2-5,6-10,11-25,26-50,50+',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'business_stage' => 'nullable|in:idea,startup,growth,established,scaling',
            'founding_date' => 'nullable|date',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'team_size.in' => 'Noto\'g\'ri jamoa hajmi',
            'business_stage.in' => 'Noto\'g\'ri biznes bosqichi',
            'website.url' => 'Noto\'g\'ri web-sayt manzili',
            'email.email' => 'Noto\'g\'ri email manzili',
        ];
    }
}
