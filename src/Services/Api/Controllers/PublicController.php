<?php

namespace Bayfront\Bones\Services\Api\Controllers;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\PublicApiController;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class PublicController extends PublicApiController
{

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @param Response $response
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws NotFoundException
     * @throws UnexpectedApiException
     */
    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        parent::__construct($events, $filters, $response);

    }

}