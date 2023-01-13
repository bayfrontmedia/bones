# Scheduler

The [Cron Scheduler](https://github.com/bayfrontmedia/cron-scheduler) library is used to manage scheduled jobs, 
and is added to the services container as `schedule`.

Scheduled jobs are typically setup as a cron job on the server.

**Example crontab entry:**

```
* * * * * /path/to/php/bin cd /path/to/your/app && php bones schedule:run >> /dev/null 2>&1
```

The scheduled jobs will run immediately after the `app.cli` event.
The `app.schedule.start` event is fired just before the jobs are ran.
The `app.schedule.end` event fires just after the jobs are completed, and includes the `$result` as a parameter.

## Configuration

In order to customize the handling of scheduled jobs, a configuration file may be added at `/resources/scheduler.php`.

**Example (default values):**

```
return [
    'lock_file_path' => storage_path('/app/temp'),
    'output_file' => storage_path('/app/cron/cron-' . date('Y-m-d') . '.txt')
];
```

All output from the scheduled jobs will be saved to the output file specified.
For more information, see [Cron Scheduler](https://github.com/bayfrontmedia/cron-scheduler#creating-an-instance).