# Utilities: App

The app utility has the namespace `Bayfront\Bones\Application\Utilities\App` and allows you to easily use 
and reference data related to the application.
All methods are static.

## Constants

The following constants can be used to check against the [environment](#environment):

- `ENV_DEV` (dev)
- `ENV_STAGING` (staging)
- `ENV_QA` (qa)
- `ENV_PROD` (prod)

The following constants can be used to check against the [interface](#getinterface):

- `INTERFACE_CLI` (CLI)
- `INTERFACE_HTTP` (HTTP)

## Methods

- [environment](#environment)
- [isDebug](#isdebug)
- [getInterface](#getinterface)
- [getEnv](#getenv)
- [envHas](#envhas)
- [getConfig](#getconfig)
- [basePath](#basepath)
- [publicPath](#publicpath)
- [configPath](#configpath)
- [resourcesPath](#resourcespath)
- [storagePath](#storagepath)
- [createKey](#createkey)
- [getElapsedTime](#getelapsedtime)
- [getBonesVersion](#getbonesversion)
- [getContainer](#getcontainer)
- [make](#make)
- [get](#get)
- [abort](#abort)

<hr />

### environment

**Description:**

Return app environment value.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```php
if (App::environment() == App::ENV_PROD) {
    // Do something
}
```

<hr />

### isDebug

**Description:**

Is app in debug mode?

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```php
if (App::isDebug()) {
    // Do something
}
```

<hr />

### getInterface

**Description:**

Return app interface.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```php
if (App::getInterface() == App::INTERFACE_HTTP) {
    // Do something
}
```

<hr />

### getEnv

**Description:**

Return value of environment variable, or default value if not existing.

Strings "true", "false" and "null" will be cast to their respective types.

NOTE: This method should rarely be used outside a config file.

**Parameters:**

- `$key` (string)
- `$default = null` (mixed): Default value to return if not existing

**Returns:**

- (mixed)

**Example:**

```php
App::getEnv('APP_KEY');
```

<hr />

### envHas

**Description:**

Does environment variable exist?

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)

**Example:**

```php
if (App::envHas('EXAMPLE_VAR')) {
    // Do something
}
```

<hr />

### getConfig

**Description:**

Returns value from a configuration array key using dot notation,
with the first segment being the filename. (e.g.: filename.key)

**Parameters:**

- `$key` (string)
- `$default = null` (mixed): Default value to return if not existing

**Returns:**

- (mixed)

**Example:**

The following example would return the value of the `timezone` key in `config/app.php`:

```php
$timezone = App::getConfig('app.timezone');
```

<hr />

### basePath

**Description:**

Return base path.

**Parameters:**

- `$path = ''` (string)

**Returns:**

- (string)

**Example:**

```php
$path = App::basePath('/path/from/base');
```

<hr />

### publicPath

**Description:**

Return public path.

**Parameters:**

- `$path = ''` (string)

**Returns:**

- (string)

**Example:**

```php
$path = App::publicPath('/path/from/public');
```

<hr />

### configPath

**Description:**

Return config path.

**Parameters:**

- `$path = ''` (string)

**Returns:**

- (string)

**Example:**

```php
$path = App::configPath('/path/from/config');
```

<hr />

### resourcesPath

**Description:**

Return resources path.

**Parameters:**

- `$path = ''` (string)

**Returns:**

- (string)

**Example:**

```php
$path = App::resourcesPath('/path/from/resources');
```

<hr />

### storagePath

**Description:**

Return storage path.

**Parameters:**

- `$path = ''` (string)

**Returns:**

- (string)

**Example:**

```php
$path = App::storagePath('/path/from/storage');
```

<hr />

### createKey

**Description:**

Create a cryptographically secure key of random bytes.

**Parameters:**

- `$characters = 32` (int): Number of characters of binary data

**Returns:**

- (string)

**Throws:**

- `\Exception`

**Example:**

```php
$key = App::createKey();
```

**Console command:**

A key can also be created using the [console command](../usage/console.md) `php bones key:create`.

<hr />

### getElapsedTime

**Description:**

Return elapsed time in seconds since Bones was instantiated.

**Parameters:**

- `$timestamp = 0` (float): Uses current time if `0`
- `$decimals = 3` (int)

**Returns:**

- (string)

**Example:**

```php
$elapsed = App::getElapsedTime();
```

<hr />

### getBonesVersion

**Description:**

Return Bones version.

**Parameters:**

- None

**Returns:**

- (string)


**Example:**

```php
$version = App::getBonesVersion();
```

<hr />

### getContainer

**Description:**

Get container instance.

**Parameters:**

- None

**Returns:**

- `Bayfront\Container\Container`

**Example:**

```php
$container = App::getContainer();
```

<hr />

### make

**Description:**

Use the container to make and return a new class instance,
automatically injecting dependencies which exist in the container.

**Parameters:**

- `$class` (string): Fully namespaced class name
- `$params = []` (array): Additional parameters to pass to the class constructor

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\ContainerException`
- `Bayfront\Container\NotFoundException`

**Example:**

```php
class ClassName {

    protected $service;
    protected $config;
    
    public function __construct(AnotherService $service, array $config)
    {
        $this->service = $service;
        $this->config = $config;
    }

}

$service = App::make('Fully\Namespaced\ClassName', [
    'config' => []
])
```

<hr />

### get

**Description:**

Get an entry from the container by its ID or alias.

**Parameters:**

- `$id` (string)

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```php
$service = $container->get('Fully\Namespaced\ClassName');
```

<hr />

### abort

**Description:**

Abort script execution by throwing an `HttpException` and send response message.

If no message is provided, the phrase for the HTTP status code will be used.

**Parameters:**

- `$status_code` (int): HTTP status code for response
- `$message = ''` (string): Response message
- `$headers = []` (array): Key/value pairs of headers to be sent with the response

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\HttpException`
- `Bayfront\Container\ContainerException`
- `Bayfront\HttpResponse\InvalidStatusCodeException`

**Example:**

```php
App::abort(403);
```