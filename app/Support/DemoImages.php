<?php

namespace App\Support;

/**
 * Stand-in photography for content nobody has uploaded a picture for yet.
 *
 * The site is image-led — a consultant card, a department header and an article
 * cover all read as broken without one — but a hospital rarely has its own
 * photography ready on day one. Rather than leave those slots empty, every
 * image position falls back to a demo photo from `public/images/demo/`.
 *
 * The pick is **deterministic**: the same doctor gets the same face on every
 * page and on every request, because a portrait that reshuffles between the
 * listing and the profile reads as a bug. Seeds are content slugs, so the
 * mapping only changes if the slug does.
 *
 * Uploading a real image always wins — see image_url(). Staff can switch the
 * whole fallback off from Site controls, which returns the site to the plain
 * initials-and-icon treatment it had before.
 */
class DemoImages
{
    /**
     * How many files exist per set. Files are named `<set>-01.jpg` upward in
     * `public/images/demo/<set>/`; the count is declared rather than globbed so
     * that rendering a page never touches the filesystem.
     */
    private const SETS = [
        'doctor' => 8,    // 640×720 clinical portraits
        'cover' => 22,    // 1000×640 wards, theatres, equipment, consultations
        'hero' => 3,      // 1800×1000 wide banners
    ];

    /**
     * Which portraits in the doctor set read as which gender.
     *
     * A consultant listed as Dr. Sadia Afrin Rupa above a photograph of a man
     * reads as carelessness rather than as a placeholder, so the stand-in
     * follows the `gender` recorded on the doctor. Left unset, the pick comes
     * from the whole set — a guess, but a visible and correctable one, and it
     * stops mattering the moment somebody uploads a real photograph.
     */
    private const PORTRAIT_POOLS = [
        'female' => [2, 4, 5],
        'male' => [1, 3, 6, 7, 8],
    ];

    /** Named one-off images that are not part of a numbered set. */
    private const SINGLES = [
        'about' => '/images/demo/hero/about.jpg',
    ];

    /**
     * A demo image path for a content type, stable for the given seed.
     *
     * A **numeric** seed (a row id) walks the set one image at a time, so the
     * first dozen consultants get a dozen different faces — hashing a slug
     * instead scatters, and scattering means the same portrait twice in one
     * row of four, which reads as a mistake rather than as a placeholder.
     *
     * `$group` offsets that walk per content type, so a department and an
     * article that happen to share an id do not share a photograph.
     *
     * @param  string  $set  one of the keys in SETS, or a name from SINGLES
     * @param  string|int|null  $seed  a row id (walks) or any string (hashes)
     * @param  string  $group  namespace, so two content types do not align
     */
    public static function pick(string $set, string|int|null $seed = null, string $group = ''): ?string
    {
        if (isset(self::SINGLES[$set])) {
            return self::SINGLES[$set];
        }

        $count = self::SETS[$set] ?? null;

        if ($count === null) {
            return null;
        }

        $offset = $group === '' ? 0 : crc32($group);

        $index = match (true) {
            $seed === null => $offset,
            is_int($seed) || ctype_digit((string) $seed) => (int) $seed + $offset,
            default => crc32((string) $seed) + $offset,
        } % $count;

        return sprintf('/images/demo/%s/%s-%02d.jpg', $set, $set, $index + 1);
    }

    /**
     * A stand-in portrait for a consultant, matched to their recorded gender.
     *
     * Walks its pool by row id for the same reason pick() does: no two of the
     * consultants on one screen should be wearing the same face.
     */
    public static function portrait(?string $gender, string|int|null $seed = null): string
    {
        $pool = self::PORTRAIT_POOLS[$gender] ?? array_merge(...array_values(self::PORTRAIT_POOLS));
        sort($pool);

        $index = match (true) {
            $seed === null => 0,
            is_int($seed) || ctype_digit((string) $seed) => (int) $seed,
            default => crc32((string) $seed),
        } % count($pool);

        return sprintf('/images/demo/doctor/doctor-%02d.jpg', $pool[$index]);
    }

    /**
     * Every image in a set, in order — for a gallery that wants the lot.
     *
     * @return list<string>
     */
    public static function set(string $set): array
    {
        $count = self::SETS[$set] ?? 0;

        return array_map(
            fn (int $i) => sprintf('/images/demo/%s/%s-%02d.jpg', $set, $set, $i + 1),
            range(0, max($count - 1, 0)),
        );
    }
}
