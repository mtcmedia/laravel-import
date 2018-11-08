<?php

return [
    'import' => [
        'driver'    => 'mysql',
        'host'      => env('IMPORT_DB_HOST', 'localhost'),
        'port'      => env('IMPORT_DB_PORT', 3306),
        'database'  => env('IMPORT_DB_DATABASE', 'forge'),
        'username'  => env('IMPORT_DB_USERNAME', 'forge'),
        'password'  => env('IMPORT_DB_PASSWORD', ''),
        'charset'   => env('IMPORT_DB_CHARSET', 'utf8mb4'),
        'collation' => env('IMPORT_DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix'    => env('IMPORT_DB_PREFIX', ''),
        'timezone'  => env('IMPORT_DB_TIMEZONE', '+00:00'),
        'strict'    => env('IMPORT_DB_STRICT_MODE', false),
    ],
];