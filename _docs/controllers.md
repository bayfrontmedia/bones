# Controllers

All controllers should reside in the `/app/Controllers` directory, and extend a [Bones controller](#bones-controllers).

[Bones Controllers](#bones-controllers) may throw a `Bayfront\Bones\Exceptions\ControllerException` exception in the constructor.

If a controller needs its own constructor, be sure to invoke `parent::__construct()` within it. Keep in mind that controller constructors cannot accept any parameters.

Controllers should be instantiated via the router using defined routes in the `/resources/routes.php` file. Any parameters that need to be passed to the controller should be injected into the method via the router.

## Bones controllers

Bones has multiple controllers available to extend, customized for a particular use.
Each executes its own event.

### General controller

> NOTE: All other Bones controllers extend this controller.
> This controller instantiates first.

**Class**

`Bayfront\Bones\Controller`

**Services available**

- Container as `$this->container`
- HTTP response as `$this->response`

**Event**

`app.controller`

### Web controller

**Class**

`Bayfront\Bones\Controllers\WebController`

**Services available**

- Veil template engine as `$this->veil` (if existing in container)

**Event**

`app.controller.web`

## Creating a new controller

The easiest way of creating a new controller is from the command line:

```
php /path/to/resources/cli.php
```