<?php

namespace App\Http\Middleware;

use App\Support\SiteFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public site's "closed for maintenance" switch, set from Site controls.
 *
 * Deliberately narrower than `artisan down`: the staff panel, the patient
 * portal and the payment callbacks stay up, because the work that takes the
 * site offline is usually work being done *in* the panel. Staff signed in to
 * the panel keep browsing the live site so they can check it before reopening.
 *
 * The notice answers 503 with a Retry-After, so search engines treat it as
 * temporary rather than delisting pages, and it carries the hotline and
 * ambulance numbers — the one thing a visitor must never lose access to.
 */
class MaintenanceGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteFeatures::enabled('behaviour_maintenance') || $request->user('web')) {
            return $next($request);
        }

        return response()
            ->view('pages.maintenance', [], 503)
            ->header('Retry-After', '3600');
    }
}
