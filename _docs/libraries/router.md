# Router

The [Route It](https://github.com/bayfrontmedia/route-it) library is required by Bones.

This library exists in the services container as `router`. 
A valid [configuration array](https://github.com/bayfrontmedia/route-it#start-using-route-it) must exist at `/config/router.php`.

**Example:**
```
return [
    'host' => get_env('ROUTER_HOST'),
    'route_prefix' => get_env('ROUTER_ROUTE_PREFIX'),
    'automapping_enabled' => false,
    'automapping_namespace' => 'App\\Controllers',
    'automapping_route_prefix' => '', // No trailing slash
    'class_namespace' => 'App\\Controllers',
    'files_root_path' => resources_path('/views'), // No trailing slash
    'force_lowercase_url' => true
];
```

The `host` and `route_prefix` keys are not required by Route It, but it may be helpful to define these values 
in your `.env` file, and use them when defining your routes. This allows for the values to differ 
depending on the environment.

## Routes

Routes should be defined in `/resources/routes.php`.
This file is required by Bones.

**Example:**

```
use Bayfront\RouteIt\Router;

/** @var $router Router */

$router->setHost(get_config('router.host'))
    ->setRoutePrefix(do_filter('router.route_prefix', get_config('router.route_prefix')))
    ->addNamedRoute('/storage', 'storage')
    ->addFallback('ANY', function () {
        abort(404);
    })
    ->get('/', 'Home:index', [], 'home');
```

The above example uses a custom filter named `router.route_prefix`, and adds a named route to `/storage`. 

To get a list of all defined routes, the `php bones route:list` command can be used.
For more information, see [CLI](cli.md).