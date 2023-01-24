# Usage: Console

The [Symfony Console](https://github.com/symfony/console) library is used to manage command line functionality.

## Command line

**Example usage:**

```
php bones
```

The [app.cli](../services/events.md) event is executed when the app interface is `CLI`.
The Symfony Console application is passed as a parameter to this event.

Command line functionality includes:

```shell
# Information about this Bones application
php bones about:app
# Return as JSON
php bones about:app --json

# List all registered aliases
php bones alias:list
# Return as JSON
php bones alias:list --json
# Aliases can be sorted by "class", "used" (if in container) or "alias" (default)
php bones alias:list --sort=class

# List contents of the service container
php bones container:list
# Return as JSON
php bones container:list --json
# Services can be sorted by "alias" or "id" (default)

# Deploy application
# TARGET examples: origin/master (branch), v1.0.0 (tag), or commit hash
php bones deploy:app TARGET
# Deploy app and create backup of current files
php bones deploy:app TARGET --backup

# Purge deployment backups
# --days= Purge backups older than number of days
# --limit= Purge oldest backups over limit
php bones deploy:purge --days=90 --limit=50
# Purge all backups
php bones deploy:purge --limit=0

# List all event subscriptions
php bones event:list
# Return as JSON
php bones event:list --json
# Subscriptions can be returned for specific events
php bones event:list --event=app.bootstrap --event=app.controller
# Subscriptions can be sorted by "event", "priority", or "subscriber" (default)
php bones event:list --sort=event

# List all filter subscriptions
php bones filter:list
# Return as JSON
php bones filter:list --json
# Subscriptions can be returned for specific values
php bones filter:list --value=router.parameters
# Subscriptions can be sorted by "filter", "priority", or "subscriber" (default)
php bones filter:list --sort=filter

# Install Bones (bare)
php bones install:bare
# To install optional services, use the following options:
--db --filesystem --logs --router --scheduler --veil

## Install an optional service
php bones install:service --[OPTION]
## Service options include:
--db --filesystem --logs --router --scheduler --veil

# Create a cryptographically secure key
php bones key:create

# Create a new console command
php bones make:command NAME

# Create a new controller
php bones make:controller NAME

# Create a new event subscriber
php bones make:event NAME

# Create a new exception
php bones make:exception NAME

# Create a new filter subscriber
php bones make:filter NAME

# Create a new model
php bones make:model NAME

# Create a new service
php bones make:service NAME

# List all routes
php bones route:list
# Return as JSON
php bones route:list --json
# Routes can be returned for specific request methods:
# NOTE: The additional method "named" will return all named routes
php bones route:list --method=get --method=named
# Routes can be returned for specific hosts:
php bones route:list --host=example.com
# Routes can be sorted by "host", "path", "name", "destination" or "method" (default)
php bones route:list --sort=path

# List all scheduled jobs
php bones schedule:list
# Return as JSON
php bones schedule:list --json
# Schedules can be sorted by "schedule", "prev", "next" or "name" (default)
php bones schedule:list --sort=next

# Run all scheduled jobs which are due
php bones schedule:run
```

### Creating a new command

The easiest way of creating a new custom console command is from the command line:

```
php bones make:command NAME
```

How commands are loaded depends on the [app config settings](config.md#commands).

For more information, see: [https://symfony.com/doc/current/console#creating-a-command](https://symfony.com/doc/current/console#creating-a-command)