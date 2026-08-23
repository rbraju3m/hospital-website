<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use Illuminate\Validation\Rule;

class HealthPackageRequest extends AdminFormRequest
{
    public const CATEGORIES = ['executive', 'cardiac', 'diabetes', 'women', 'men', 'senior', 'basic'];

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('health_packages', 'slug')->ignore($this->route('healthPackage'))],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:20000'],
            // Whole taka, no minor units — the site renders ৳{{ number_format() }}.
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'discount_price' => ['nullable', 'integer', 'min:0', 'max:10000000', 'lt:price'],
            'tests' => ['nullable', 'string', 'max:4000'],
            'duration' => ['nullable', 'string', 'max:255'],
            'suitable_for' => ['nullable', 'string', 'max:255'],
            'image' => MediaLibrary::rules(),
            'image_remove' => ['nullable', 'boolean'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules(['name', 'summary', 'description', 'tests', 'duration', 'suitable_for']));
    }
}
