<?php

/*
 * For more information, see:
 * https://github.com/bayfrontmedia/filesystem-factory#usage
 */

use Bayfront\Bones\Application\Utilities\App;

return [
    'local' => [ // Name of disk
        'default' => true, // One disk must be marked as default
        'adapter' => 'Local', // Class name in Bayfront\Filesystem\Adapters namespace
        'root' => App::storagePath(),
        'permissions' => [
            'file' => [
                'public' => 0644,
                'private' => 0600
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ]
    ]
];