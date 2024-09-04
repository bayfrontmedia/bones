# Bones documentation

## Installation

It is recommended to install a supported [Bones project](../README.md#installation) to help you get started.

For manual installation instructions, see [manual installation](install/manual.md).

## File structure

The file structure for a Bones application is as follows:

```
/app
  /Controllers
  /Console/Commands
  /Events
  /Exceptions
  /Filters
  /Migrations
  /Models
  /Services
  /Utilities
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
  /bones
  /public
.env
```

- `/app` - All of your app's namespaced classes reside here.
- `/app/Migrations` - Optional location for any [database migrations](services/db.md#migrations).
- `/config` - All of your app's configuration files reside here.
- `/public` - Public web root of the application.
- `/public/storage` - Symlink to `/storage/public`.
- `/resources` - All of your app's resources reside here. These include the `bootstrap.php` file,
as well as any resources you wish to add such as views, translations, or other utilities.
- `/storage` - All locally stored files reside here. This includes files written by the app, written by Bones, 
publicly shared files, and any other files you wish to store.
- `.env` - All environment variables are saved here. 
**This file should never be made public or committed to your application's source control.**

## Configuration

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

- [Encryptor](services/encryptor.md)
- [Events](services/events.md)
- [Filters](services/filters.md)
- [Response](services/response.md)

Depending on your installation configuration, Bones may use these place additional services into the container:

- [Db](services/db.md)
- [Router](services/router.md)
- [Scheduler](services/scheduler.md)
- [Veil](services/veil.md)

Other commonly used helpful services you may wish to bootstrap into your app include:

- [Multi-Filesystem](https://github.com/bayfrontmedia/multi-filesystem): An easy-to-use library used to manage multiple Flysystem adapters from a single class.
- [Multi-Logger](https://github.com/bayfrontmedia/multi-logger): An easy-to-use library used to manage multiple Monolog channels from a single class.

The following services are included with Bones:

- None.

### Utilities

- [App](utilities/app.md)
- [Constants](utilities/constants.md)