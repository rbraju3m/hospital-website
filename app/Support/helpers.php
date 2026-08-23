<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /** Read a site setting (hotline numbers, address, social links, …). */
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
