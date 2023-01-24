# Usage: Exceptions

All exceptions should reside in the `/app/Exceptions` directory, and extend `Bayfront\Bones\Exceptions\BonesException`.

Since all Bones exceptions extend `Bayfront\Bones\Exceptions\BonesException`, you can choose to catch exceptions 
as narrowly or broadly as you like.

With Bones, all PHP errors will be thrown as an `Bayfront\Bones\Exceptions\ErrorException` exception.

Whenever an exception is thrown, the `bones.exception` event will be executed, if able.
The exception and `response` service are passed as parameters to the event.

## Exception handler

Bones will automatically report and respond to exceptions.
The [whoops](https://github.com/filp/whoops) library is used to respond to exceptions when the app is in debug mode, 
or when running from the command line.

If an `Errors` controller exists, Bones will attempt to resolve the `errorNUM` method, where `NUM` corresponds 
to the HTTP status code of the exception. A `$data` array containing information related to the exception 
is passed to the method as a parameter.

If a matching controller/method is not found, a plaintext response will be shown.

You can customize how exceptions will be handled by creating a `Handler` class in the `/app/Exceptions/Handler` 
directory which can extend `Bayfront\Bones\Abstracts\ExceptionHandler` (if you want Bones to also handle the exception) 
and must implement `Bayfront\Bones\Interfaces\ExceptionHandlerInterface`.

If this class exists, it will override the default `Bayfront\Bones\Exceptions\Handler` class.

The `report` method allows you to customize how the exception will be reported (e.g., logged), 
and the `respond` method allows you to customize the exception's response.

Adding classes to the `$excluded_classes` array prevents them from being reported.

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
     * Fully namespaced exception classes to exclude from reporting.
     *
     * @var array $excluded_classes
     */

    protected $excluded_classes = [
        'Bayfront\Bones\Exceptions\HttpException'
    ];

    /**
     * @inheritDoc
     */

    public function getExcludedClasses(): array
    {
        return $this->excluded_classes;
    }

    /**
     * @inheritDoc
     */

    public function report(Response $response, Throwable $e): void
    {
        parent::report($response, $e);
    }

    /**
     * @inheritDoc
     */

    public function respond(Response $response, Throwable $e): void
    {
        parent::respond($response, $e);
    }

}
```

## Console commands

The following [console commands](console.md) can be used with relation to exceptions:

- `make:exception`