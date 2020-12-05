# Filesystem

The [Filesystem Factory](https://github.com/bayfrontmedia/filesystem-factory) library is required by Bones.

This library exists in the services container as `filesystem`. 
A valid [configuration array](https://github.com/bayfrontmedia/filesystem-factory#configuration-array) must exist at `/config/filesystem.php`.

**Example:**
```
return [
    'local' => [ // Name of disk
        'default' => true, // One disk must be marked as default
        'adapter' => 'Local', // Class name in Bayfront\Filesystem\Adapters namespace
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
        'url' => 'https://www.example.com/path/to/root', // Optional key
        'cache' => [ // Optional key
            'location' => 'Memory' // Class name in Bayfront\Filesystem\Cache namespace
        ]
    ]
];
``` 