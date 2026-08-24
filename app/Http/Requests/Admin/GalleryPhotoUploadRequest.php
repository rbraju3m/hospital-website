<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibrary;

/**
 * Adding pictures to an album.
 *
 * Multiple files in one go, because the alternative is a member of staff
 * repeating a single-file form twenty times for one theatre visit. The cap is
 * per submission, not per album.
 */
class GalleryPhotoUploadRequest extends AdminFormRequest
{
    public const MAX_PER_UPLOAD = 20;

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'max:'.self::MAX_PER_UPLOAD],
            'photos.*' => [
                'required', 'image',
                'mimes:'.implode(',', MediaLibrary::MIME_TYPES),
                'max:'.MediaLibrary::MAX_KILOBYTES,
            ],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'photos' => __('admin.fields.photos'),
            'photos.*' => __('admin.fields.photos'),
        ]);
    }
}
