<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | GlitchTip URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your GlitchTip instance. Use the hosted service or point
    | this at a self-hosted install, without the trailing /api path.
    |
    */

    'url' => env('GLITCHTIP_URL', 'https://app.glitchtip.com'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | A personal API token, created under Profile > Auth Tokens. Grant only
    | the scopes the application needs (for example event:read, event:write
    | and project:read).
    |
    */

    'token' => env('GLITCHTIP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Default Organization
    |--------------------------------------------------------------------------
    |
    | The organization slug used by organization-scoped calls. Override it per
    | call chain with GlitchTip::forOrganization('other-slug').
    |
    */

    'organization' => env('GLITCHTIP_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Options
    |--------------------------------------------------------------------------
    |
    | Timeouts are in seconds. Idempotent requests (GET, PUT and DELETE) are
    | retried on connection failures, 429 and 5xx gateway responses; POSTs are
    | never retried so they cannot be duplicated.
    |
    */

    'timeout' => (int) env('GLITCHTIP_TIMEOUT', 30),

    'connect_timeout' => (int) env('GLITCHTIP_CONNECT_TIMEOUT', 10),

    'retry' => [
        'times' => (int) env('GLITCHTIP_RETRY_TIMES', 2),
        'sleep_milliseconds' => (int) env('GLITCHTIP_RETRY_SLEEP', 500),
    ],

];
