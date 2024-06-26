<?php /** @noinspection PhpRedundantMethodOverrideInspection */

namespace Bayfront\Bones\Exceptions;

use Bayfront\Bones\Abstracts\ExceptionHandler;
use Bayfront\Bones\Interfaces\ExceptionHandlerInterface;
use Bayfront\HttpResponse\Response;
use Throwable;

/**
 * Exception handler.
 *
 * This is used when an exception handler is not present in the app at:
 * Exceptions/Handler.php
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