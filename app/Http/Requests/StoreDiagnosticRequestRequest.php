<?php

namespace App\Http\Requests;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Call me back about this test."
 *
 * Deliberately short. Nothing is scheduled and nothing is charged — this
 * lands in the same inbox as the contact form, and the desk takes it from
 * there. Asking for more than a name and a number would cost conversions for
 * information nobody acts on.
 */
class StoreDiagnosticRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'max:32', Rules::BD_MOBILE],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('forms.phone_format')];
    }

    public function attributes(): array
    {
        return [
            'name' => __('diagnostics.request.name'),
            'phone' => __('diagnostics.request.phone'),
            'email' => __('diagnostics.request.email'),
            'notes' => __('diagnostics.request.notes'),
        ];
    }
}
