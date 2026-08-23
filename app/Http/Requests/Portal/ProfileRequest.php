<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('patient')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            // Changing a password requires proving you know the current one:
            // a borrowed unlocked phone should not become a stolen account.
            'current_password' => ['nullable', 'required_with:password', 'current_password:patient'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('portal.fields.name'),
            'email' => __('portal.fields.email'),
            'date_of_birth' => __('portal.fields.date_of_birth'),
            'gender' => __('portal.fields.gender'),
            'current_password' => __('portal.fields.current_password'),
            'password' => __('portal.fields.password'),
        ];
    }
}
