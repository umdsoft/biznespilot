<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'login' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'login'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNotNull('email'),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->whereNotNull('phone'),
            ],
            // SECURITY: Strong password policy
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()      // At least one uppercase and one lowercase
                    ->numbers()        // At least one number
                    ->symbols()        // At least one special character
                    ->uncompromised(), // Check against breached password databases
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Login is required.',
            'login.unique' => 'This login is already taken.',
            'login.alpha_dash' => 'Login can only contain letters, numbers, dashes and underscores.',
            'email.unique' => 'This email is already registered.',
            'phone.unique' => 'This phone number is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Parol kamida 8 ta belgidan iborat bo\'lishi kerak.',
            'password.mixed' => 'Parol katta va kichik harflarni o\'z ichiga olishi kerak.',
            'password.numbers' => 'Parol kamida bitta raqamni o\'z ichiga olishi kerak.',
            'password.symbols' => 'Parol kamida bitta maxsus belgini o\'z ichiga olishi kerak.',
            'password.uncompromised' => 'Bu parol ma\'lumotlar bazalarida topilgan. Iltimos, boshqa parol tanlang.',
        ];
    }
}
