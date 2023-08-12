# Services: API

Bones comes with an API service ready to use out of the box.

The API service is dependent on the following:

- [Db service](../db.md)
- [Router service](../router.md)
- [Scheduler service](../scheduler.md)
- `Bayfront\MultiLogger\Log` instance set in the container ([see documentation](https://github.com/bayfrontmedia/multi-logger/tree/master))

The `Log` instance can be added to the container from the `resources/bootstrap.php` file. For example:

```php
$container->set('Bayfront\MultiLogger\Log', function () {

    $app_channel = new Logger('App');

    $level = App::environment() == App::ENV_DEV ? Level::Debug : Level::Info;

    $file_handler = new RotatingFileHandler(App::storagePath('/app/logs/app.log'), 90, $level);

    $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
    $date_format = 'Y-m-d H:i:s T';

    $file_handler->setFormatter(new LineFormatter($output, $date_format));

    $app_channel->pushHandler($file_handler);

    return new Bayfront\MultiLogger\Log($app_channel);

});

$container->setAlias('log', 'Bayfront\MultiLogger\Log');
```

## Installation

The API service can be installed with the `php bones install:service --api` [console command](../../usage/console.md).

Installing the API service will perform the following actions:

### Add config files

The following config files will be added:

- `/config/api.php` - General API configuration
- `/config/api-docs.php` - API service documentation links to be returned with exceptions
- `/config/api-plans.php` - Available API service tenant plans, along with their roles and permissions

### Add database migration

A database migration file will be added which will create the necessary database tables needed for the API service.

### Create event subscriber

The `/app/Events/ApiEvents` event subscriber will be added.

### Create filter subscriber

The `/app/Filters/ApiFilters` filter subscriber will be added.

## Completing installation

If using the controllers provided by Bones for the API routes, ensure the app's namespace is not automatically added
to the router destinations.
This can be done by removing or leaving blank the `class_namespace` array key in the `config/router.php` file.

In addition, if an exception handler exists at `app/Exceptions/Handler.php`, ensure it extends `Bayfront\Bones\Services\Api\Exceptions\ApiExceptionHandler`.
Doing so will allow the API service to respond appropriately to any exceptions.

## Console commands

The following [console commands](../../usage/console.md) can be used with relation to the API service:

- `php bones api:manage:tenant`
- `php bones api:manage:user`

## Additional information

Additional documentation is coming soon.