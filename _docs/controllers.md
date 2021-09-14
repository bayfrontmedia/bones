# Controllers

All controllers should reside in the `/app/Controllers` directory, and extend `Bayfront\Bones\Controller`.

Controllers may throw a `Bayfront\Bones\Exceptions\ControllerException` exception in the constructor.

If a controller needs its own constructor, be sure to invoke `parent::__construct()` within it. Keep in mind that controller constructors cannot accept any parameters.

Services available within a controller:

- Container as `$this->container`
- Filesystem as `$this->filesystem`
- HTTP response service as `$this->response`
- Veil template engine as `$this->veil` (if existing in container)

The `app.controller` event executes from within the controller's constructor.

Controllers should be instantiated via the router using defined routes in the `/resources/routes.php` file. Any parameters that need to be passed to the controller should be injected into the method via the router.

## Creating a new controller

The easiest way of creating a new controller is from the command line:

```
php /path/to/resources/cli.php
```