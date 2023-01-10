# Bones documentation

## Terminology

- **Controller:** Similar to an air traffic controller, a controller is responsible for gathering and handling all incoming and outgoing
data.
- **Helper:** A helper is a global function (or group of functions) introduced to the app by including a specific file.
- **Service:** A service is a class or library not intended to be used or maintained outside a specific Bones application.
- **Model:** A model is a class to be used from within a controller to provide required data needed to build a response.
- **Route:** A route is the destination for an incoming HTTP request.
- **View:** A view is a file used to build the HTML that will be returned to the browser. They are either called from within a controller, or directly from a defined route.

## File structure

The file structure for building an app using Bones is as follows:

```
/app
  /Controllers
  /Exceptions
  /Models
  /Services
/config
  /app.php
  /filesystem.php
  /router.php
/public
  .htaccess
  index.php
/resources
  bootstrap.php
  cli.php
  events.php
  filters.php
  routes.php
/storage
.env
```

- `/app`- All of your app's namespaced classes reside here. These include controllers, exceptions, models and services.
- `/config`- All of your app's configuration files reside here.
- `/public`- Public web root of the application. 
- `/resources`- All of your app's resources reside here. These include the bootstrap and command line file, cron jobs, events, filters and routes. Resources may also include global helper functions, translations, views, or any other custom resource you choose to use.
- `/storage`- All locally stored files reside here. This includes files written by the app, publicly shared files, as well as any other files you wish to store. 
- `.env`- All environment variables are saved here. **This file should never be made public or committed to your application's source control.**

## Configuration

### Environment variables

Environment variables are set in the `.env` file in the root directory. 

The [get_env](helpers.md#get_env) helper can be used to retrieve environment variables.

**Because the `.env` file contains sensitive information, 
it should never be made public or committed to your application's version control**

**Example:**

```
APP_KEY=SECURE_APP_KEY
APP_DEBUG_MODE=true
APP_ENVIRONMENT=development
APP_TIMEZONE=America/New_York
APP_EVENTS_ENABLED=true
APP_FILTERS_ENABLED=true

ROUTER_HOST=example.com
ROUTER_ROUTE_PREFIX=
```

### Configuration arrays

General application configuration is done via config files in the `/config` directory.
These files typically return an array.

The required configuration files are:
 
- `app.php`- [App configuration](app.md)
- `filesystem.php`- [Filesystem](libraries/filesystem.md)
- `router.php`- [Router](libraries/router.md)

Additional configuration files may be needed depending on the services you use.
You may also add your own configuration files as desired.

Config files can use the [get_env](helpers.md#get_env) helper to retrieve environment variables.
The [get_config](helpers.md#get_config) helper can be used to retrieve config values.

**Because the configuration files are typically committed to your application's version control, they should never contain sensitive information such as account credentials.**

## Documentation

- [App configuration](app.md)
- [Bootstrap](bootstrap.md)
- [Constants](constants.md)
- [Container](container.md)
- [Controllers](controllers.md)
- [Exceptions](exceptions.md)
- [Helpers](helpers.md)
- [Models](models.md)
- [Services](services.md)

### Libraries

Bones may automatically place certain libraries into the services container.
These include:

- [Command line](libraries/cli.md)
- [Cron jobs](libraries/cron.md)
- [Database](libraries/database.md)
- [Filesystem](libraries/filesystem.md)
- [Hooks](libraries/hooks.md)
- [HTTP Response](libraries/http-response.md)
- [Logs](libraries/logs.md)
- [Router](libraries/router.md)
- [Translation](libraries/translation.md)
- [Views](libraries/views.md)