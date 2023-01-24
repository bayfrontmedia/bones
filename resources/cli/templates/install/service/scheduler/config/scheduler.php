<?php

/*
 * For more information, see:
 * https://github.com/bayfrontmedia/cron-scheduler#usage
 */

use Bayfront\Bones\Application\Utilities\App;

return [
    'lock_file_path' => App::storagePath('/app/temp'),
    'output_file' => App::storagePath('/app/cron/cron-' . date('Y-m-d') . '.txt')
];