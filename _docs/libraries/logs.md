# Logs

The [Monolog Factory](https://github.com/bayfrontmedia/monolog-factory) library is used to manage logging.

This library will be added to the services container as `logs` if a valid [configuration array](https://github.com/bayfrontmedia/monolog-factory#configuration-array) array exists as `/config/logs.php`.

**Example:**
```
return [
    'App' => [
        'default' => true,
        'enabled' => true,
        'handlers' => [
            'RotatingFileHandler' => [
                'params' => [
                    'filename' => storage_path('/app/logs/app.log'),
                    'maxFiles' => 30,
                    'level' => 'INFO'
                ],
                'formatter' => [
                    'name' => 'LineFormatter',
                    'params' => [
                        'output' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                        'dateformat' => 'Y-m-d H:i:s T'
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
    ]
];
``` 