<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ContactMessage;

/**
 * The staff panel's menu, in one place.
 *
 * The sidebar renders it and the quick-search palette searches it, so a link
 * added here appears in both. Two lists would drift the moment somebody adds a
 * section to the sidebar and forgets the palette — and a link that exists but
 * cannot be found is worse than one that does not exist at all.
 *
 * An item's `key` doubles as its label key (`admin.nav.<key>`) and a group's
 * heading as `admin.nav.group_<heading>`, so a section cannot be added with a
 * label in one locale and a raw key in the other — the localisation tests read
 * this registry and fail on a missing string.
 */
class PanelNavigation
{
    /**
     * Grouped so the front desk's daily work sits above the editorial tools it
     * rarely touches. `match` keeps the active state on nested routes too.
     */
    private const GROUPS = [
        [
            'heading' => null,
            'items' => [
                ['key' => 'dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'layout-dashboard'],
                ['key' => 'appointments', 'route' => 'admin.appointments.index', 'match' => 'admin.appointments.*', 'icon' => 'calendar-check', 'badge' => 'pending_appointments'],
                ['key' => 'messages', 'route' => 'admin.messages.index', 'match' => 'admin.messages.*', 'icon' => 'inbox', 'badge' => 'unread_messages'],
            ],
        ],
        [
            'heading' => 'content',
            'items' => [
                ['key' => 'departments', 'route' => 'admin.departments.index', 'match' => 'admin.departments.*', 'icon' => 'building'],
                ['key' => 'doctors', 'route' => 'admin.doctors.index', 'match' => 'admin.doctors.*', 'icon' => 'stethoscope'],
                ['key' => 'services', 'route' => 'admin.services.index', 'match' => 'admin.services.*', 'icon' => 'activity'],
                ['key' => 'packages', 'route' => 'admin.packages.index', 'match' => 'admin.packages.*', 'icon' => 'package'],
                ['key' => 'diagnostics', 'route' => 'admin.diagnostics.index', 'match' => 'admin.diagnostics.*', 'icon' => 'microscope'],
                ['key' => 'posts', 'route' => 'admin.posts.index', 'match' => 'admin.posts.*', 'icon' => 'newspaper'],
                ['key' => 'gallery', 'route' => 'admin.gallery.index', 'match' => 'admin.gallery.*', 'icon' => 'image'],
                ['key' => 'testimonials', 'route' => 'admin.testimonials.index', 'match' => 'admin.testimonials.*', 'icon' => 'quote'],
            ],
        ],
        [
            'heading' => 'portal',
            'items' => [
                ['key' => 'documents', 'route' => 'admin.documents.index', 'match' => 'admin.documents.*', 'icon' => 'file-text'],
                ['key' => 'patients', 'route' => 'admin.patients.index', 'match' => 'admin.patients.*', 'icon' => 'user-round'],
            ],
        ],
        [
            'heading' => 'system',
            'items' => [
                ['key' => 'site_controls', 'route' => 'admin.site.edit', 'match' => 'admin.site.*', 'icon' => 'power'],
                ['key' => 'settings', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'sliders'],
                ['key' => 'users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users'],
            ],
        ],
    ];

    /** The registry as declared — labels unresolved, no queries. */
    public static function registry(): array
    {
        return self::GROUPS;
    }

    /**
     * The menu, resolved for this request: labels translated, URLs generated,
     * the current section marked and the badge counts filled in.
     *
     * Counted once and handed to the layout, which passes it down to every
     * partial that renders the menu — see the composer in AppServiceProvider.
     */
    public static function groups(): array
    {
        $badges = self::badges();

        return array_map(fn (array $group) => [
            'heading' => $group['heading'],
            'label' => $group['heading'] ? __("admin.nav.group_{$group['heading']}") : null,
            'items' => array_map(fn (array $item) => self::resolve($item, $badges), $group['items']),
        ], self::GROUPS);
    }

    /** The same menu flattened — what a search box wants. */
    public static function items(): array
    {
        return array_merge(...array_column(self::groups(), 'items'));
    }

    private static function resolve(array $item, array $badges): array
    {
        return [
            'key' => $item['key'],
            'label' => __("admin.nav.{$item['key']}"),
            'url' => route($item['route']),
            'icon' => $item['icon'],
            'active' => request()->routeIs($item['match']),
            'badge' => $badges[$item['badge'] ?? ''] ?? null,
        ];
    }

    /**
     * Counts for the two things that go stale if nobody looks at them:
     * unanswered bookings and an unread inbox. Yesterday's pending bookings are
     * left out — the visit has already been missed, and a number that can only
     * grow stops being read.
     */
    private static function badges(): array
    {
        return [
            'pending_appointments' => Appointment::where('status', 'pending')
                ->whereDate('appointment_date', '>=', today())
                ->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
        ];
    }
}
