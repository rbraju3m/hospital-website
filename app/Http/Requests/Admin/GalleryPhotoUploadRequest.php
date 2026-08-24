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
    /** What the form offers, before this machine's own limits are applied. */
    public const MAX_PER_UPLOAD = 20;

    /**
     * The real cap: PHP's `max_file_uploads` and `post_max_size` both bite
     * before Laravel does, and a batch that overruns the second one arrives
     * with no CSRF token and reads as an expired page.
     */
    public static function batchSize(): int
    {
        return MediaLibrary::maxFilesPerRequest(self::MAX_PER_UPLOAD);
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'max:'.self::batchSize()],
            'photos.*' => [
                'required', 'image',
                'mimes:'.implode(',', MediaLibrary::MIME_TYPES),
                'max:'.MediaLibrary::maxKilobytes(),
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
