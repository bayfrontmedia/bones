# Usage: App config

The `/config/app.php` file is used to define the general configuration of your application.
This file is required by Bones.

Example:

```php
use Bayfront\Bones\Application\Utilities\App;

return [
    'namespace' => 'App\\', // Namespace for the app/ directory, as specified in composer.json
    'key' => App::getEnv('APP_KEY'), // Unique to the app, not to the environment
    'debug' => App::getEnv('APP_DEBUG'),
    'environment' => App::getEnv('APP_ENVIRONMENT'), // e.g.: "dev", "staging", "qa", "prod"
    'timezone' => App::getEnv('APP_TIMEZONE'), // See: https://www.php.net/manual/en/timezones.php
    'events' => [
        'autoload' => true,
        'load' => []
    ],
    'filters' => [
        'autoload' => true,
        'load' => []
    ],
    'commands' => [
        'autoload' => true,
        'load' => []
    ]
];
```

## namespace

This should be the same as the PSR-4 namespace defined in your app's `composer.json` file for autoloading classes, and is the namespace used for all classes residing in the `/app` directory.

## key

This should be a cryptographically secure key which may be used for a variety of tasks, 
for example, when signing a token or hashing a password.
Since this value is required by Bones, it may be used throughout your app, for example, in your own models or services.

A key can be created using the [createKey](../utilities/app.md#createkey) app utility, or via the [command line](console.md).

**Although the app key is unique to the app, storing it in the `.env` file ensures it will never be made public.**

## debug

Value must be a `boolean`. When set to `true`, errors and exceptions thrown by Bones will return 
additional potentially sensitive data to assist in tracing its cause.

## environment

This setting exists to help differentiate between different environments, such as development, staging or production.
This can prove helpful, for example, when defining routes or for logging purposes.

Suggested environments are:

- `dev`
- `staging`
- `qa`
- `prod`

For continuity purposes, the [app utility](../utilities/app.md) contains constants to reference these environments.

## timezone

A valid [timezone](https://www.php.net/manual/en/timezones.php) should be defined.

## events

The `autoload` key accepts a `boolean` value, and is used to specify whether to autoload all the event subscribers
located in the `app/Events` directory.

When set to `false`, only those event subscribers whose fully namespaced class names are listed in the `load` array
will be loaded.

For more information, see [events](../services/events.md).

## filters

The `autoload` key accepts a `boolean` value, and is used to specify whether to autoload all the filter subscribers
located in the `app/Filters` directory.

When set to `false`, only those filter subscribers whose fully namespaced class names are listed in the `load` array
will be loaded.

For more information, see [filters](../services/filters.md).

## commands

The `autoload` key accepts a `boolean` value, and is used to specify whether to autoload all the console commands
located in the `app/Console/Commands` directory.

When set to `false`, only those console commands whose fully namespaced class names are listed in the `load` array
will be loaded.

For more information, see [console](../usage/console.md).