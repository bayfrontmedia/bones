# Helpers

Helpers are globally accessible functions which can be used throughout your application.

## Custom helpers

You can create your own helpers by placing them in the `/resources/helpers` directory.

Custom helpers should be included via the [use_helper()](#use_helper) function.

## Bones helpers

The following helpers are automatically included by Bones:

- [get_datetime](#get_datetime)
- [root_path](#root_path)
- [public_path](#public_path)
- [config_path](#config_path)
- [resources_path](#resources_path)
- [storage_path](#storage_path)
- [get_env](#get_env)
- [get_config](#get_config)
- [get_container](#get_container)
- [in_container](#in_container)
- [get_from_container](#get_from_container)
- [put_in_container](#put_in_container)
- [set_in_container](#set_in_container)
- [get_model](#get_model)
- [get_service](#get_service)
- [use_helper](#use_helper)
- [create_key](#create_key)
- [abort](#abort)
- [is_cli](#is_cli)
- [is_cron](#is_cron)

## Services helpers

The following helpers are automatically included by Bones when their associated services exist in the container:

**Hooks helpers**

- [get_hooks](#get_hooks)
- [add_event](#add_event)
- [do_event](#do_event)
- [add_filter](#add_filter)
- [do_filter](#do_filter)

**Logs helpers**

- [get_logs](#get_logs)
- [log_event](#log_event)
- [log_debug](#log_debug)
- [log_info](#log_info)
- [log_notice](#log_notice)
- [log_warning](#log_warning)
- [log_error](#log_error)
- [log_critical](#log_critical)
- [log_alert](#log_alert)
- [log_emergency](#log_emergency)

**Router helpers**

- [get_router](#get_router)
- [get_named_routes](#get_named_routes)
- [get_named_route](#get_named_route)

**Translate helpers**

- [get_translate](#get_translate)
- [set_locale](#set_locale)
- [translate](#translate)
- [say](#say)

**Veil helpers**

- [get_veil](#get_veil)
- [get_view](#get_view)
- [view](#view)
- [view_or_fallback](#view_or_fallback)

<hr />

### get_datetime

**Description:**

Returns datetime in `Y-m-d H:i:s` format for current time
or of an optional timestamp.

**Parameters:**

- `$timestamp = 0` (int): Optional timestamp

**Returns:**

- (string)

**Example:**

```
$datetime = get_datetime();
```

<hr />

### root_path

**Description:**

Returns the fully qualified path to the `APP_ROOT_PATH` directory, 
ensuring a leading slash, no trailing slash and single forward slashes.

**Parameters:**

- `$path = ''` (string): Path relative to the `APP_ROOT_PATH` directory

**Returns:**

- (string)

**Example:**

```
$root_path = root_path();
```

<hr />

### public_path

**Description:**

Returns the fully qualified path to the `APP_PUBLIC_PATH` directory, 
ensuring a leading slash, no trailing slash and single forward slashes.

**Parameters:**

- `$path = ''` (string): Path relative to the `APP_PUBLIC_PATH` directory

**Returns:**

- (string)

**Example:**

```
$example_path = public_path('/example/directory');
```

<hr />

### config_path

**Description:**

Returns the fully qualified path to the `APP_CONFIG_PATH` directory, 
ensuring a leading slash, no trailing slash and single forward slashes.

**Parameters:**

- `$path = ''` (string): Path relative to the `APP_CONFIG_PATH` directory

**Returns:**

- (string)

**Example:**

```
$config_path = config_path();
```

<hr />

### resources_path

**Description:**

Returns the fully qualified path to the `APP_RESOURCES_PATH` directory, 
ensuring a leading slash, no trailing slash and single forward slashes.

**Parameters:**

- `$path = ''` (string): Path relative to the `APP_RESOURCES_PATH` directory

**Returns:**

- (string)

**Example:**

```
$example_resource = resources_path('/example/file.php');
```

<hr />

### storage_path

**Description:**

Returns the fully qualified path to the `APP_STORAGE_PATH` directory, 
ensuring a leading slash, no trailing slash and single forward slashes.

**Parameters:**

- `$path = ''` (string): Path relative to the `APP_STORAGE_PATH` directory

**Returns:**

- (string)

**Example:**

```
$storage_path = storage_path();
```

<hr />

### get_env

**Description:**

Returns value of `.env` variable, or default value if not existing. 

Converts strings from `true`, `false` and `null`.

**Parameters:**

- `$key` (string)
- `$default = NULL` (mixed): Default value to return if not existing

**Returns:**

- (mixed)

**Example:**

```
get_env('APP_TIMEZONE');
```

<hr />

### get_config

**Description:**

Returns value from a configuration array key using dot notation, 
with the first segment being the filename. (e.g.: filename.key)

**Parameters:**

- `$key` (string): Key to retrieve in dot notation
- `$default = NULL` (mixed): Default value to return

**Returns:**

- (mixed)

**Example:**

```
$key = get_config('app.key');
```

<hr />

### get_container

**Description:**

Returns instance of the service container.

**Parameters:**

- None

**Returns:**

- (`Bayfront\Container\Container`)

**Example:**

```
$container = get_container();
```

<hr />

### in_container

**Description:**

Does container have an instance with ID.

**Parameters:**

- `$id` (string)

**Returns:**

- (bool)

**Example:**

```
if (in_container('db')) {
    // Do something
}
```

<hr />

### get_from_container

**Description:**

Returns instance from the service container by ID.

**Parameters:**

- `$id` (string)

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$filesystem = get_from_container('filesystem');
```

<hr />

### put_in_container

**Description:**

Saves a preexisting class instance into the container identified by `$id`.

If another entry exists in the container with the same `$id`, it will be overwritten.

Saving a class instance to the container using its namespaced name as the `$id`
will allow it to be used by the container whenever another class requires it as a dependency.

**Parameters:**

- `$id` (string)
- `$object` (object)

**Returns:**

- (void)

**Example:**

```
$class_name = new Namespace\ClassName();

put_in_container('ClassName', $class_name);
```

<hr />

### set_in_container

**Description:**

Creates a class instance and saves it into the container identified by `$id`.
An instance of the class will be returned.

If another entry exists in the container with the same `$id`, it will be overwritten.

Saving a class instance to the container using its namespaced name as the `$id`
will allow it to be used by the container whenever another class requires it as a dependency.

**Parameters:**

- `$id` (string)
- `$class` (string): Fully namespaced class name
- `$params = []` (array): Named parameters to pass to the class constructor

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\ContainerException`

**Example:**

```
try {

    $class_name = set_in_container('ClassName', 'Namespace\ClassName');

} catch (ContainerException $e) {
    die($e->getMessage());
}
```

<hr />

### get_model

**Description:**

Returns a class instance in the models namespace as defined in the app config array.

**Parameters:**

- `$model` (string): Class name in the models namespace
- `$params = []` (array): Key/value pairs to be injected into the model's constructor
- `$force_unique = false` (bool)

**Returns:**

- (object)

**Throws:**

- `Bayfront\Bones\Exceptions\ModelException`

**Example:**

```
$model = get_model('ExampleModel');
```

<hr />

### get_service

**Description:**

Returns a class instance in the services namespace as defined in the app config array.

**Parameters:**

- `$service` (string): Class name in the services namespace
- `$params = []` (array): Key/value pairs to be injected into the service's constructor
- `$force_unique = false` (bool)

**Returns:**

- (object)

**Throws:**

- `Bayfront\Bones\Exceptions\ServiceException`

**Example:**

```
$service = get_service('ExampleService');
```

<hr />

### use_helper

**Description:**

Include helper file(s) located in the `/resources/helpers` directory.

**Parameters:**

- `$helpers` (string|array): Helper file(s) to include

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\FileNotFoundException`

**Example:**

```
use_helper([
    'first_file',
    'second_file'
]);
```

<hr />

### create_key

**Description:**

Create a cryptographically secure key of random bytes.

**Parameters:**

- `$characters = 32` (int): Number of characters of binary data

**Returns:**

- (string)

**Throws:**

- `Exception`

**Example:**

```
$key = create_key();
```

<hr />

### abort

**Description:**

Abort script execution by throwing a `Bayfront\Bones\Exceptions\HttpException` and send response message.

If no message is provided, the phrase for the HTTP status code will be used.

**Parameters:**

- `$code` (int): HTTP status code for response
- `$message = ''` (string): Message to be sent with response
- `$headers = []` (array): Key/value pairs of headers to be sent with the response
- `$reset_response = false` (bool): Reset the HTTP response after fetching it from the services container

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\Bones\Exceptions\InvalidStatusCodeException`
- `Bayfront\Bones\Exceptions\NotFoundException`

**Example:**

```
abort(429);
```

<hr />

### is_cli

**Description:**

Checks if the app is running from the command line interface.

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```
if (is_cli()) {
    // Do something
}
```

<hr />

### is_cron

**Description:**

Checks if the app is running from a cron job.

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```
if (is_cron()) {
    // Do something
}
```

<hr />

### get_hooks

**Description:**

Get PHP Hooks instance from container.

See: [https://github.com/bayfrontmedia/php-hooks](https://github.com/bayfrontmedia/php-hooks)

**Parameters:**

- None

**Returns:**

- (`Bayfront\Hooks\Hooks`)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$hooks = get_hooks();
```

<hr />

### add_event

**Description:**

Adds a hook for a given event name.

See: [https://github.com/bayfrontmedia/php-hooks#addevent](https://github.com/bayfrontmedia/php-hooks#addevent)

**Parameters:**

- `$name` (string): Name of event
- `$function` (callable)
- `$priority = 5` (int): Hooks will be executed by order of priority in descending order

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
add_event('app.custom_event', function() {
    // Do something
});
```

<hr />

### do_event

**Description:**

Execute queued hooks for a given event in order of priority.

See: [https://github.com/bayfrontmedia/php-hooks#doevent](https://github.com/bayfrontmedia/php-hooks#doevent)

**Parameters:**

- `$name` (string): Name of event
- `$arg` (mixed): Optional additional argument(s) to be passed to the functions hooked to the event

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
do_event('app.custom_event');
```

<hr />

### add_filter

**Description:**

Adds a hook for a given filter name.

See: [https://github.com/bayfrontmedia/php-hooks#addfilter](https://github.com/bayfrontmedia/php-hooks#addfilter)

**Parameters:**

- `$name` (string): Name of filter
- `$function` (callable)
- `$priority = 5` (int): Filters will be executed by order of priority in descending order

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
add_filter('name', function($name) {
    return strtoupper($name);
});
```

<hr />

### do_filter

**Description:**

Filters value through queued filters in order of priority.

See: [https://github.com/bayfrontmedia/php-hooks#dofilter](https://github.com/bayfrontmedia/php-hooks#dofilter)

**Parameters:**

- `$name` (string): Name of filter
- `$value` (mixed): Original value to be filtered

**Returns:**

- (mixed): Filtered value

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$name = do_filter('name', 'John');
```

<hr />

### get_logs

**Description:**

Get LoggerFactory instance from container.

See: [https://github.com/bayfrontmedia/monolog-factory](https://github.com/bayfrontmedia/monolog-factory)

**Parameters:**

- None

**Returns:**

- (`Bayfront\MonologFactory\LoggerFactory`)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$logs = get_logs();
```

<hr />

### log_event

**Description:**

Logs with an arbitrary level.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#log](https://github.com/bayfrontmedia/monolog-factory#log)

**Parameters:**

- `$level` (mixed)
- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_event('DEBUG', 'Message to be logged');
```

<hr />

### log_debug

**Description:**

Detailed debug information.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#debug](https://github.com/bayfrontmedia/monolog-factory#debug)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_debug('Message to be logged');
```

<hr />

### log_info

**Description:**

Interesting events.

Example: User logs in, SQL logs.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#info](https://github.com/bayfrontmedia/monolog-factory#info)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_info('Message to be logged');
```

<hr />

### log_notice

**Description:**

Normal but significant events.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#notice](https://github.com/bayfrontmedia/monolog-factory#notice)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_notice('Message to be logged');
```

<hr />

### log_warning

**Description:**

Exceptional occurrences that are not errors.

Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#warning](https://github.com/bayfrontmedia/monolog-factory#warning)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_warning('Message to be logged');
```

<hr />

### log_error

**Description:**

Runtime errors that do not require immediate action but should typically be logged and monitored.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#error](https://github.com/bayfrontmedia/monolog-factory#error)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_error('Message to be logged');
```

<hr />

### log_critical

**Description:**

Critical conditions.

Example: Application component unavailable, unexpected exception.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#critical](https://github.com/bayfrontmedia/monolog-factory#critical)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_critical('Message to be logged');
```

<hr />

### log_alert

**Description:**

Action must be taken immediately.

Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#alert](https://github.com/bayfrontmedia/monolog-factory#alert)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_alert('Message to be logged');
```

<hr />

### log_emergency

**Description:**

System is unusable.

The `$context` array is filtered through the `logs.context` hook.

See: [https://github.com/bayfrontmedia/monolog-factory#emergency](https://github.com/bayfrontmedia/monolog-factory#emergency)

**Parameters:**

- `$message` (string)
- `$context` (array)
- `$channel = NULL` (string): Log channel to use. Defaults to current channel.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
log_emergency('Message to be logged');
```

<hr />

### get_router

**Description:**

Get Router instance from container.

See: [https://github.com/bayfrontmedia/route-it](https://github.com/bayfrontmedia/route-it)

**Parameters:**

- None

**Returns:**

- (`Bayfront\RouteIt\Router`)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$router = get_router();
```

<hr />

### get_named_routes

**Description:**

Returns array of named routes.

See: [https://github.com/bayfrontmedia/route-it#getnamedroutes](https://github.com/bayfrontmedia/route-it#getnamedroutes)

**Parameters:**

- None

**Returns:**

- (array)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$named_routes = get_named_routes();
```

<hr />

### get_named_route

**Description:**

Returns URL of a single named route.

See: [https://github.com/bayfrontmedia/route-it#getnamedroute](https://github.com/bayfrontmedia/route-it#getnamedroute)

**Parameters:**

- `$name` (string)
- `$default = ''` (string): Default value to return if named route does not exist

**Returns:**

- (string)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
echo get_named_route('home');
```

<hr />

### get_translate

**Description:**

Get Translate instance from container.

See: [https://github.com/bayfrontmedia/translation](https://github.com/bayfrontmedia/translation)

**Parameters:**

- None

**Returns:**

- (`Bayfront\Translation\Translate`)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$translate = get_translate();
```

<hr />

### set_locale

**Description:**

Set locale.

See: [https://github.com/bayfrontmedia/translation#setlocale](https://github.com/bayfrontmedia/translation#setlocale)

**Parameters:**

- `$locale` (string)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
set_locale('es');
```

<hr />

### translate

**Description:**

Return the translation for a given string.

The string format is: `id.key`. Keys are in array dot notation, so they can be as deeply nested as needed.

Replacement variables should be surrounded in `{{ }}` in the original string.

If a translation is not found and `$default = NULL`, the original string is returned.

Returned value is filtered through the `translate` hook.

See: [https://github.com/bayfrontmedia/translation#get](https://github.com/bayfrontmedia/translation#get)

**Parameters:**

- `$string` (string)
- `$replacements = []` (array)
- `$default = NULL` (mixed): Default value to return if translation is not found

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\Translation\TranslationException`

**Example:**

```
$title = translate('dashboard.title');
```

<hr />

### say

**Description:**

Echos the translation for a given string.

Returned value is filtered through the `translate` hook.

See: [https://github.com/bayfrontmedia/translation#say](https://github.com/bayfrontmedia/translation#say)

**Parameters:**

- `$string` (string)
- `$replacements = []` (array)
- `$default = NULL` (mixed): Default value to return if translation is not found

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\Translation\TranslationException`

**Example:**

```
say('dashboard.title');
```

<hr />

### get_veil

**Description:**

Get Veil instance from container.

See: [https://github.com/bayfrontmedia/veil](https://github.com/bayfrontmedia/veil)

**Parameters:**

- None

**Returns:**

- (`Bayfront\Veil\Veil`)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
$veil = get_veil();
```

<hr />

### get_view

**Description:**

Get compiled template file as a string.

Returned value is filtered through the `veil.view` hook.

See: [https://github.com/bayfrontmedia/veil#getview](https://github.com/bayfrontmedia/veil#getview)

**Parameters:**

- `$file` (string): Path to file from base path, excluding file extension
- `$data = []` (array): Data to pass to view in dot notation
- `$minify = false` (bool): Minify compiled HTML?

**Returns:**

- (string)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\Veil\FileNotFoundException`

**Example:**

```
$html = get_view('/path/to/file');
```

<hr />

### view

**Description:**

Echo compiled template file.

Returned value is filtered through the `veil.view` hook.

See: [https://github.com/bayfrontmedia/veil#view](https://github.com/bayfrontmedia/veil#view)

**Parameters:**

- `$file` (string): Path to file from base path, excluding file extension
- `$data = []` (array): Data to pass to view in dot notation
- `$minify = false` (bool): Minify compiled HTML?

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\Veil\FileNotFoundException`

**Example:**

```
view('/path/to/file');
```

<hr />

### view_or_fallback

**Description:**

Echo compiled template file, or dispatch to fallback if not existing.

Returned value is filtered through the `veil.view` hook.

**NOTE:** This helper also requires the router service to be present in the container.

**Parameters:**

- `$file` (string): Path to file from base path, excluding file extension
- `$data = []` (array): Data to pass to view in dot notation
- `$minify = false` (bool): Minify compiled HTML?

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\NotFoundException`
- `Bayfront\RouteIt\DispatchException`

**Example:**

```
view_or_fallback('/path/to/file');
```