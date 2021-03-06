<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020-2021 Bayfront Media
 */

namespace Bayfront\Bones\Exceptions\Handler;

use Bayfront\HttpResponse\Response;
use Throwable;

interface HandlerInterface
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
     * @param Throwable $e
     *
     * @return void
     */

    public function report(Throwable $e): void;

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