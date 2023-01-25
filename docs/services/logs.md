# Services: Logs

The [Monolog Factory](https://github.com/bayfrontmedia/monolog-factory) library is used to manage logging.

This library will be added to the service container with alias `logs` if a valid 
[configuration array](https://github.com/bayfrontmedia/monolog-factory#configuration-array) exists as `/config/logs.php`.

**Example:**

```php
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
```

## Installation

The logs service can be installed with the `php bones install:service --logs` [console command](../usage/console.md).

Installing the logs service will perform the following actions:

### Add config file

A config file will be added to `/config/logs.php`. (See above example)

### Create event subscriber

The `/app/Events/LogContext` event subscriber will be added, which adds the IP and URL to all log entries
over the HTTP interface.