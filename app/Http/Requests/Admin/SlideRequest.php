<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use App\Support\Rules;

class SlideRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            // A sentence, not a paragraph: this is read in the four seconds
            // before the slide moves on.
            'subtitle' => ['nullable', 'string', 'max:400'],
            'image' => MediaLibrary::rules(),
            'image_remove' => ['nullable', 'boolean'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255', Rules::LINK],
            'cta_secondary_label' => ['nullable', 'string', 'max:60'],
            'cta_secondary_url' => ['nullable', 'string', 'max:255', Rules::LINK],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules(['eyebrow', 'title', 'subtitle', 'cta_label', 'cta_secondary_label']));
    }
}
