# Services: Router

The [Route It](https://github.com/bayfrontmedia/route-it) library is used to route incoming HTTP requests.

This library will be added to the service container with alias `router` if a valid 
[configuration array](https://github.com/bayfrontmedia/route-it#usage) exists at `/config/router.php`.

**Example:**

```php
use Bayfront\Bones\Application\Utilities\App;

return [
    'host' => App::getEnv('ROUTER_HOST'), // Default host
    'route_prefix' => App::getEnv('ROUTER_ROUTE_PREFIX'), // Default route prefix
    'automapping_enabled' => false,
    'automapping_namespace' => 'App\\Controllers',
    'automapping_route_prefix' => App::getEnv('ROUTER_ROUTE_PREFIX'),
    'class_namespace' => 'App\\Controllers',
    'files_root_path' => App::resourcesPath('/views'),
    'force_lowercase_url' => true
];
```

Although routes do not have to be added until the `app.http` event,
subscribing them to the `app.bootstrap` event will enable them to be shown when using the `php bones route:list` [console command](#console-commands).

In order to utilize the service container to resolve controllers, Bones uses a custom `RouterDispatcher` class,
which adds the following [filter](filters.md):

- `router.parameters`: Used to filter parameters passed to the route destination when dispatching.

## Installation

The router service can be installed with the `php bones install:service --router` [console command](../usage/console.md).

Installing the router service will perform the following actions:

### Add environment variables

Applicable environment variables will be added to the `.env` file, if not already existing.

**Example:**

```dotenv
#---- Start router service ----#
ROUTER_HOST=example.com
ROUTER_ROUTE_PREFIX=/
#---- End router service ----#
```

### Add config file

A config file will be added to `/config/router.php` (See above example)

### Create event subscriber

The `/app/Events/Routes` event subscriber will be added, 
which allows for all the routes for your application to be defined.
Some examples are provided to help get you started.

The `router.route_prefix` filter is used from within this file to filter the value of the route prefix.

### Create controller

The `/app/Controllers/Home` controller will be added, which is used by the event subscriber which was created (see above).

The `response.body` filter is used from within this file to filter the value of the response body.

## Console commands

The following [console commands](../usage/console.md) can be used with relation to the router:

- `php bones route:list`