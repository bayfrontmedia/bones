# Utilities: Constants

The constants utility allows you to easily define and retrieve constants to be used throughout the app.
All methods are static.

## Bones constants

Bones defines several constants by default, which are listed below.
Instead of using these constants directly, it is suggested to use the associated [app utility functions](app.md).

- `BONES_START` - Timestamp of when the app instantiated (in `microtime`)
- `APP_BASE_PATH` - Path to the application's base directory (`/`)
- `APP_PUBLIC_PATH` - Path to the application's public directory (`/public`)
- `APP_INTERFACE` - App interface. One of `HTTP` or `CLI`
- `APP_CONFIG_PATH` - Path to the application's `/config` directory
- `APP_RESOURCES_PATH` - Path to the application's `/resources` directory
- `APP_STORAGE_PATH` - Path to the application's `/storage` directory
- `BONES_BASE_PATH` - Base path to Bones
- `BONES_RESOURCES_PATH` - Path to the Bones `/resources` directory
- `BONES_VERSION` - Currently installed Bones version

## Methods

- [isDefined](#isdefined)
- [define](#define)
- [get](#get)
- [getAll](#getall)
- [remove](#remove)

<hr />

### isDefined

**Description:**

Is constant already defined?

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)

**Example:**

```php
if (Constants::isDefined('EXAMPLE')) {
    // Do something
}
```

<hr />

### define

**Description:**

Define constant.

**Parameters:**

- `$key` (string)
- `$value` (mixed)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\ConstantAlreadyDefinedException`

**Example:**

```php
Constants::define('EXAMPLE', 'value');
```

<hr />

### get

**Description:**

Get constant.

**Parameters:**

- `$key` (string)

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Bones\Exceptions\UndefinedConstantException`

**Example:**

```php
$value = Constants::get('EXAMPLE');
```

<hr />

### getAll

**Description:**

Return all defined constants.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```php
$constants = Constants::getAll();
```

<hr />

### remove

**Description:**

Remove constant definition.

**Parameters:**

- `$key` (string)

**Returns:**

- (void)

**Example:**

```php
Constants::remove('EXAMPLE');
```