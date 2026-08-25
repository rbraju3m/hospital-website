<?php

namespace App\Support;

/**
 * Who can reach which part of the panel.
 *
 * Three roles, cut along the lines the menu already draws: the front desk's
 * daily work, the editorial tools, and the switches underneath both. An editor
 * has no business reading a patient's mobile number, and a receptionist has no
 * business taking the public site down.
 *
 * Two decisions hold this together:
 *
 * - **Administrator is a wildcard, not a list.** A section added to the panel
 *   next year is reachable by an administrator the moment it exists. A list
 *   would leave it locked to everybody, which is a bug nobody would look for.
 * - **Everyone else is an explicit list, so a new section is denied by
 *   default.** Forgetting to grant something is a support request; forgetting
 *   to deny it is a patient's medical record in front of the wrong person.
 *
 * The keys are `PanelNavigation`'s section keys, which are also
 * `ManagedLists`' — one vocabulary for "part of the panel", so a role, a menu
 * item and a drag-to-reorder endpoint cannot disagree about what `doctors`
 * means. A test asserts the three lists line up.
 */
class StaffRoles
{
    public const ADMINISTRATOR = 'administrator';
    public const FRONT_DESK = 'front_desk';
    public const EDITOR = 'editor';

    /** Sections each role may reach. Administrator is handled as a wildcard. */
    private const GRANTS = [
        self::FRONT_DESK => [
            'dashboard', 'appointments', 'messages', 'notifications', 'documents', 'patients',
        ],
        self::EDITOR => [
            'dashboard', 'slides', 'departments', 'doctors', 'services', 'packages',
            'diagnostics', 'posts', 'gallery', 'testimonials',
        ],
    ];

    /**
     * Route-name segments that belong to no section: signing in and out, the
     * dashboard, the palette's search endpoint, and the listing endpoints —
     * the last of those is gated by `ListController` against the list it was
     * asked for, because one route serves eight sections.
     */
    private const UNSECTIONED = ['login', 'logout', 'dashboard', 'search', 'lists'];

    /** Route segments whose section is not spelled the same way. */
    private const ALIASES = ['site' => 'site_controls'];

    /** @return list<string> */
    public static function all(): array
    {
        return [self::ADMINISTRATOR, self::FRONT_DESK, self::EDITOR];
    }

    public static function exists(?string $role): bool
    {
        return in_array($role, self::all(), true);
    }

    public static function label(string $role): string
    {
        return __("admin.roles.{$role}");
    }

    /** @return list<string> the sections this role may reach, wildcard aside */
    public static function sections(string $role): array
    {
        return self::GRANTS[$role] ?? [];
    }

    public static function grants(?string $role, string $section): bool
    {
        if ($role === self::ADMINISTRATOR) {
            return true;
        }

        return in_array($section, self::sections((string) $role), true);
    }

    /**
     * The section a route name belongs to, or null when it belongs to none.
     *
     * Derived from the route name rather than declared per route: a resource
     * added without a line here is denied to everyone but an administrator,
     * which is the safe direction to fail in.
     */
    public static function sectionForRoute(?string $name): ?string
    {
        if ($name === null || ! str_starts_with($name, 'admin.')) {
            return null;
        }

        $segment = explode('.', $name)[1] ?? '';

        if (in_array($segment, self::UNSECTIONED, true)) {
            return null;
        }

        return self::ALIASES[$segment] ?? $segment;
    }
}
