<?php

namespace App\Http\Requests\Admin;

/**
 * Editing one picture that is already in an album: its caption and where it
 * sits. Replacing the file itself is a delete and a fresh upload — a gallery
 * has no URL per photograph for anything to be pointing at.
 */
class GalleryPhotoRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules(['caption']));
    }
}
