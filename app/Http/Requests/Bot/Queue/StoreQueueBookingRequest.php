<?php

namespace App\Http\Requests\Bot\Queue;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueBookingRequest extends FormRequest
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
            'service_id' => ['required', 'uuid', 'exists:queue_services,id'],
            'branch_id' => ['required', 'uuid', 'exists:queue_branches,id'],
            'specialist_id' => ['nullable', 'uuid'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'payment_method' => ['nullable', 'in:cash,card,click,payme'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
