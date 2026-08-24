<?php

namespace App\Http\Requests\Admin;

/**
 * One photograph's caption, in whichever locale is being typed into. Saved as
 * it is typed, so this is the smallest write in the panel.
 */
class GalleryPhotoRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'caption' => ['nullable', 'string', 'max:255'],
        ], $this->translationRules(['caption']));
    }
}
