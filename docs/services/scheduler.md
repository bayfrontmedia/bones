# Services: Scheduler

The [Cron Scheduler](https://github.com/bayfrontmedia/cron-scheduler) library is used to manage scheduled jobs,
and is added to the service container with alias `scheduler` when a valid configuration array exists at `/config/scheduler.php`.

**Example:**

```php
use Bayfront\Bones\Application\Utilities\App;

return [
    'lock_file_path' => App::storagePath('/app/temp'),
    'output_file' => App::storagePath('/app/cron/cron-' . date('Y-m-d') . '.txt')
];
```

Scheduled jobs are typically setup as a cron job on the server.

**Example crontab entry:**

```
* * * * * /path/to/php/bin cd /path/to/your/app && php bones schedule:run >> /dev/null 2>&1
```

The `app.schedule.start` event is executed just before the jobs are ran. 
The Cron Scheduler instance is passed as a parameter.

The `app.schedule.end` event is executed just after the jobs are completed.
The response of the scheduler's [run method](https://github.com/bayfrontmedia/cron-scheduler#run) is passed as a parameter

Although scheduled jobs do not have to be added until the `app.schedule.start` event,
subscribing them to the `app.cli` event will enable them to be shown when using the `php bones schedule:list` [console command](#console-commands).

## Installation

The scheduler service can be installed with the `php bones install:service --scheduler` [console command](../usage/console.md).

Installing the scheduler service will perform the following actions:

## Add config file

A config file will be added to `/config/scheduler.php` (See above example)

### Create event subscriber

The `/app/Events/ScheduledJobs` event subscriber will be added,
which allows for scheduled jobs to be added to the scheduler.
An example job is provided.

## Console commands

The following [console commands](../usage/console.md) can be used with relation to the scheduler:

- `php bones schedule:list`
- `php bones schedule:run`