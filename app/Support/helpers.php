<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /** Read a site setting (hotline numbers, address, social links, …) in the active locale. */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::config($key, $default);
    }
}

if (! function_exists('inline_markup')) {
    /**
     * Escape editorial text, then re-enable the small set of inline markers we
     * allow in article bodies (**bold** only). Everything else stays literal.
     */
    function inline_markup(string $text): string
    {
        return preg_replace(
            '/\*\*(.+?)\*\*/',
            '<strong class="font-semibold text-navy-900">$1</strong>',
            e($text)
        );
    }
}

if (! function_exists('category_label')) {
    /**
     * Human label for a category slug stored on a model.
     *
     * Falls back to a title-cased version of the slug so a category added to
     * the database before its translation exists still reads sensibly.
     */
    function category_label(string $domain, ?string $slug): string
    {
        if (blank($slug)) {
            return '';
        }

        $key = "{$domain}.categories.{$slug}";
        $label = __($key);

        return $label === $key ? (string) str($slug)->headline() : $label;
    }
}

if (! function_exists('media_url')) {
    /**
     * Browser URL for an uploaded image path.
     *
     * Stored paths are relative to the `public` disk ("doctors/x.webp"), which
     * only becomes reachable through the storage:link symlink — so they cannot
     * go through asset(). Absolute URLs and root-relative paths pass straight
     * through, letting a column hold an external image if it ever needs to.
     */
    function media_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Split a textarea into a list, one item per line.
     *
     * The admin edits JSON list columns (highlights, tests, expertise) as plain
     * lines because a repeatable-input widget buys nothing for flat strings.
     */
    function lines_to_array(?string $text): array
    {
        if (blank($text)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}

if (! function_exists('array_to_lines')) {
    /** The inverse of lines_to_array(), for rendering a list back into a textarea. */
    function array_to_lines(mixed $items): string
    {
        if (blank($items)) {
            return '';
        }

        return implode("\n", is_array($items) ? $items : [$items]);
    }
}

if (! function_exists('translation_locales')) {
    /**
     * Locales stored in the `translations` JSON column — every available locale
     * except the fallback, which lives in the ordinary columns.
     *
     * @return list<string>
     */
    function translation_locales(): array
    {
        return array_values(array_diff(
            array_keys(config('app.available_locales', [])),
            [config('app.fallback_locale')]
        ));
    }
}

if (! function_exists('feature')) {
    /**
     * Is a site feature switched on? (Site controls, in the staff panel.)
     *
     * Reads through the settings cache, so a template may guard on this as
     * freely as it reads any other setting. Unknown keys are on — see
     * App\Support\SiteFeatures::enabled().
     */
    function feature(string $key): bool
    {
        return App\Support\SiteFeatures::enabled($key);
    }
}

if (! function_exists('demo_image')) {
    /**
     * Stand-in photograph for a content type, stable for the given seed.
     *
     * Returns null when staff have switched the demo imagery off, which is what
     * lets every call site fall back to the icon or initials treatment without
     * a second condition.
     */
    function demo_image(string $set, string|int|null $seed = null, string $group = ''): ?string
    {
        if (! feature('behaviour_demo_images')) {
            return null;
        }

        return App\Support\DemoImages::pick($set, $seed, $group);
    }
}

if (! function_exists('image_url')) {
    /**
     * The image to render in a slot: the upload if there is one, otherwise a
     * demo photograph, otherwise nothing.
     *
     * Every image position on the public site goes through this, so "no photo
     * on file" is answered in exactly one place.
     */
    function image_url(?string $path, ?string $set = null, string|int|null $seed = null, string $group = ''): ?string
    {
        return media_url($path) ?? ($set ? demo_image($set, $seed, $group) : null);
    }
}

if (! function_exists('is_demo_image')) {
    /** True when a slot is showing stand-in photography rather than an upload. */
    function is_demo_image(?string $path): bool
    {
        return blank($path);
    }
}

if (! function_exists('doctor_photo')) {
    /**
     * The portrait to show for a consultant.
     *
     * Their own photograph if one has been uploaded; otherwise a stand-in that
     * matches the gender recorded on them, stable for the life of the row.
     */
    function doctor_photo(App\Models\Doctor $doctor): ?string
    {
        $uploaded = media_url($doctor->untranslated('photo'));

        if ($uploaded) {
            return $uploaded;
        }

        if (! feature('behaviour_demo_images')) {
            return null;
        }

        return App\Support\DemoImages::portrait($doctor->gender, $doctor->id);
    }
}
