<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use Illuminate\Validation\Rule;

class DoctorRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('doctors', 'slug')->ignore($this->route('doctor'))],
            'designation' => ['nullable', 'string', 'max:255'],
            // Post-nominals stay in Latin script in both locales, so this one
            // field is deliberately not translatable.
            'qualifications' => ['nullable', 'string', 'max:255'],
            'speciality' => ['nullable', 'string', 'max:255'],
            'expertise' => ['nullable', 'string', 'max:2000'],
            'photo' => MediaLibrary::rules(),
            'photo_remove' => ['nullable', 'boolean'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'about' => ['nullable', 'string', 'max:20000'],
            'languages' => ['nullable', 'string', 'max:500'],
            'chamber' => ['nullable', 'string', 'max:255'],
            'consultation_fee' => ['required', 'integer', 'min:0', 'max:1000000'],
            'follow_up_fee' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'accepts_online_appointment' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules([
            'name', 'designation', 'speciality', 'expertise', 'about', 'languages', 'chamber',
        ]));
    }
}
