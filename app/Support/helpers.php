<?php

use App\Models\Setting;

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
