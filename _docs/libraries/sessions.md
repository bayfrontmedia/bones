# Sessions

The [Session Manager](https://github.com/bayfrontmedia/session-manager) library is used to manage sessions.

This library will be added to the services container as `session` if enabled using a valid configuration array as `/config/sessions.php`.

**Example:**
```
return [
    'enabled' => true,
    'handler' => 'Flysystem', // Class name in the Bayfront\SessionManager\Handlers namespace
    'disk_name' => 'local', // Filesystem disk used to store sessions
    'root_path' => '/app/sessions',
    'config' => [
        'cookie_name' => 'bones_sess',
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => true,
        'cookie_http_only' => true,
        'sess_regenerate_duration' => 300, // 0 to disable
        'sess_lifetime' => 3600, // 0 for "until the browser is closed"
        'sess_gc_probability' => 1, // 0 to disable garbage collection
        'sess_gc_divisor' => 100
    ]
];
``` 