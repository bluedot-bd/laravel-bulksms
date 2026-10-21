<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default gateway config
    |--------------------------------------------------------------------------
    |
    | Name of the file inside storage/magic-sms used by the container instance
    | and therefore by the `LaravelBulksms` facade. Leave it empty when every
    | call passes its own config name to the constructor.
    |
    */

    'config' => env('BULK_SMS_CONFIG'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for the gateway before giving up.
    |
    */

    'timeout' => env('BULK_SMS_TIMEOUT', 15),

];
