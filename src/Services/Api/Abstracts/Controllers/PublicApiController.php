<?php

namespace Bayfront\Bones\Services\Api\Abstracts\Controllers;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
use Exception;

abstract class PublicApiController extends ApiController
{

    /**
     * @param EventService $events
     * @param Response $response
     * @throws UnexpectedApiException
     * @throws HttpException
     * @throws NotFoundException
     * @throws InvalidStatusCodeException
     */
    public function __construct(EventService $events, Response $response)
    {

        parent::__construct($events, $response);

        $this->initApi();

        $this->rateLimitOrAbort(md5('public-' . Request::getIp()), App::getConfig('api.rate_limit.public'));

        $events->doEvent('api.controller', $this);

    }

}