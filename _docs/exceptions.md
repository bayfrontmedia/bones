# Exceptions

All Bones exceptions extend `Bayfront\Bones\Exceptions\BonesException`, so you can choose to catch exceptions as narrowly or broadly as you like.

With Bones, all PHP errors will be thrown as an `Bayfront\Bones\Exceptions\ErrorException` exception. 

Whenever an exception is thrown, the `bones.exception` event will be executed, if able.

You can create your own custom app-specific exceptions in the `/app/Exceptions` directory.
All exceptions must extend `Bayfront\Bones\Exceptions\BonesException`. 

## Creating a new exception

The easiest way of creating a new exception is from the command line:

```
php bones make:exception NAME
```

## Exception handler

Bones will automatically report and respond to exceptions. 
The [whoops](https://github.com/filp/whoops) library is used to respond to exceptions when the app is in debug mode, or when running from the command line.

If [Veil](libraries/views.md) exists in the services container, Bones will attempt to display a template from the `/resources/views/errors` directory that corresponds with the HTTP status code of the exception.
If not found, a plaintext response will be shown.

You can customize how exceptions will be handled by creating a `Handler` class in the `/app/Exceptions/Handler` directory which must extend `Bayfront\Bones\Exceptions\Handler\ExceptionHandler` and implement `Bayfront\Bones\Exceptions\Handler\HandlerInterface`.
If this class exists, it will override the default `Bayfront\Bones\Exceptions\Handler\Handler` class. 

The `report` method allows you to customize how the exception will be reported (e.g., logged), and the `respond` method allows you to customize the exception's response.
Adding classes to the `$excluded_classes` array prevents them from being reported.

**Example:**

```
namespace App\Exceptions\Handler;

use Bayfront\Bones\Exceptions\Handler\ExceptionHandler;
use Bayfront\Bones\Exceptions\Handler\HandlerInterface;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\Response;
use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;
use Throwable;

class Handler extends ExceptionHandler implements HandlerInterface
{

    /**
     * Fully namespaced exception class names to exclude from reporting.
     *
     * @var array $excluded_classes
     */

    protected $excluded_classes = [
        'Bayfront\Bones\Exceptions\HttpException'
    ];

    /**
     * Return array of fully namespaced exception classes to exclude from reporting.
     *
     * @return array
     */

    public function getExcludedClasses(): array
    {
        return $this->excluded_classes;
    }

    /**
     * Report exception.
     *
     * @param Throwable $e
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws ChannelNotFoundException
     */

    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Respond to exception.
     *
     * @param Response $response
     * @param Throwable $e
     *
     * @return void
     */

    public function respond(Response $response, Throwable $e): void
    {
        parent::respond($response, $e);
    }

}
```