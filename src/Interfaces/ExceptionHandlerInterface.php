<?php

namespace Bayfront\Bones\Interfaces;

use Bayfront\HttpResponse\Response;
use Throwable;

interface ExceptionHandlerInterface
{

    /**
     * Return array of fully namespaced exception classes to exclude from reporting.
     *
     * @return array
     */

    public function getExcludedClasses(): array;

    /**
     * Report exception.
     *
     * @param Response $response
     * @param Throwable $e
     *
     * @return void
     */

    public function report(Response $response, Throwable $e): void;

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