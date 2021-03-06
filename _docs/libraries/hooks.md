# Hooks

The [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) library is used to manage events and filters, and is added to the services container as `hooks`.

Events and filters will only be enabled if specified in the `/config/app.php` file.

**Example:**
```
'events_enabled' => get_env('APP_EVENTS_ENABLED'),
'filters_enabled' => get_env('APP_FILTERS_ENABLED')
``` 

Although not necessary to store these values in a the `.env` file, it may be convenient to disable the hooks in a development environment. 

## Events

### Default Bones events

Default Bones events, in order of execution:

- `bones.init`: Executes just after Bones has initialized.
- `app.cron`: Executes as the last event when running as a cron job.
- `app.cli`: Executes as the last event when running from the command line.
- `app.bootstrap`: Executes just after the app's `/resources/bootstrap.php` file has been loaded.
- `app.controller`: Executes when a controller is constructed.
- `bones.exception`: Executes when a `Bayfront\Bones\Exceptions\BonesException` is thrown. This event accepts two parameters: the thrown exception object and the [HTTP Response](../container.md) library.
- `bones.shutdown`: Executes as the last event.

The php-hooks library also has its own default events:

- `always`: Always executed whenever `doEvent()` is called, regardless of the name.
- `destruct`: Executes when the PHP Hooks library destructs.

### Managing event hooks

You can manage event hooks in the `/resources/events.php` file. 
This file is required by Bones.

The PHP Hooks library will automatically be available in this file as `$hooks`.

## Filters

### Default Bones filters

- `logs.context`: Used by the [Logs helpers](../helpers.md#services-helpers) to filter the log context array.
- `router.parameters`: Used to inject global parameters into every router destination.
- `translate`: Used by some [Translate helpers](../helpers.md#services-helpers) to filter translated strings.
- `veil.view`: Used by some [Veil helpers](../helpers.md#services-helpers) to filter the returned HTML from a view.

### Managing filters

You can manage filters in the `/resources/filters.php` file.
This file is required by Bones.

The PHP Hooks library will automatically be available in this file as `$hooks`.