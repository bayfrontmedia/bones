<?php

/*
 * For more information, see:
 * https://github.com/bayfrontmedia/filesystem-factory#configuration-array
 */

return [
    'local' => [ // Name of disk
        'default' => true,
        'adapter' => 'Local',
        'root' => storage_path(),
        'permissions' => [
            'file' => [
                'public' => 0644,
                'private' => 0600
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ],
        'url' => 'https://www.example.com/storage' // Optional. No trailing slash
    ]
];