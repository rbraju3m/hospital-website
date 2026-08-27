<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Read by Laravel's TrustProxies middleware. Empty is the right answer for
    | the deployment in deploy/: Apache terminates TLS itself and talks to PHP
    | in-process, so there is no proxy in front and nothing should be believed
    | about X-Forwarded-Proto — any client can send that header, and trusting
    | it from everywhere lets one claim its plain-http request was secure.
    |
    | The day something *is* put in front — nginx, a load balancer, Cloudflare
    | — this has to be filled in, or every request arrives looking like http.
    | The visible symptom is not "no padlock": it is a 403 on the confirmation
    | link in every appointment email, because a signature made over https is
    | being checked against a URL that now reads http. See App\Support\Https.
    |
    | TRUSTED_PROXIES takes a comma-separated list of addresses or CIDR ranges,
    | or '*' to trust whatever is directly in front — which is safe only when
    | nothing but that proxy can reach this machine's port.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
