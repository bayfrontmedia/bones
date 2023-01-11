# Command line

The [Symfony Console](https://github.com/symfony/console) library is used to manage command line functionality.

**Example usage:**

```
php bones
```

The `app.cli` event is fired immediately after the `app.bootstrap` event, and before the command is processed.

Command line functionality includes:

```shell
# Get app info
php bones about
php bones about --json

# List items in container
php bones container:list
php bones container:list --json

# Create a cryptographically secure key
php bones key:create

# Make a new controller
php bones make:controller
php bones make:controller --type=web

# Make a new exception
php bones make:exception

# Make a new model
php bones make:model

# Make a new service
php bones make:service
```