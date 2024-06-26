# Usage: Exceptions

All exceptions should reside in the `/app/Exceptions` directory, and extend `Bayfront\Bones\Exceptions\BonesException`.

Since all Bones exceptions extend `Bayfront\Bones\Exceptions\BonesException`, you can choose to catch exceptions 
as narrowly or broadly as you like.

With Bones, all PHP errors will be thrown as an `Bayfront\Bones\Exceptions\ErrorException` exception.

Whenever an exception is thrown, the `bones.exception` event will be executed, if able.
The [response service](../services/response.md) and the exception are passed as parameters to the event.

## Exception handler

You can customize how exceptions will be handled by creating a `Handler` class in the `/app/Exceptions/Handler`
directory which can extend `Bayfront\Bones\Abstracts\ExceptionHandler` (if you also want Bones to handle the exception)
and must implement `Bayfront\Bones\Interfaces\ExceptionHandlerInterface`.

The `respond` method allows you to customize how the application responds to the exception.

If this class exists, it will override the default `Bayfront\Bones\Exceptions\Handler` class,
which utilizes `Bayfront\Bones\Abstracts\ExceptionHandler` to respond to the exception.

Example:

```php
<?php

namespace App\Exceptions;

use Bayfront\Bones\Abstracts\ExceptionHandler;
use Bayfront\Bones\Interfaces\ExceptionHandlerInterface;
use Bayfront\HttpResponse\Response;
use Throwable;

/**
 * Exception Handler.
 */
class Handler extends ExceptionHandler implements ExceptionHandlerInterface
{

    /**
     * @inheritDoc
     */

    public function respond(Response $response, Throwable $e): void
    {
        parent::respond($response, $e);
    }

}
```

### Respond

Bones `Bayfront\Bones\Abstracts\ExceptionHandler` uses the [whoops](https://github.com/filp/whoops) library to respond to exceptions 
when the app is in debug mode, or when running from the command line.

If an `Errors` controller exists, Bones will attempt to resolve the `errorNUM` method, where `NUM` corresponds 
to the HTTP status code of the exception. A `$data` array containing information related to the exception 
is passed to the method as a parameter.

If a matching controller/method is not found, a plaintext response will be shown.

## Console commands

The following [console commands](console.md) can be used with relation to exceptions:

- `php bones make:exception`