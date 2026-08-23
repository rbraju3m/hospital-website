<?php

namespace App\Http\Requests\Admin;

use App\Models\PatientDocument;
use App\Support\Rules;
use Illuminate\Validation\Rule;

class PatientDocumentRequest extends AdminFormRequest
{
    public const MAX_KILOBYTES = 10240;

    public const MIME_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];

    public function rules(): array
    {
        return [
            // Addressed to a number, not an account: a report exists before the
            // patient gets round to registering.
            'phone' => ['required', 'string', 'max:32', Rules::BD_MOBILE],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(PatientDocument::CATEGORIES)],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => [
                $this->route('document') ? 'nullable' : 'required',
                'file', 'mimes:'.implode(',', self::MIME_TYPES), 'max:'.self::MAX_KILOBYTES,
            ],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('forms.phone_format')];
    }

    public function attributes(): array
    {
        return (array) __('admin.fields');
    }
}
