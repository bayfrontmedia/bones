# Command line

The [Climate](https://github.com/thephpleague/climate) library is used to manage command line functionality.

This library will be added to the services container as `cli` if the `/resources/cli.php` file is accessed.

**Example `cli.php`:**

```
define('IS_CLI', true);

require(dirname(__FILE__, 2) . '/public/index.php'); // Modify this path if necessary
```

**Example usage:**

```
php /path/to/resources/cli.php
```

This file is loaded immediately after the `bones.init` event.
The `app.cli` event is fired immediately after the Climate library is added to the container.

**NOTE:** When running from the command line, the `bones.shutdown` event will not be executed unless called in the `/resources/cli.php` file.

Command line functionality includes:

- Create new controller
- Create new exception
- Create new model
- Create new service
- Create a secure key