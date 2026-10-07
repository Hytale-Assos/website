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
    | response was received" error. It also cannot recover the real client IP
    | from X-Forwarded-For.
    |
    | Set TRUSTED_PROXIES to a comma-separated list of IPs/CIDRs: the network
    | shared with the reverse proxy (Traefik, for the production deployment).
    | Never use "*": it trusts the left-most X-Forwarded-For entry, which any
    | public client can spoof. When unset, no proxy is trusted.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
