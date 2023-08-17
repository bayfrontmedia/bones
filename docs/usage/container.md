# Usage: Container

The service container is responsible for storing and resolving dependencies for your application.

Bones uses the [Container library](https://github.com/bayfrontmedia/container) as its service container.

Bones utilizes a variety of services within the framework.
When Bones is started, all required services are bound to the container and available for use, 
referenced by their alias (see table below).

| Service                                  | ID                                                  | Alias       |
|------------------------------------------|-----------------------------------------------------|-------------|
| [Events](../services/events.md)          | `Bayfront\Bones\Application\Services\EventService`  | `events`    |
| [Filters](../services/filters.md)        | `Bayfront\Bones\Application\Services\FilterService` | `filters`   |
| [Encryptor](../services/encryptor.md)    | `Bayfront\Encryptor\Encryptor`                      | `encryptor` |
| [HTTP Response](../services/response.md) | `Bayfront\HttpResponse\Response`                    | `response`  |
| [Database](../services/db.md)*           | `Bayfront\PDO\Db`                                   | `db`        |
| [Router](../services/router.md)*         | `Bayfront\RouteIt\Router`                           | `router`    |
| [Scheduler](../services/scheduler.md)*   | `Bayfront\CronScheduler\Cron`                       | `scheduler` |
| [Veil](../services/veil.md)*             | `Bayfront\Veil\Veil`                                | `veil`      |

**NOTE:**

\* These services are optional, and will only exist in the container if the associated config file exists in the `/config` directory.

One of the main purposes of Bones is to be as minimal as possible. 
For this reason, you don’t have to use any of the optional services - Bones doesn't even require a router! 
Add any service you'd like to use to the container in the [bootstrap file](bootstrap.md).

## Interacting with the container

Having to interact with the container instance directly should be rare,
as most direct interaction should be done in the `/resources/bootstrap.php` file.

However, if the need should arise, you can interact with the container 
using the following app utilities:

- [getContainer](../utilities/app.md#getcontainer)
- [make](../utilities/app.md#make)
- [get](../utilities/app.md#get)

## Console commands

The following [console commands](console.md) can be used with relation to the container:

- `php bones alias:list`
- `php bones container:list`