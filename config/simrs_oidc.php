<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIMRS OIDC SSO Enabled
    |--------------------------------------------------------------------------
    |
    | Master feature toggle to enable or disable SIMRS OIDC SSO login.
    | When disabled, the login page displays the standard local authentication.
    |
    */
    'enabled' => env('SIMRS_OIDC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | SIMRS OIDC Server Endpoints & Credentials
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to RSUD Kardinah's SIMRS OAuth2 / OIDC Server.
    |
    */
    'base_url' => env('SIMRS_OIDC_BASE_URL', 'http://172.16.61.111:8080'),
    'token_endpoint' => env('SIMRS_OIDC_TOKEN_ENDPOINT', 'http://172.16.61.111:8080/oauth/v2/token'),
    'client_id' => env('SIMRS_OIDC_CLIENT_ID', 'sipakar-client'),
    'client_secret' => env('SIMRS_OIDC_CLIENT_SECRET', 'sipakar-secret-key'),
    'scope' => env('SIMRS_OIDC_SCOPE', 'openid profile email simrs:pegawai'),
    
    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Maximum time to wait for response from SIMRS server before gracefully
    | aborting with a user-friendly error message.
    |
    */
    'timeout_seconds' => (int) env('SIMRS_OIDC_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Default Role for First-Time SSO Users
    |--------------------------------------------------------------------------
    |
    | The default role assigned to new employees logging in via SIMRS SSO for
    | the first time in SIPAKAR. Existing user roles are always preserved.
    |
    */
    'default_role' => env('SIMRS_OIDC_DEFAULT_ROLE', 'Operator'),
];
