<?php

namespace Bayfront\Bones\Services\Api\Controllers\Abstracts;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Container\NotFoundException as ContainerNotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

abstract class AuthApiController extends ApiController
{

    /**
     * @param EventService $events
     * @param FilterService $filters
     * @param Response $response
     * @throws ContainerNotFoundException
     * @throws HttpException
     * @throws InvalidStatusCodeException
     * @throws UnexpectedApiException
     */
    public function __construct(EventService $events, FilterService $filters, Response $response)
    {
        parent::__construct($events, $filters, $response);

        $this->initApi();

        $this->rateLimitOrAbort(md5('auth-' . Request::getIp()), App::getConfig('api.rate_limit.auth'));

        $events->doEvent('api.controller', $this);
    }

}