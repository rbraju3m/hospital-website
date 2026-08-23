<?php

namespace App\Http\Requests\Portal;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32', Rules::BD_MOBILE],
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('forms.phone_format')];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('portal.fields.phone'),
            'code' => __('portal.fields.code'),
            'password' => __('portal.fields.password'),
        ];
    }
}
