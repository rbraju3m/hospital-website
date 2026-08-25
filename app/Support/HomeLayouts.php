<?php

namespace App\Support;

/**
 * Which of the home page's layouts is on air.
 *
 * A layout decides what the top of the page looks like and the order the bands
 * come in. It never decides what a band *says* — every one of them is the same
 * partial under `pages/home/bands/`, shared by all three, so a change to the
 * doctors strip lands on whichever layout is showing rather than on one of
 * them and not the others.
 *
 * Stored as a `settings` row rather than a Site-controls switch because it is a
 * choice of one from several, not an on/off. Everything else about it follows
 * the same rule as `SiteFeatures`: **the registry here is the source of truth,
 * not the database.** A key that is missing, misspelled, or names a layout that
 * has since been deleted falls back to `classic` — the alternative is a blank
 * home page, which is the one page on the site that must always render.
 */
class HomeLayouts
{
    public const SETTING = 'home_layout';

    public const DEFAULT = 'classic';

    /** In the order they are offered in the panel. */
    private const LAYOUTS = ['classic', 'slider', 'compact'];

    /** @return list<string> */
    public static function all(): array
    {
        return self::LAYOUTS;
    }

    public static function exists(?string $layout): bool
    {
        return in_array($layout, self::LAYOUTS, true);
    }

    public static function current(): string
    {
        $stored = setting(self::SETTING);

        return self::exists($stored) ? $stored : self::DEFAULT;
    }

    public static function view(?string $layout = null): string
    {
        $layout ??= self::current();

        return 'pages.home.'.(self::exists($layout) ? $layout : self::DEFAULT);
    }

    public static function label(string $layout): string
    {
        return __("admin.home_layouts.{$layout}");
    }

    public static function description(string $layout): string
    {
        return __("admin.home_layouts.{$layout}_help");
    }
}
