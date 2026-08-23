<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;
use Illuminate\Validation\Rule;

class PostRequest extends AdminFormRequest
{
    public const CATEGORIES = ['health-tips', 'news', 'events', 'achievements'];

    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:60000'],
            'image' => MediaLibrary::rules(),
            'image_remove' => ['nullable', 'boolean'],
            'author' => ['nullable', 'string', 'max:255'],
            'read_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ], $this->translationRules(['title', 'excerpt', 'body', 'author']));
    }
}
