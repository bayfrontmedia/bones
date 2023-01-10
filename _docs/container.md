# Services container

The services container is responsible for resolving and optionally storing class instances using dependency injection. 

The Bones services container can be retrieved via the [get_container](helpers.md#get_container) helper. 
Services inside the container can be retrieved via the [get_from_container](helpers.md#get_from_container) helper.

Bones utilizes a variety of libraries to provide all of its required services. 
When Bones is initialized, all required services are bound to the container and available for use, referenced by their ID (see table below).

Additional documentation for each library can be found by visiting its URL.

| Service                                     | Library                                                                   | Container ID | 
|---------------------------------------------|---------------------------------------------------------------------------|--------------|
| Container                                   | [Container](https://github.com/bayfrontmedia/container)                   |              |
| [HTTP Response](libraries/http-response.md) | [PHP HTTP Response](https://github.com/bayfrontmedia/php-http-response)   | `response`   |
| [Hooks](libraries/hooks.md)                 | [PHP Hooks](https://github.com/bayfrontmedia/php-hooks)                   | `hooks`      |
| [Router](libraries/router.md)               | [Route It](https://github.com/bayfrontmedia/route-it)                     | `router`     |
| [Database](libraries/database.md)*          | [Simple PDO](https://github.com/bayfrontmedia/simple-pdo)                 | `db`         |
| [Filesystem](libraries/filesystem.md)*      | [Filesystem Factory](https://github.com/bayfrontmedia/filesystem-factory) | `filesystem` |
| [Logs](libraries/logs.md)*                  | [Monolog Factory](https://github.com/bayfrontmedia/monolog-factory)       | `logs`       |
| [Translation](libraries/translation.md)*    | [Translation](https://github.com/bayfrontmedia/translation)               | `translate`  |
| [Views](libraries/views.md)*                | [Veil](https://github.com/bayfrontmedia/veil)                             | `veil`       |
| [Cron jobs](libraries/cron.md)              | [Cron Scheduler](https://github.com/bayfrontmedia/cron-scheduler)         | `cron`       |
| [Command line](libraries/cli.md)            | [Climate](https://github.com/thephpleague/climate)                        | `cli`        |

**NOTE:**

\* These services are optional, and will only exist in the container if the associated config file exists in the `/config` directory.

The `cron` and `cli` services will only exist in the container when running in their respective environments.