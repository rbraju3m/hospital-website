<?php

namespace App\Http\Requests\Portal;

use App\Sms\PhoneNumber;
use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The mobile is normalised before validation so that 01712345678 and
     * +8801712345678 collide on the unique index instead of becoming two
     * accounts looking at the same appointments.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['phone_national' => PhoneNumber::national($this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'max:32', Rules::BD_MOBILE],
            'phone_national' => ['required', Rule::unique('patients', 'phone')],
            // Optional, as everywhere else on this site. Recovery goes by SMS.
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('forms.phone_format'),
            'phone_national.unique' => __('portal.register.phone_taken'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('portal.fields.name'),
            'phone' => __('portal.fields.phone'),
            'phone_national' => __('portal.fields.phone'),
            'email' => __('portal.fields.email'),
            'password' => __('portal.fields.password'),
        ];
    }
}
