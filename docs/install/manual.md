# Install: Manual

It is recommended you install a supported [Bones project](../../README.md#installation) to help get you started.

The following steps will take you through the process of manually installing the bare minimum Bones application.

## Add Bones to Composer

```shell
composer require bayfrontmedia/bones
```

Create an `app/` directory in the base path of your project, 
and ensure the following autoload directives are added to your `composer.json` file:

```
"autoload": {
  "psr-4": {
    "App\\": "app/"
  }
}
```

Any namespace other than `App` can be used, as long as the path to `app/` remains the same. 
Just be sure to update the `namespace` key in the app [configuration file](#add-required-config-file).

### Add required environment variables

The method in which you add environment variables may vary depending on your server setup.
Typically, this is done by creating an `.env` file at the base path of your project.

The [getEnv](../utilities/app.md#getenv) app utility can be used to retrieve environment variables from this file,
and this should typically only be done from within configuration files.

**Because the `.env` file contains sensitive information,
it should never be made public or committed to your application's source control**

```dotenv
APP_KEY=SECURE_APP_KEY
APP_DEBUG=true
# Valid environments: dev, staging, qa, prod
APP_ENVIRONMENT=dev
APP_TIMEZONE=UTC
# Optional path to deployment backups
APP_DEPLOY_BACKUP_PATH=/path/to/deployment/backups
```

> **NOTE:** Be sure to define a cryptographically secure app key for the APP_KEY variable.
One can be created using the `php bones key:create` command once Bones is installed.

### Add required config file

A config file is required at `/config/app.php`. For more information, see [app config](../usage/config.md).

### Add required bootstrap file

A bootstrap file is required at `/resources/bootstrap.php`. For more information, see [bootstrap](../usage/bootstrap.md).

### Starting the app

Bones runs via both the HTTP and CLI interfaces. Running Bones from the CLI interface allows you to utilize the [console commands](../usage/console.md).

In both of the examples below, the `$base_path` and `$public_path` may need to be updated, depending on your server setup.
In these examples, if the environment variables `APP_BASE_PATH` or `APP_PUBLIC_PATH` exist, they will be used.

#### CLI

Create a file named `bones` at the base path of your project with the following contents:

```php
#!/usr/bin/env php
<?php

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Bones;
use Bayfront\Bones\BonesConstructor;

// -------------------- Use Composer's autoloader --------------------

require __DIR__ . '/vendor/autoload.php';

// -------------------- Start app --------------------

$base_path = $_ENV['APP_BASE_PATH'] ?? dirname(__FILE__);
$public_path = $_ENV['APP_PUBLIC_PATH'] ?? $base_path . '/public';

$bones = new Bones(new BonesConstructor([
    'base_path' => $base_path,
    'public_path' => $public_path
]));

$bones->start(App::INTERFACE_CLI);
```

Technically, a `/public` directory does not have to exist if your application will only utilize the CLI interface.
Otherwise, ensure that `$public_path` points to the public web root of your project.

#### HTTP

When utilizing the HTTP interface, all web requests are handled by the `/public/index.php` file.

Create a file named `index.php` at the public web root of your project (`/public` by default) with the following contents:

```php
<?php

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Bones;
use Bayfront\Bones\BonesConstructor;

// -------------------- Use Composer's autoloader --------------------

require __DIR__ . '/../vendor/autoload.php';

// -------------------- Start app --------------------

$base_path = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__);
$public_path = $_ENV['APP_PUBLIC_PATH'] ?? $base_path . '/public';

$bones = new Bones(new BonesConstructor([
    'base_path' => $base_path,
    'public_path' => $public_path
]));

$bones->start(App::INTERFACE_HTTP);
```

Your web server must be configured to send all web requests to the `index.php` file.
If using Apache, you can create a file named `.htaccess` at the public web root of your project with the following contents:

```
# Hide open directories
Options -Indexes -Multiviews

<IfModule mod_rewrite.c>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect trailing slashes if not a folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Redirect all requests to the front controller unless it is an existing directory or file
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]

</IfModule>
```

In addition, it is recommended to create a symlink in your public web root to a directory used for public storage:

- Create the directory `/storage/public`
- Navigate to the public web root and type: `ln -s ../storage/public storage`

Or, change "storage" to whatever you want the public storage directory to be named.

### Installation complete

At this point, installation of Bones should be complete.

To utilize the CLI interface, navigate to the base path of your project and type the command: `php bones about:bones`.

To utilize the HTTP interface, navigate to the public web root of your project in a browser.
Unless the HTTP request is being handled by a [router](../services/router.md) or some other event subscriber, a blank page will be returned.