<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use Illuminate\Validation\Rule;

class GalleryAlbumRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('gallery_albums', 'slug')->ignore($this->route('album'))],
            'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'image' => MediaLibrary::rules(),
            'image_remove' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ], $this->translationRules(['title', 'summary', 'description']));
    }
}
