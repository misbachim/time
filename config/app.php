<?php
return [
    'domain' => env('APP_DOMAIN'),
    'key_private' => base_path('resources/keys/enc_prt.pem'), // expired : 17 July 2018
    'key_public'  => base_path('resources/keys/enc_pub.crt'), // expired : 17 July 2018
    'token_ttl' => 1800, // seconds
    'token_renew_window' => 300, // seconds

    'locale' => env('APP_LOCALE', 'en'), //default app locale
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'), //backup locale if default not exist

    'default_created_by' => 0,

    //validator constants
    'valid_date' => 'date_format:Y-m-d'
];
