<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Amazon SP-API Application ID
    |--------------------------------------------------------------------------
    | Found in Seller Central > Apps & Services > Develop Apps
    | Used in the OAuth authorization URL.
    */
    'application_id' => env('AMAZON_APP_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | LWA (Login with Amazon) Credentials
    |--------------------------------------------------------------------------
    | From your SP-API app in Seller Central.
    | These can be overridden per-tenant via the amazon_accounts table.
    */
    'lwa_client_id'     => env('AMAZON_LWA_CLIENT_ID', ''),
    'lwa_client_secret' => env('AMAZON_LWA_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | OAuth Redirect URI
    |--------------------------------------------------------------------------
    | Must match exactly what's registered in your SP-API application.
    | Register it in: Seller Central > Apps > Your App > Edit > OAuth URLs
    */
    'redirect_uri' => env('AMAZON_REDIRECT_URI', env('APP_URL') . '/amazon/callback'),

    /*
    |--------------------------------------------------------------------------
    | Default Marketplace
    |--------------------------------------------------------------------------
    | Default marketplace ID if not stored per-account.
    | US: ATVPDKIKX0DER | UK: A1F83G8C2ARO7P | DE: A1PA6795UKMFR9
    */
    'default_marketplace_id' => env('AMAZON_DEFAULT_MARKETPLACE_ID', 'ATVPDKIKX0DER'),

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'orders_days_back' => env('AMAZON_SYNC_DAYS_BACK', 30),  // Fetch orders from N days ago
        'rate_limit_sleep' => 1, // Seconds to sleep between paginated requests
    ],

];
