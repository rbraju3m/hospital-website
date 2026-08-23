<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Uploads for editorial imagery — doctor portraits, department and article
 * covers. Files live on the `public` disk under one folder per content type,
 * and the model column stores the disk-relative path ("doctors/xyz.webp").
 *
 * Render stored paths with the media_url() helper, never asset(): the disk
 * resolves through storage/app/public, which reaches the browser only via the
 * `php artisan storage:link` symlink.
 */
class MediaLibrary
{
    public const MAX_KILOBYTES = 4096;

    public const MIME_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

    /** The disk-relative path a fresh upload was stored at. */
    public function store(UploadedFile $file, string $folder): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = Str::limit($name ?: 'image', 60, '');

        return $file->storeAs(
            $folder,
            $name.'-'.Str::lower(Str::random(8)).'.'.$file->extension(),
            'public'
        );
    }

    /**
     * Swap one image for another, discarding whatever the column held before.
     *
     * Returns the new path, or null when the caller asked for removal. Pass the
     * previous value as $existing so the orphaned file does not linger.
     */
    public function replace(?UploadedFile $file, string $folder, ?string $existing, bool $remove = false): ?string
    {
        if ($file) {
            $this->delete($existing);

            return $this->store($file, $folder);
        }

        if ($remove) {
            $this->delete($existing);

            return null;
        }

        return $existing;
    }

    /**
     * Delete a stored file.
     *
     * Absolute URLs and anything outside the disk are left alone — the column
     * may legitimately hold a path this class never wrote.
     */
    public function delete(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /** The validation rules an image field should carry. */
    public static function rules(): array
    {
        return ['nullable', 'image', 'mimes:'.implode(',', self::MIME_TYPES), 'max:'.self::MAX_KILOBYTES];
    }
}
