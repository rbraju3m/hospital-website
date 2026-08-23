<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;

class TestimonialRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'patient_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'treatment' => ['nullable', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'quote' => ['required', 'string', 'max:2000'],
            'photo' => MediaLibrary::rules(),
            'photo_remove' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules(['patient_name', 'location', 'treatment', 'quote']));
    }
}
