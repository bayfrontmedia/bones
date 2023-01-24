# Services: Veil

[Veil](https://github.com/bayfrontmedia/veil) is used as the template engine.

This library will be added to the service container with alias `veil` when a valid configuration array 
exists at `/config/veil.php`.

**Example:**

```php
use Bayfront\Bones\Application\Utilities\App;

return [
    'base_path' => App::resourcesPath('/views'),
    'file_extension' => '.veil.php'
];
```

It is recommended to place all your views and template files in the `/resources/views` directory.

## Installation

The Veil service can be installed with the `install:service --veil` [console command](../usage/console.md).

Installing Veil will perform the following actions:

### Add config file

A config file will be added to `/config/veil.php` (See above example)

### Create controller

The `/app/Controllers/VeilExample` controller will be added, which provides an example of how to use Veil.

The `veil.data` filter is used from within this file to filter the value of the `$data` array passed to the view.

The `response.body` filter is used from within this file to filter the value of the response body.

> **NOTE:** Once Veil is installed, create a route which resolves to `VeilExample:index` to see it in action.

### Create views

Sample views are created at `/resources/views/examples`. These are used from within the `VeilExample` controller.