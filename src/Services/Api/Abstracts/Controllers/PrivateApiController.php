<?php

namespace Bayfront\Bones\Services\Api\Abstracts\Controllers;

use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;
abstract class PrivateApiController extends ApiController
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

        $this->initApi();

        $this->rateLimitOrAbort(md5('private-' . Request::getIp()), App::getConfig('api.rate_limit.private'));

        $events->doEvent('api.controller', $this);
    }

}