<?php

namespace App\Http\Requests\Bot\Delivery;

use App\Models\Bot\Delivery\DeliveryOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:confirmed,preparing,ready,delivering,delivered,cancelled'],
            'cancel_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if ($order && ! $order->canTransitionTo($this->input('status'))) {
                $validator->errors()->add('status', "'{$order->status}' dan '{$this->input('status')}' ga o'tish mumkin emas.");
            }
        });
    }
}
