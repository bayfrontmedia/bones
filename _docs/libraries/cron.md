# Cron jobs

The [Cron Scheduler](https://github.com/bayfrontmedia/cron-scheduler) library is used to manage cron jobs.

This library will be added to the services container as `cron` if the `/resources/cron.php` file is accessed.
This file is responsible for defining and scheduling all cron jobs.

**Example `cron.php`:**

```
use Bayfront\Container\NotFoundException;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\FilesystemException;

const IS_CRON = true;

require(dirname(__FILE__, 2) . '/public/index.php'); // Modify this path if necessary

/**
 * @var Cron $cron
 */

try {

    $cron = get_from_container('cron');

} catch (NotFoundException $e) {
    die($e->getMessage());
}

/*
 * ############################################################
 * Add cron jobs below
 * ############################################################
 */



/*
 * ############################################################
 * Stop adding cron jobs
 * ############################################################
 */

try {

    $result = $cron->run();

} catch (FilesystemException $e) {
    die($e->getMessage());
}

log_debug('Completed running ' . $result['count'] . ' cron jobs', [
    'result' => $result
]);
```

**Example crontab entry:**

```
* * * * * /path/to/php/bin /path/to/resources/cron.php > /dev/null 2>&1
```

This file is loaded immediately after the `app.bootstrap` event.
The `app.cron` event is fired immediately after the Cron Scheduler library is added to the container.

**NOTE:** When running as cron, the `bones.shutdown` event will not be executed unless called in the `/resources/cron.php` file.

## Configuration

In order to customize the handling of cron jobs, a configuration file must be located at `/resources/cron.php`.

**Example (default values):**

```
return [
    'lock_file_path' => storage_path('/app/temp'),
    'output_file' => storage_path('/app/cron/cron-' . date('Y-m-d') . '.txt')
];
```

All output from the cron jobs will be saved to the output file specified.
For more information, see [Cron Scheduler](https://github.com/bayfrontmedia/cron-scheduler#creating-an-instance).