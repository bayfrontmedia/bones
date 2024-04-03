<?php

namespace Bayfront\Bones\Interfaces;

use Bayfront\HttpResponse\Response;
use Throwable;

interface ExceptionHandlerInterface
{

    /**
     * Respond to exception.
     *
     * @param Response $response
     * @param Throwable $e
     *
     * @return void
     */

    public function respond(Response $response, Throwable $e): void;

}