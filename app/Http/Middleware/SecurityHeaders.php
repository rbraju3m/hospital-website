<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * The response headers that are not about transport — HTTPS itself is
 * App\Support\Https and the vhosts in deploy/.
 *
 * All of it is in one place on purpose. These headers describe the whole
 * application, and split between Apache and PHP they would be two lists that
 * drift: a directive added to the vhost is invisible under `artisan serve`,
 * where the site is actually developed, so a policy that breaks a page would
 * first be discovered in production. Here they apply wherever the application
 * runs, and a test can read them.
 */
class SecurityHeaders
{
    /**
     * Sent on every response, and none of them can break a page.
     */
    private const HEADERS = [
        // Stop the browser second-guessing a Content-Type. Patient documents
        // are streamed from the private disk, and an uploaded file that the
        // browser decides is HTML would run on this origin.
        'X-Content-Type-Options' => 'nosniff',

        // Clickjacking. Same answer as the CSP's frame-ancestors below, for
        // the browsers and scanners that only read this one.
        'X-Frame-Options' => 'SAMEORIGIN',

        // Send the full URL to ourselves, only the origin off-site, and
        // nothing at all when leaving https for http. Portal URLs carry a
        // booking reference, and the contact page frames a third-party map.
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Nothing here asks for a camera, a microphone or a location, so no
        // injected script gets to either. `fullscreen=(self)` is not padding:
        // the gallery lightbox's F key is the Fullscreen API, and a bare
        // `fullscreen=()` would switch it off.
        'Permissions-Policy' => 'accelerometer=(), autoplay=(), camera=(), display-capture=(), '
            .'encrypted-media=(), fullscreen=(self), geolocation=(), gyroscope=(), '
            .'magnetometer=(), microphone=(), midi=(), payment=(), usb=(), xr-spatial-tracking=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $header => $value) {
            $response->headers->set($header, $value);
        }

        if ($policy = $this->contentSecurityPolicy()) {
            $response->headers->set(
                config('security.csp.enforce')
                    ? 'Content-Security-Policy'
                    : 'Content-Security-Policy-Report-Only',
                $policy,
            );
        }

        return $response;
    }

    /**
     * Two directives in here are concessions rather than choices, and both are
     * worth knowing before somebody tries to tighten them:
     *
     * `'unsafe-eval'` is Alpine. The standard build evaluates every `x-data`
     * and `x-show` expression through the AsyncFunction constructor, which the
     * browser counts as eval. Removing it means Alpine's CSP build, which
     * cannot evaluate expressions at all — every one of them becomes a method
     * on a registered component. That is a rewrite of the interaction layer,
     * not a header change.
     *
     * `'unsafe-inline'` on style-src is the inline `style` attributes the
     * design system writes: the meter's width, the reveal stagger's delay, the
     * card spotlight's pointer position. A nonce cannot cover an attribute.
     *
     * Scripts do NOT get 'unsafe-inline'. The five inline blocks in the views
     * carry a nonce instead — see csp_nonce() — so an injected <script> tag is
     * still refused, which is most of what a CSP is for on a page that renders
     * anything a member of staff typed.
     */
    private function contentSecurityPolicy(): ?string
    {
        if (! config('security.csp.enabled')) {
            return null;
        }

        // `npm run dev` serves the bundle from Vite's own origin over a
        // websocket. Naming that origin here would be writing a development
        // server into the application's security policy; the header is simply
        // not sent while it is running.
        if (Vite::isRunningHot()) {
            return null;
        }

        $nonce = Vite::cspNonce();

        return implode('; ', array_filter([
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            // Nothing here embeds this site, and the panel is not framed.
            "frame-ancestors 'self'",
            // A form is only ever posted back to us. The payment gateway is
            // reached by a redirect, not by posting a form off-site.
            "form-action 'self'",
            "script-src 'self' ".($nonce ? "'nonce-{$nonce}' " : '')."'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            // The contact page frames OpenStreetMap's embed. It is the only
            // third-party origin this site loads anything from.
            'frame-src https://www.openstreetmap.org',
        ]));
    }
}
