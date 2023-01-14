# App configuration

The `/config/app.php` file is used to define the general configuration of your application.
This file is required by Bones.

**Example:**

```
return [
    'namespace' => 'App\\', // Namespace for the app/ directory, as specified in composer.json
    'key' => get_env('APP_KEY'), // Unique to the app, not to the environment
    'debug_mode' => get_env('APP_DEBUG_MODE'),
    'environment' => get_env('APP_ENVIRONMENT'), // e.g.: "development", "staging", "production"
    'timezone' => get_env('APP_TIMEZONE'), // See: https://www.php.net/manual/en/timezones.php
    'actions' => [
        'cache' => get_env('APP_ENVIRONMENT') !== 'dev',
        'autoload' => true,
        'load' => []
    ],
    'filters' => [
        'cache' => get_env('APP_ENVIRONMENT') !== 'dev',
        'autoload' => true,
        'load' => []
    ],
    'aliases' => []
];
```

## namespace

This should be the same as the PSR-4 namespace defined in your app's `composer.json` file for autoloading classes, and is the namespace used for all classes residing in the `/app` directory.

## key

This should be a cryptographically secure key which may be used for a variety of tasks, for example, when signing a token or hashing a password.
Since this value is required by Bones, it may be used throughout your app, for example, in your own models or services.

A key can be created using the [create_key](helpers.md#create_key) helper, or via the [command line](libraries/cli.md).

**Although the app key is unique to the app, storing it in the `.env` file ensures it will never be made public.**

## debug_mode

Value must be a `boolean`. When set to `true`, errors and exceptions thrown by Bones will return additional potentially sensitive data to assist in tracing its cause.

## environment

This setting is not directly used by Bones. Rather, it exists to help differentiate between different environments, such as development, staging or production.
This can prove helpful, for example, when defining routes or for logging purposes.

## timezone

A valid [timezone](https://www.php.net/manual/en/timezones.php) should be defined.

## actions

An array used to manage queuing [actions](actions.md).
First, Bones will attempt to automatically load all actions if the `actions.autoload` config value is `true`.
If `false`, Bones will only load actions whose fully qualified class names exist in the `actions.load` config array.

When `actions.cache` is `true`, all instantiated actions will be cached.

For more information, see [actions](actions.md).

## filters

An array used to manage queuing [filters](filters.md).
These are loaded in the same manner as [actions](#actions).

## aliases

An array used to define aliases for classes in the container. Typically, classes are saved in the container using its
fully namespaced class name. Aliases allow you to use the [helper functions](helpers.md) `in_container` and `get_from_container` 
using a more user-friendly ID.

Each array key is a unique alias whose value is a string of the fully namespaced class name matching an ID
which would exist in the container.

Example:

```php
[
    'alias' => 'Namespaced\ClassName'
]
```

Bones utilizes its own protected aliases for some of [its services](container.md).
A list of aliases can be returned with the `php bones alias:list` command.
For more information, see [command line](libraries/cli.md).