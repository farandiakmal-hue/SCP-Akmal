<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "memcached",
    |            "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    

    'files' => storage_path('framework/sessions'),

   

    'connection' => env('SESSION_CONNECTION'),

    

    'table' => env('SESSION_TABLE', 'sessions'),

    
    'store' => env('SESSION_STORE'),

    

    'lottery' => [2, 100],

    

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    

    'path' => env('SESSION_PATH', '/'),

    

    'domain' => env('SESSION_DOMAIN'),

    

    'secure' => env('SESSION_SECURE_COOKIE'),

    

    'http_only' => env('SESSION_HTTP_ONLY', true),

    

    'same_site' => env('SESSION_SAME_SITE', 'lax'),


    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
