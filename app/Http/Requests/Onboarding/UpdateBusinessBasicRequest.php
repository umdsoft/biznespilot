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
            'name' => 'required|string|max:255',
            'industry_id' => 'required|exists:industries,id',
            'sub_industry_id' => 'nullable|exists:industries,id',
            'business_type' => 'required|in:b2b,b2c,b2b2c,d2c',
            'business_model' => 'required|in:product,service,marketplace,subscription,hybrid',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Biznes nomi kiritilishi shart',
            'industry_id.required' => 'Soha tanlanishi shart',
            'industry_id.exists' => 'Noto\'g\'ri soha tanlandi',
            'business_type.required' => 'Biznes turi tanlanishi shart',
            'business_type.in' => 'Noto\'g\'ri biznes turi',
            'business_model.required' => 'Biznes modeli tanlanishi shart',
            'business_model.in' => 'Noto\'g\'ri biznes modeli',
        ];
    }
}
