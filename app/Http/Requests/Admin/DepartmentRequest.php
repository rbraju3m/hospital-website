<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use Illuminate\Validation\Rule;

class DepartmentRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('departments', 'slug')->ignore($this->route('department'))],
            'tagline' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:64'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'image' => MediaLibrary::rules(),
            'image_remove' => ['nullable', 'boolean'],
            'highlights' => ['nullable', 'string', 'max:2000'],
            'treatments' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_centre_of_excellence' => ['boolean'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ], $this->translationRules([
            'name', 'tagline', 'summary', 'description', 'highlights',
            'treatments', 'location', 'meta_title', 'meta_description',
        ]));
    }
}
