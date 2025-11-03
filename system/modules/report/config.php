<?php

Config::set('report', [
    'active' => true,
    'path' => 'system/modules',
    "dependencies" => [
        "tecnickcom/tcpdf" => "^6.10.0",
        "parsecsv/php-parsecsv" => "^1.3.2",
    ],
    '__password' => 'maybeconsiderchangingthis',
    'topmenu' => true,
    'hooks' => [
        'admin',
        'core_web'
    ],
    'database' => [
        'hostname'  => '',
        'username'  => '',
        'password'  => '',
        'database'  => '',
        'driver'    => ''
    ]
]);
