<?php
Config::set('tokens', [
    'active' => true,
    'path' => 'system/modules',
    'topmenu' => false,
    'hooks' => [
        'auth',
        'tokens'
    ],
]);
