<?php

namespace App\Http\Middleware;

use App\Support\SiteFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes a route whose Site-controls switch is off.
 *
 * Hiding the navigation link is only half the job: the URL is in search
 * results, in somebody's bookmarks and in the confirmation email they were
 * sent last month. A switched-off area answers 404 — the page is not there
 * rather than temporarily broken — so the link stops being an entrance.
 *
 * Staff signed in to the panel pass through, so an area can be prepared and
 * previewed before it is turned on for visitors.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        foreach ($features as $feature) {
            if (SiteFeatures::enabled($feature)) {
                continue;
            }

            if ($request->user('web')) {
                continue;
            }

            abort(404);
        }

        return $next($request);
    }
}
