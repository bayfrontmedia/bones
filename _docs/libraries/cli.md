# Command line

The [Symfony Console](https://github.com/symfony/console) library is used to manage command line functionality.

**Example usage:**

```
php bones
```

The `app.cli` event is fired when the app interface is `CLI`, immediately after the Symfony Console application is added to the container as `console`.
The Symfony Console application is passed as a parameter to this event.

Command line functionality includes:

```shell
# Get app info
php bones about
php bones about --json

# List items in container
php bones container:list
php bones container:list --json

# Install Bones
php bones install:bare
# To install optional services, use the following options:
--db --filesystem --logs --translation --veil

# Create a cryptographically secure key
php bones key:create

# Make a new console command
php bones make:command NAME

# Make a new controller
php bones make:controller NAME
php bones make:controller NAME --type=web

# Make a new exception
php bones make:exception NAME

# Make a new model
php bones make:model NAME

# Make a new service
php bones make:service NAME

# Run all scheduled jobs
php bones schedule:run
```

## Creating a new command

The easiest way of creating a new custom console command is from the command line:

```
php bones make:command NAME
```

Once the command is created, it needs to be registered with the console application.
This is easily done using the `app.cli` event:

```php
function event_app_cli(Application $console)
{
    $console->add(new CustomCommandName());
}
add_event('app.cli', 'event_app_cli');
```

For more information, see: [https://symfony.com/doc/current/console#creating-a-command](https://symfony.com/doc/current/console#creating-a-command)