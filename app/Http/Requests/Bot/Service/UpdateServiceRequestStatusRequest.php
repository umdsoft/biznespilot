<?php

namespace App\Http\Requests\Bot\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:assigned,en_route,arrived,diagnosing,in_progress,completed,cancelled'],
            'cancel_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $serviceRequest = $this->route('serviceRequest');
            if ($serviceRequest && ! $serviceRequest->canTransitionTo($this->input('status'))) {
                $validator->errors()->add('status', "'{$serviceRequest->status}' dan '{$this->input('status')}' ga o'tish mumkin emas.");
            }
        });
    }
}
