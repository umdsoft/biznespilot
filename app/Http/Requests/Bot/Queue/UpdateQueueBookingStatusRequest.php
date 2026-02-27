<?php

namespace App\Http\Requests\Bot\Queue;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:confirmed,in_progress,completed,cancelled,no_show'],
            'cancel_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $booking = $this->route('booking');
            if ($booking && ! $booking->canTransitionTo($this->input('status'))) {
                $validator->errors()->add(
                    'status',
                    "'{$booking->status}' dan '{$this->input('status')}' ga o'tish mumkin emas."
                );
            }
        });
    }
}
