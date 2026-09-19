<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL of the Hytale core API (the module the website talks to).
    |
    */

    'base_url' => env('HYTALE_API_URL', 'http://localhost:3000'),

    /*
    |--------------------------------------------------------------------------
    | Module identity
    |--------------------------------------------------------------------------
    |
    | The website's own module id in the core registry. Used by the mock
    | transport so server ownership rules behave like production.
    |
    */

    'module_id' => env('HYTALE_MODULE_ID', '01a0a967-cb40-76c1-a30a-4983d3633605'),

    'module_name' => env('HYTALE_MODULE_NAME', 'siteweb'),

    /*
    |--------------------------------------------------------------------------
    | Module credentials
    |--------------------------------------------------------------------------
    |
    | The website is itself a module. `api_key` is sent on every request,
    | `hmac_secret` signs POST/DELETE bodies. Both are issued once when the
    | `siteweb` module is registered (see docs/api.md).
    |
    */

    'api_key' => env('HYTALE_API_KEY'),

    'hmac_secret' => env('HYTALE_API_HMAC_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Timestamp tolerance
    |--------------------------------------------------------------------------
    |
    | Accepted clock skew (seconds) between the website and the core when
    | validating signed requests.
    |
    */

    'tolerance' => (int) env('HYTALE_API_TOLERANCE', 300),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('HYTALE_API_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Mock mode
    |--------------------------------------------------------------------------
    |
    | When true, the website uses an in-memory fake transport instead of the
    | real API. Handy while the core is not deployed yet: no request leaves
    | the server and deterministic data is returned. Flip to false to talk to
    | the real API without changing any application code.
    |
    */

    'mock' => (bool) env('HYTALE_API_MOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Response cache
    |--------------------------------------------------------------------------
    |
    | GET responses are cached to limit calls to the core. Writes bump a
    | version key which invalidates every cached entry. Disabled automatically
    | in mock mode.
    |
    */

    'cache' => [
        'enabled' => (bool) env('HYTALE_API_CACHE', true),
        'store' => env('HYTALE_API_CACHE_STORE', 'redis'),
        'ttl' => (int) env('HYTALE_API_CACHE_TTL', 60),
        'prefix' => env('HYTALE_API_CACHE_PREFIX', 'hytale-api'),
    ],


];
