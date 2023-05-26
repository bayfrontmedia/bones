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
php bones about:bones
# Return as JSON
php bones about:bones --json

# List all registered aliases
php bones alias:list
# Aliases can be sorted by "class", "used" (if in container) or "alias" (default)
php bones alias:list --sort=class
# Return as JSON
php bones alias:list --json

# Manage API service (scheduler, db, and router must exist)
# Manage API tenant
php bones api:manage:tenant
# Manage API user
php bones api:manage:user

# List contents of the service container
php bones container:list
# Return as JSON
php bones container:list --json

# List all event subscriptions
php bones event:list
# Subscriptions can be returned for specific events
php bones event:list --event=app.bootstrap --event=app.controller
# Subscriptions can be sorted by "event", "priority", or "subscriber" (default)
php bones event:list --sort=event
# Return as JSON
php bones event:list --json

# List all filter subscriptions
php bones filter:list
# Subscriptions can be returned for specific values
php bones filter:list --value=router.parameters
# Subscriptions can be sorted by "filter", "priority", or "subscriber" (default)
php bones filter:list --sort=filter
# Return as JSON
php bones filter:list --json

# Set the APP_KEY environment variable to a cryptographically secure key
php bones install:key

## Install an optional service
php bones install:service --[OPTION]
## Service options include:
--db --router --scheduler --veil

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

# Create a cryptographically secure key
php bones make:key

# Create a database migration
php bones make:migration NAME

# Create a new model
php bones make:model NAME

# Create a new service
php bones make:service NAME

# Rollback database migrations
php bones migrate:down
# Rollback database migrations to a specific batch
php bones migrate:down --batch=BATCH
# Use --force to skip confirmation prompt
php bones migrate:down --force

# Run all pending database migrations
php bones migrate:up 
# Use --force to skip confirmation prompt
php bones migrate:up --force

# List all migrations which have ran
php bones migration:list
# Migrations can be sorted by "id", "migration", or "batch" (default)
php bones migration:list --sort=id
# Return as JSON
php bones migration:list --json

# List all routes
php bones route:list
# Routes can be returned for specific request methods:
# NOTE: The additional method "named" will return all named routes
php bones route:list --method=get --method=named
# Routes can be returned for specific hosts:
php bones route:list --host=example.com
# Routes can be sorted by "host", "path", "name", "destination" or "method" (default)
php bones route:list --sort=path
# Return as JSON
php bones route:list --json

# List all scheduled jobs
php bones schedule:list
# Schedules can be sorted by "schedule", "prev", "next" or "name" (default)
php bones schedule:list --sort=next
# Return as JSON
php bones schedule:list --json

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