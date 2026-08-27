<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Whether this installation is served over HTTPS is decided in exactly one
 * place — the scheme of `APP_URL` — and everything else follows from it: the
 * URLs the application generates, the public disk's upload URLs, and whether
 * the session cookie is marked Secure.
 *
 * The reason it has to be decided at all, rather than taken from the request:
 *
 * A signed link is signed over the whole URL, scheme included. The confirmation
 * link in an appointment email is built with `URL::signedRoute()`, and the
 * day-before reminder is built by `appointments:remind` running under cron —
 * with no request to take a scheme from, that generator falls back to APP_URL.
 * Leave APP_URL on http and a patient taps an http link, Apache redirects them
 * to https, and the signature is then checked against an https URL that was
 * never the one signed. It answers 403: the booking is fine, the patient is
 * looking at a refusal, and nothing in the logs says "scheme".
 *
 * So: one switch, and both halves of the link agree.
 *
 * Behind a proxy that terminates TLS there is a second half to this — see
 * `config/trustedproxy.php`, without which the request arrives looking like
 * plain http and the same signature check fails for the same reason.
 */
class Https
{
    /** Is this installation meant to be served over HTTPS? */
    public static function wanted(?string $appUrl = null): bool
    {
        $appUrl ??= config('app.url');

        return is_string($appUrl)
            && str_starts_with(strtolower(trim($appUrl)), 'https://');
    }

    /**
     * Generate https URLs everywhere, including from the console.
     *
     * Deliberately not a redirect: sending http traffic to https is the web
     * server's job (see deploy/hospital-production.conf), and an application
     * that also redirects is one that loops forever the day it sits behind a
     * proxy nobody remembered to declare.
     */
    public static function enforce(?string $appUrl = null): bool
    {
        if (! static::wanted($appUrl)) {
            return false;
        }

        URL::forceScheme('https');

        return true;
    }
}
