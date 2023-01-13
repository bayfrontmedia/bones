<?php

/*
 * For more information, see:
 * https://github.com/bayfrontmedia/monolog-factory#configuration-array
 */

return [
    'App' => [
        'default' => true,
        'enabled' => true,
        'handlers' => [
            'RotatingFileHandler' => [
                'params' => [
                    'filename' => storage_path('/app/logs/app.log'),
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
            ],
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
    ],

];