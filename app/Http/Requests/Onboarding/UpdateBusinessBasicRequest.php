<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessBasicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:retail,wholesale,ecommerce,food_service,manufacturing,construction,it_services,education,healthcare,beauty_wellness,real_estate,transportation,agriculture,tourism,finance,consulting,marketing_agency,media,fitness,automotive,textile,furniture,electronics,cleaning,event_services,legal,other',
            'business_type' => 'nullable|in:b2b,b2c,b2b2c,d2c',
            'business_model' => 'nullable|in:product,service,marketplace,subscription,hybrid',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'Noto\'g\'ri kategoriya tanlandi',
            'business_type.in' => 'Noto\'g\'ri biznes turi',
            'business_model.in' => 'Noto\'g\'ri biznes modeli',
        ];
    }
}
