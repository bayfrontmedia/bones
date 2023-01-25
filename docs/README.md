# Bones documentation

## Installation

Please the [README](../README.md#installation) for a list of official Bones projects.

For manual installation instructions, see [bare installation](install/bare.md).

## File structure

The file structure for a Bones application is as follows:

```
/app
  /Controllers
  /Console/Commands
  /Events
  /Exceptions
  /Filters
  /Models
  /Services
/config
  /app.php
/public
  /storage
  .htaccess
  index.php
/resources
  bootstrap.php
/storage
  /app
  /public
.env
```

- `/app` - All of your app's namespaced classes reside here.
- `/config` - All of your app's configuration files reside here.
- `/public` - Public web root of the application.
- `/public/storage` - Symlink to `/storage/public`.
- `/resources` - All of your app's resources reside here. These include the `bootstrap.php` file,
as well as any resources you wish to add such as views, translations, or global helper functions.
- `/storage` - All locally stored files reside here. This includes files written by the app, publicly shared files, 
and any other files you wish to store.
- `.env` - All environment variables are saved here. 
**This file should never be made public or committed to your application's source control.**

## Configuration

### Environment variables

Add required environment variables as described in the [manual installation instructions](https://github.com/bayfrontmedia/bones/blob/dev/docs/install/bare.md#add-required-environment-variables).

### Configuration arrays

General application configuration is done via config files in the `/config` directory.
These files typically return an array.

The required configuration files are:

- `app.php`- [App configuration](usage/config.md)

Additional configuration files may be needed depending on which services you use.
You may also add your own configuration files as desired.

Config files can use the [getEnv](utilities/app.md#getenv) app utility to retrieve environment variables.
The [getConfig](utilities/app.md#getconfig) app utility can be used to retrieve config values throughout your app.

**Because the configuration files are typically committed to your application's source control, 
they should never contain sensitive information such as account credentials.**

## Usage

- [Bootstrap](usage/bootstrap.md)
- [App configuration](usage/config.md)
- [Console](usage/console.md)
- [Container](usage/container.md)
- [Controllers](usage/controllers.md)
- [Exceptions](usage/exceptions.md)
- [Models](usage/models.md)
- [Services](usage/services.md)

### Services

Bones will automatically place the following services into the container:

- [Events](services/events.md)
- [Filters](services/filters.md)
- [Response](services/response.md)

Depending on your installation configuration, Bones may use these place additional services into the container:

- [Db](services/db.md)
- [Filesystem](services/filesystem.md)
- [Logs](services/logs.md)
- [Router](services/router.md)
- [Scheduler](services/scheduler.md)
- [Veil](services/veil.md)

### Utilities

- [App](utilities/app.md)
- [Constants](utilities/constants.md)