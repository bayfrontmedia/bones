# Database

The [Simple PDO](https://github.com/bayfrontmedia/simple-pdo) library is used 
to manage database connections.

This library will be added to the services container as `db` if a [Simple PDO factory](https://github.com/bayfrontmedia/simple-pdo#factory-usage) configuration array exists as `/config/database.php`.

**Example:**
```
return [
    'primary' => [ // Connection name
        'default' => true, // One connection on the array must be defined as default
        'adapter' => get_env('DB_ADAPTER'), // Adapter to use
        'host' => get_env('DB_HOST'),
        'port' => get_env('DB_PORT'),
        'database' => get_env('DB_DATABASE'),
        'user' => get_env('DB_USER'),
        'password' => get_env('DB_PASSWORD')
    ]
];
``` 