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
 *
 * `create` is the same trick twice: the route name is also the label key, so
 * `admin.doctors.create` names both the screen and the words already on the
 * button that opens it ("Add doctor", "Write article", "Book for a patient").
 * That is a coincidence worth using and not worth trusting silently, so
 * PanelNavigationTest asserts both halves of it for every entry.
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
                ['key' => 'appointments', 'route' => 'admin.appointments.index', 'match' => 'admin.appointments.*', 'icon' => 'calendar-check', 'badge' => 'pending_appointments', 'create' => 'admin.appointments.create'],
                ['key' => 'messages', 'route' => 'admin.messages.index', 'match' => 'admin.messages.*', 'icon' => 'inbox', 'badge' => 'unread_messages'],
            ],
        ],
        [
            'heading' => 'content',
            'items' => [
                ['key' => 'departments', 'route' => 'admin.departments.index', 'match' => 'admin.departments.*', 'icon' => 'building', 'create' => 'admin.departments.create'],
                ['key' => 'doctors', 'route' => 'admin.doctors.index', 'match' => 'admin.doctors.*', 'icon' => 'stethoscope', 'create' => 'admin.doctors.create'],
                ['key' => 'services', 'route' => 'admin.services.index', 'match' => 'admin.services.*', 'icon' => 'activity', 'create' => 'admin.services.create'],
                ['key' => 'packages', 'route' => 'admin.packages.index', 'match' => 'admin.packages.*', 'icon' => 'package', 'create' => 'admin.packages.create'],
                ['key' => 'diagnostics', 'route' => 'admin.diagnostics.index', 'match' => 'admin.diagnostics.*', 'icon' => 'microscope', 'create' => 'admin.diagnostics.create'],
                ['key' => 'posts', 'route' => 'admin.posts.index', 'match' => 'admin.posts.*', 'icon' => 'newspaper', 'create' => 'admin.posts.create'],
                ['key' => 'gallery', 'route' => 'admin.gallery.index', 'match' => 'admin.gallery.*', 'icon' => 'image', 'create' => 'admin.gallery.create'],
                ['key' => 'testimonials', 'route' => 'admin.testimonials.index', 'match' => 'admin.testimonials.*', 'icon' => 'quote', 'create' => 'admin.testimonials.create'],
            ],
        ],
        [
            'heading' => 'portal',
            'items' => [
                ['key' => 'documents', 'route' => 'admin.documents.index', 'match' => 'admin.documents.*', 'icon' => 'file-text', 'create' => 'admin.documents.create'],
                ['key' => 'patients', 'route' => 'admin.patients.index', 'match' => 'admin.patients.*', 'icon' => 'user-round'],
            ],
        ],
        [
            'heading' => 'system',
            'items' => [
                ['key' => 'site_controls', 'route' => 'admin.site.edit', 'match' => 'admin.site.*', 'icon' => 'power'],
                ['key' => 'settings', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'sliders'],
                ['key' => 'users', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'create' => 'admin.users.create'],
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
        $gaps = TranslationGaps::counts();

        return array_map(fn (array $group) => [
            'heading' => $group['heading'],
            'label' => $group['heading'] ? __("admin.nav.group_{$group['heading']}") : null,
            'items' => array_map(fn (array $item) => self::resolve($item, $badges, $gaps), $group['items']),
        ], self::GROUPS);
    }

    /** The same menu flattened — what a search box wants. */
    public static function items(): array
    {
        return array_merge(...array_column(self::groups(), 'items'));
    }

    /**
     * The searchable list behind the Ctrl+K palette: every section, plus the
     * "add one" screen for the sections that have one.
     *
     * Takes the resolved groups rather than resolving them again — the badge
     * counts are two queries, and the sidebar has already paid for them.
     */
    public static function palette(?array $groups = null): array
    {
        $entries = [];

        foreach ($groups ?? self::groups() as $group) {
            foreach ($group['items'] as $item) {
                $entries[] = [
                    'kind' => 'page',
                    'label' => $item['label'],
                    'group' => $group['label'],
                    'url' => $item['url'],
                    'badge' => $item['badge'],
                    'gaps' => $item['gaps'],
                    // Resolved here, not in the browser: the plural form of a
                    // count is the translator's business, not JavaScript's.
                    'gaps_label' => $item['gaps']
                        ? trans_choice('admin.translation.gap', $item['gaps'])
                        : null,
                ];

                if ($item['create']) {
                    $entries[] = [
                        'kind' => 'create',
                        'label' => __($item['create']),
                        'group' => $item['label'],
                        'url' => route($item['create']),
                        'badge' => null,
                        'gaps' => null,
                        'gaps_label' => null,
                    ];
                }
            }
        }

        return $entries;
    }

    private static function resolve(array $item, array $badges, array $gaps): array
    {
        return [
            'key' => $item['key'],
            'label' => __("admin.nav.{$item['key']}"),
            'url' => route($item['route']),
            'icon' => $item['icon'],
            'active' => request()->routeIs($item['match']),
            'badge' => $badges[$item['badge'] ?? ''] ?? null,
            // Content still waiting on a translator. A different colour from
            // the badge above and never on the same row as one: work waiting
            // on the desk today and a sentence nobody has written yet are not
            // the same kind of news.
            'gaps' => $gaps[$item['key']] ?? null,
            'create' => $item['create'] ?? null,
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
