<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen locale for the request.
 *
 * Preference order: an explicit choice stored in the session, then the
 * browser's Accept-Language header, then the configured default. Anything
 * not listed in config('app.available_locales') is ignored, so a crafted
 * session value or header cannot point the translator at an arbitrary path.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $available = array_keys(config('app.available_locales', []));

        $session = $request->session()->get('locale');
        if (in_array($session, $available, true)) {
            return $session;
        }

        $preferred = $request->getPreferredLanguage($available);
        if (is_string($preferred) && in_array($preferred, $available, true)) {
            return $preferred;
        }

        return config('app.locale');
    }
}
