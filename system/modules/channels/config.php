<?php

Config::set('channels', [
    'active' => true,
    'path' => 'system/modules',
    'topmenu' => true,
    '__password' => 'maybeconsiderchangingthis',
    'processors' => [
        'TestProcessor'
    ],
    'dependencies' => [
        'ddeboer/imap' => '^1.21.0'
    ],
]);
