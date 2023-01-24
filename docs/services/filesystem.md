# Services: Filesystem

The [Filesystem Factory](https://github.com/bayfrontmedia/filesystem-factory) library is used
to manage read/write functions from multiple filesystems.

This library will be added to the service container with alias `filesystem` if a valid 
[configuration array](https://github.com/bayfrontmedia/filesystem-factory#configuration-array) exists as `/config/filesystem.php`.

**Example:**

```
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
``` 

## Installation

The filesystem service can be installed with the `install:service --filesystem` [console command](../usage/console.md).

Installing the filesystem service will perform the following actions:

### Add config file

A config file will be added to `/config/filesystem.php`. (See above example)