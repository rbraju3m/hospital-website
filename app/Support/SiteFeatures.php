<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The switchboard behind the panel's Site controls page.
 *
 * Every switch is a `settings` row in the `features` group holding "1" or "0",
 * so turning one off is a cache-busting save rather than a deploy. Keys are
 * declared here rather than in the database: the registry is what the panel
 * renders, what the middleware validates against, and what supplies a default
 * when a row does not exist yet — a fresh database, or a key added after the
 * seeder last ran, therefore behaves as "on" instead of silently hiding a page.
 *
 * Defaults are all `true` on purpose. Nothing about the site changes until a
 * staff member decides it should.
 */
class SiteFeatures
{
    /** The `group` column every switch is stored under. */
    public const GROUP = 'features';

    /** Stored keys are prefixed so they cannot collide with a content setting. */
    public const PREFIX = 'feat_';

    /**
     * Switch groups, in the order the panel renders them.
     *
     * @return array<string, array<string, bool>>
     */
    public static function groups(): array
    {
        return [
            // Whole areas of the site. Turning one off hides every link to it
            // *and* closes the route — see EnsureFeatureEnabled.
            'areas' => [
                'area_departments' => true,
                'area_doctors' => true,
                'area_services' => true,
                'area_packages' => true,
                'area_diagnostics' => true,
                'area_posts' => true,
                'area_gallery' => true,
                'area_portal' => true,
                'area_about' => true,
                'area_international' => true,
                'area_emergency' => true,
                'area_contact' => true,
                'area_appointment' => true,
            ],

            // Bands on the home page, top to bottom.
            'home' => [
                'home_quick_actions' => true,
                'home_booker' => true,
                'home_stats' => true,
                'home_departments' => true,
                'home_doctors' => true,
                'home_services' => true,
                'home_why' => true,
                'home_packages' => true,
                'home_testimonials' => true,
                'home_posts' => true,
                'home_gallery' => true,
                'home_visit' => true,
            ],

            // Header, footer and the persistent affordances around the page.
            'chrome' => [
                'chrome_topbar' => true,
                'chrome_mega_menu' => true,
                'chrome_header_search' => true,
                'chrome_header_book' => true,
                'chrome_locale_switcher' => true,
                'chrome_footer_social' => true,
                'chrome_footer_departments' => true,
                'chrome_mobile_bar' => true,
                'chrome_back_to_top' => true,
                'chrome_scroll_progress' => true,
            ],

            // What visitors are allowed to do, and how the site presents itself.
            'behaviour' => [
                'behaviour_online_booking' => true,
                'behaviour_contact_form' => true,
                'behaviour_test_request' => true,
                'behaviour_portal_registration' => true,
                'behaviour_portal_changes' => true,
                'behaviour_online_payment' => true,
                'behaviour_demo_images' => true,
                'behaviour_animations' => true,
                'behaviour_maintenance' => false,
            ],
        ];
    }

    /**
     * Flat key => default map.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        static $flat = null;

        return $flat ??= array_merge(...array_values(static::groups()));
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::defaults());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, static::defaults());
    }

    /** The `settings.key` a switch is stored under. */
    public static function settingKey(string $key): string
    {
        return static::PREFIX.$key;
    }

    /**
     * Is this switch on?
     *
     * Reads through the settings cache, so this costs nothing per call beyond
     * the map every page already loads. An unknown key is "on": a template
     * guarding on a typo should fail visible rather than blank the section.
     */
    public static function enabled(string $key): bool
    {
        $default = static::defaults()[$key] ?? true;
        $stored = Setting::config(static::settingKey($key));

        if ($stored === null || $stored === '') {
            return $default;
        }

        return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Current state of every switch, for the panel's form.
     *
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $state = [];

        foreach (static::keys() as $key) {
            $state[$key] = static::enabled($key);
        }

        return $state;
    }

    /**
     * Persist the submitted state.
     *
     * The form posts only the switches that are on (an unchecked checkbox posts
     * nothing), so every known key is written explicitly — otherwise turning a
     * switch off would leave the previous "1" in place.
     *
     * @param  array<string, mixed>  $submitted
     */
    public static function store(array $submitted): void
    {
        foreach (static::keys() as $key) {
            Setting::updateOrCreate(
                ['key' => static::settingKey($key)],
                [
                    'value' => filter_var($submitted[$key] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                    'group' => static::GROUP,
                ],
            );
        }

        // Each save busts the cache already; this is belt and braces for the
        // case where nothing changed and no model event fired.
        Setting::flushCache();
    }
}
