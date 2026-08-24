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
        return ['nullable', 'image', 'mimes:'.implode(',', self::MIME_TYPES), 'max:'.self::maxKilobytes()];
    }

    /**
     * What an upload may actually weigh, here, on this machine.
     *
     * PHP rejects an oversized file before Laravel ever sees it: the file
     * silently does not arrive, and the form comes back saying the field is
     * required. Worse, a *batch* over `post_max_size` empties the whole request
     * — including the CSRF token — which surfaces as "page expired" rather than
     * as anything to do with photographs. So the app validates against the real
     * ceiling and says what it is, rather than promising 4 MB and failing at 2.
     */
    public static function maxKilobytes(): int
    {
        return (int) min(
            self::MAX_KILOBYTES,
            self::iniKilobytes('upload_max_filesize') ?: self::MAX_KILOBYTES,
            self::iniKilobytes('post_max_size') ?: self::MAX_KILOBYTES,
        );
    }

    /**
     * How many files may be sent in one request.
     *
     * Bounded by `max_file_uploads` only. Dividing `post_max_size` by the
     * per-file ceiling was the obvious thing to do and was wrong: it prices
     * every picture at the worst case, so a 2 MB limit and an 8 MB post allowed
     * three photographs even when the browser had already shrunk them to 200 KB
     * each. Total weight is PHP's own business, and a request that does overrun
     * it now comes back as a sentence rather than as an expired page.
     */
    public static function maxFilesPerRequest(int $ceiling): int
    {
        $files = (int) ini_get('max_file_uploads');

        return (int) max(1, min($ceiling, $files ?: $ceiling));
    }

    /** An ini shorthand size ("2M", "512K", "1G") in kilobytes. */
    private static function iniKilobytes(string $directive): int
    {
        $value = trim((string) ini_get($directive));

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024,
            'm' => $number * 1024,
            'k' => $number,
            default => $number / 1024,
        };
    }
}
