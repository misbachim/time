<?php
return [
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'database' => env('DB_DATABASE'),
            'charset'  => 'utf8'
        ],
        'testing' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'database' => env('DB_TEST_DATABASE'),
            'charset'  => 'utf8'
        ],
        'pgsql_um' => [
            'driver'   => env('DB_CONNECTION_UM'),
            'host'     => env('DB_HOST_UM'),
            'port'     => env('DB_PORT_UM'),
            'username' => env('DB_USERNAME_UM'),
            'password' => env('DB_PASSWORD_UM'),
            'database' => env('DB_DATABASE_UM'),
            'charset'  => 'utf8'
        ],
        'pgsql_travel' => [
            'driver'   => env('DB_CONNECTION_TRAVEL'),
            'host'     => env('DB_HOST_TRAVEL'),
            'port'     => env('DB_PORT_TRAVEL'),
            'username' => env('DB_USERNAME_TRAVEL'),
            'password' => env('DB_PASSWORD_TRAVEL'),
            'database' => env('DB_DATABASE_TRAVEL'),
            'charset'  => 'utf8'
        ],
        'pgsql_core' => [
            'driver'   => env('DB_CONNECTION_CORE'),
            'host'     => env('DB_HOST_CORE'),
            'port'     => env('DB_PORT_CORE'),
            'username' => env('DB_USERNAME_CORE'),
            'password' => env('DB_PASSWORD_CORE'),
            'database' => env('DB_DATABASE_CORE'),
            'charset'  => 'utf8'
        ],
        'log' => [
            'driver'   => env('DB_CONNECTION_LOG'),
            'host'     => env('DB_HOST_LOG'),
            'port'     => env('DB_PORT_LOG'),
            'username' => env('DB_USERNAME_LOG'),
            'password' => env('DB_PASSWORD_LOG'),
            'database' => env('DB_DATABASE_LOG'),
            'charset'  => 'utf8'
        ]
    ],
    'migrations' => 'migrations',
    'default' => env('DB_CONNECTION')
];
