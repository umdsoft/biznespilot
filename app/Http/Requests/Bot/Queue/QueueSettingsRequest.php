<?php

namespace App\Http\Requests\Bot\Queue;

use Illuminate\Foundation\Http\FormRequest;

class QueueSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allow_same_day' => ['nullable', 'boolean'],
            'advance_booking_days' => ['nullable', 'integer', 'min:1'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:5'],
            'auto_cancel_minutes' => ['nullable', 'integer', 'min:5'],
            'require_phone' => ['nullable', 'boolean'],
            'allow_specialist_choice' => ['nullable', 'boolean'],
            'show_queue_position' => ['nullable', 'boolean'],
        ];
    }
}
