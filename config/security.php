<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | The policy itself is in App\Http\Middleware\SecurityHeaders, next to the
    | reasoning for each directive. Only the rollout is decided here.
    |
    | It ships REPORT-ONLY. A CSP is the one security header that can take a
    | site down — a directive too tight anywhere breaks that page for every
    | visitor, and no test in this suite would catch it, because the suite
    | never runs a browser. Report-only sends the same policy, changes nothing,
    | and prints every violation to the browser console.
    |
    | So: install it, open the site — home, the booking form, the gallery
    | lightbox, the contact page's map, the panel, the portal — and read the
    | console. When it is quiet, set CSP_ENFORCE=true. That is the same ratchet
    | as the HSTS max-age in deploy/hospital-production.conf, for the same
    | reason: these are the two headers that are painful to get wrong.
    |
    */

    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'enforce' => env('CSP_ENFORCE', false),
    ],

];
