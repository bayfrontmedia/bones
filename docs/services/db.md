# Services: Db

The [Simple PDO](https://github.com/bayfrontmedia/simple-pdo) library is used
to manage database connections.

This library will be added to the service container with alias `db` if a [Simple PDO factory](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/getting-started.md#factory-setup) 
configuration array exists at `/config/database.php`.

**Example:**

```php
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\SimplePdo\Db;

$options = [];

if (App::getEnv('DB_SECURE_TRANSPORT')) {
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
}

return [
    Db::DB_DEFAULT => [ // Connection name
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

The database service can be installed with the `php bones install:service --db` [console command](../usage/console.md).

After running the installation:

- Update the environment variables with your database credentials
- Update your `composer.json` file with `composer require ext-pdo`

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

## Migrations

Database migrations act as a version control system for the database schema of your application.
Migrations should be placed in the `app/Migrations` directory and 
must implement `Bayfront\Bones\Interfaces\MigrationInterface`.

Migrations can be created with the `php bones make:migration` command.
In order for a migration to run, an instance of the class must be added to the `bones.migrations` filter.

### Example

Example `FilterSubscriberInterface`:

```php
namespace App\Filters;

use App\Migrations\CreateInitialTables;
use Bayfront\Bones\Abstracts\FilterSubscriber;
use Bayfront\Bones\Application\Services\Filters\FilterSubscription;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;
use Bayfront\SimplePdo\Db;

class ExampleAppFilters extends FilterSubscriber implements FilterSubscriberInterface
{

    protected Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }
    
    public function getSubscriptions(): array
    {

        return [
            new FilterSubscription('bones.migrations', [$this, 'createInitialTables'], 10)
        ];

    }

    public function createInitialTables(array $array): array
    {
        return array_merge($array, [
            new CreateInitialTables($this->db) // MigrationInterface
        ]);
    }

}
```

The required `migrations` database table will be created the first time the `php bones migrate:up` command is used.

> **NOTE:** Be sure to back up your database before running any migrations

## Console commands

The following [console commands](../usage/console.md) can be used with relation to the database:

- `php bones make:migration`
- `php bones migrate:down`
- `php bones migrate:up`
- `php bones migration:list`