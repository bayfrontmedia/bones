# Services container

The [Container](https://github.com/bayfrontmedia/container) library is used as the services container for Bones.
The services container is responsible for resolving and optionally storing class instances using dependency injection.

The container can be retrieved via the [get_container](helpers.md#get_container) helper. 
Services inside the container can be retrieved via the [get_from_container](helpers.md#get_from_container) helper.

Both the `in_container` and `get_from_container` [helper functions](helpers.md) will resolve classes either by their class name,
or their [alias](app.md#app-configuration), if existing.

Bones utilizes a variety of libraries to provide all of its required services. 
When Bones is initialized, all required services are bound to the container and available for use, 
referenced by their class name and alias (see table below).

| Service                                     | Class                                     | Alias       | 
|---------------------------------------------|-------------------------------------------|-------------|
| [HTTP Response](libraries/http-response.md) | `Bayfront\HttpResponse\Response`          | `response`  |
| [Hooks](libraries/hooks.md)                 | `Bayfront\Hooks\Hooks `                   | `hooks`     |
| [Scheduler](libraries/scheduler.md)         | `Bayfront\CronScheduler\Cron`             | `schedule`  |
| [Router](libraries/router.md)               | `Bayfront\RouteIt\Router`                 | `router`    |
| [Database](libraries/database.md)*          | `Bayfront\PDO\DbFactory`                  | `db`        |
| [Filesystem](libraries/filesystem.md)*      | `Bayfront\Filesystem\Filesystem`          | `files`     |
| [Logs](libraries/logs.md)*                  | `Bayfront\MonologFactory\LoggerFactory`   | `logs`      |
| [Translation](libraries/translation.md)*    | `Bayfront\Translation\Translate`          | `translate` |
| [Views](libraries/views.md)*                | `Bayfront\Veil\Veil`                      | `veil`      |
| [CLI](libraries/cli.md)**                   | `Symfony\Component\Console\Application`   | `console`   |

**NOTE:**

\* These services are optional, and will only exist in the container if the associated config file exists in the `/config` directory.

\** The `console` service will only exist in the container when the app interface is `CLI`.

Alias names must be unique, although the same class can be used for multiple aliases.
Alias names used by Bones (see table above) are protected and cannot be overwritten or reused by your app.