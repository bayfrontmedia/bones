<?php

/*
 * For more information, see:
 * https://github.com/bayfrontmedia/monolog-factory#configuration-array
 */

use Bayfront\Bones\Application\Utilities\App;

return [
    'main' => [ // Channel name
        'default' => App::getConfig('app.debug') === false,
        'enabled' => true,
        'handlers' => [
            'RotatingFileHandler' => [
                'params' => [
                    'filename' => App::storagePath('/app/logs/main.log'),
                    'maxFiles' => 90,
                    'level' => 'INFO'
                ],
                'formatter' => [
                    'name' => 'LineFormatter',
                    'params' => [
                        'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                        'dateFormat' => 'Y-m-d H:i:s T'
                    ]
                ]
            ]
        ],
        'processors' => [
            'IntrospectionProcessor' => [
                'params' => [
                    'level' => 'ERROR'
                ]
            ]
        ]
    ],
    'dev' => [ // Channel name
        'default' => App::getConfig('app.debug') === true,
        'enabled' => true,
        'handlers' => [
            'BrowserConsoleHandler' => [
                'params' => [
                    'level' => 'DEBUG'
                ]
            ]
        ],
        'processors' => [
            'IntrospectionProcessor' => [
                'params' => [
                    'level' => 'ERROR'
                ]
            ]
        ]
    ]
];