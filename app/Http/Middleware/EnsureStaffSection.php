<?php

namespace App\Http\Middleware;

use App\Support\StaffRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes a panel route to a staff member whose role does not include it.
 *
 * Applied once to the whole `/admin` group rather than per route, and the
 * section is read off the route name: a resource added without a thought for
 * roles is closed to everyone but an administrator, which is the safe
 * direction to fail in. Compare `feature:<key>`, which has to be remembered
 * per route — that one is guarding a page from the public, and being wrong
 * shows a visitor a page. Being wrong here shows a receptionist a medical
 * record.
 *
 * It answers **403, not 404**. The section exists and their colleague uses it
 * every day; pretending otherwise would have them raising a bug rather than
 * asking for access.
 */
class EnsureStaffSection
{
    public function handle(Request $request, Closure $next): Response
    {
        $section = StaffRoles::sectionForRoute($request->route()?->getName());

        if ($section !== null && ! $request->user('web')?->canReach($section)) {
            abort(403, __('admin.roles.denied'));
        }

        return $next($request);
    }
}
