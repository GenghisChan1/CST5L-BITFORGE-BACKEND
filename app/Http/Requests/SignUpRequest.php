<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|unique:userstable,username|max:255',
            'email' => 'required|email|unique:userstable,email',
            'password' => [
                'required',
                Password::min(8),
            ],
            'phone_number' => ['required', 'regex:/^09\d{9}$/'],
            'street' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'postal_code' => 'required|digits_between:4,10',
        ];
    }
}
