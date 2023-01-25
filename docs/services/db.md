# Services: Db

The [Simple PDO](https://github.com/bayfrontmedia/simple-pdo) library is used
to manage database connections.

This library will be added to the service container with alias `db` if a [Simple PDO factory](https://github.com/bayfrontmedia/simple-pdo#factory-usage) 
configuration array exists at `/config/database.php`.

**Example:**

```php
use Bayfront\Bones\Application\Utilities\App;

$options = [];

if (App::getEnv('DB_SECURE_TRANSPORT')) {
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
}

return [
    'primary' => [ // Connection name
        'default' => true, // One connection on the array must be defined as default
        'adapter' => App::getEnv('DB_ADAPTER'), // Adapter to use
        'host' => App::getEnv('DB_HOST'),
        'port' => App::getEnv('DB_PORT'),
        'database' => App::getEnv('DB_DATABASE'),
        'user' => App::getEnv('DB_USER'),
        'password' => App::getEnv('DB_PASSWORD'),
        'options' => $options
    ]
];
```

## Installation

The database service can be installed with the `install:service --db` [console command](../usage/console.md).

After running the installation:

- Update the environment variables with your database credentials
- Update your `compoer.json` file with `composer require ext-pdo`

Installing the database service will perform the following actions:

### Add environment variables

Applicable environment variables will be added to the `.env` file, if not already existing.

**Example:**

```dotenv
#---- Start database service ----#
DB_ADAPTER='MySQL'
DB_HOST='localhost'
DB_PORT=3306
DB_DATABASE='database_name'
DB_USER='database_user'
DB_PASSWORD='user_password'
# Is require_secure_transport=ON?
DB_SECURE_TRANSPORT=false
#---- End database service ----#
```

### Add config file

A config file will be added to `/config/database.php`. (See above example)