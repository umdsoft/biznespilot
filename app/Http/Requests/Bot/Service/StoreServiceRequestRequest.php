<?php

namespace App\Http\Requests\Bot\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'telegram_user_id' => ['required', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'category_id' => ['required', 'uuid', 'exists:service_categories,id'],
            'service_type_id' => ['required', 'uuid', 'exists:service_types,id'],
            'master_id' => ['nullable', 'uuid'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:500'],
            'address' => ['required', 'string'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time_slot' => ['nullable', 'in:morning,afternoon,evening,anytime'],
            'payment_method' => ['nullable', 'in:cash,card,click,payme'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
