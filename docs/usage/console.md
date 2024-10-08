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
# Put Bones into maintenance mode
php bones down
# Comma-separated IP's to allow
php bones down --allow=1.1.1.1,2.2.2.2
# Message to be returned
php bones down --message="Message to be returned"

# Take Bones out of maintenance mode
php bones up

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

# Clear cache
php bones cache:clear
# Clear specific types of cache
php bones cache:clear --config --commands --events --filters

# List contents of cache
php bones cache:list
# List specific types of cache
php bones cache:list --type=config --type=commands --type=events --type=filters
# Return as JSON
php bones cache:list --json

# Save cache
php bones cache:save
# Save specific types of cache
php bones cache:save --config --commands --events --filters

# List contents of the service container
php bones container:list
# Return as JSON
php bones container:list --json

# List all event subscriptions
php bones event:list
# Subscriptions can be returned for specific events
php bones event:list --event=app.bootstrap --event=app.controller
# Subscriptions can be sorted by "event", "priority", or "subscription" (default)
php bones event:list --sort=event
# Return as JSON
php bones event:list --json

# List all filter subscriptions
php bones filter:list
# Subscriptions can be returned for specific values
php bones filter:list --value=router.parameters
# Subscriptions can be sorted by "filter", "priority", or "subscription" (default)
php bones filter:list --sort=filter
# Return as JSON
php bones filter:list --json

# Set the APP_KEY environment variable to a cryptographically secure key if not already existing
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
# Migrate down with specific database name
php bones migrate:down --db=DATABASE_NAME
# Rollback database migrations to a specific batch
php bones migrate:down --batch=BATCH
# Use --force to skip confirmation prompt
php bones migrate:down --force

# Run all pending database migrations
php bones migrate:up 
# Migrate up with specific database name
php bones migrate:up --db=DATABASE_NAME
# Use --force to skip confirmation prompt
php bones migrate:up --force

# List all migrations which have ran
php bones migration:list
# List all migrations for a specific database name
php bones migration:list --db=DATABASE_NAME
# Migrations can be sorted by "id", "name", or "batch" (default)
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

## Creating a new command

The easiest way of creating a new custom console command is from the command line:

```
php bones make:command NAME
```

For more information, see: [https://symfony.com/doc/current/console#creating-a-command](https://symfony.com/doc/current/console#creating-a-command)

### Caching commands

Performance can be improved by caching commands.
This should only be done in a production environment, where custom commands will remain unchanged.

Commands can be cached with the `php bones cache:save --commands` console command.

For more information, the command line documentation above.