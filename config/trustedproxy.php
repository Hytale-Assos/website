<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | TLS is terminated by a reverse proxy in front of the application, so the
    | app only ever receives plain HTTP. Without trusting the proxy's forwarded
    | headers, Laravel keeps generating http:// URLs while the browser is on an
    | https:// page; the browser then treats Inertia's response as cross-origin
    | and hides its X-Inertia header, and the client reports a "plain JSON
    | response was received" error.
    |
    | Set TRUSTED_PROXIES to a comma-separated list of IPs/CIDRs to narrow it
    | down. When unset or empty, it defaults to "*" (trust the immediate proxy)
    | in production and to none elsewhere, so development is unaffected.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: (env('APP_ENV') === 'production' ? '*' : null),

];
