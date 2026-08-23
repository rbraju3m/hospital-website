<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use Illuminate\Validation\Rule;

class ServiceRequest extends AdminFormRequest
{
    public const CATEGORIES = ['clinical', 'diagnostic', 'support', 'patient-care'];

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('services', 'slug')->ignore($this->route('service'))],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'icon' => ['required', 'string', 'max:64'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'image' => MediaLibrary::rules(),
            'image_remove' => ['nullable', 'boolean'],
            'highlights' => ['nullable', 'string', 'max:2000'],
            'is_247' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules(['name', 'summary', 'description', 'highlights']));
    }
}
