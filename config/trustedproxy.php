<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by Laravel's TrustProxies middleware. Empty (the default) trusts no
    | proxy: X-Forwarded-* headers are ignored, so a visitor cannot forge the
    | IP address used by the contact form rate limiter.
    |
    | Behind a reverse proxy or CDN, list only its addresses or CIDR ranges,
    | comma separated. "*" trusts the immediate caller, which is only safe when
    | the application cannot be reached except through that proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
