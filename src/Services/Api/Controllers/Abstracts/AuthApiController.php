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

        // Restrict domains

        if (!empty(App::getConfig('api.auth.domains'))
            && !in_array(Request::getReferer(), App::getConfig('api.auth.domains'))) {
            App::abort(403, 'Domain not allowed', [], 10050);
        }

        // Restrict IPs

        if (!empty(App::getConfig('api.auth.ips'))
            && !in_array(Request::getIp(), App::getConfig('api.auth.ips'))) {
            App::abort(403, 'IP not allowed', [], 10051);
        }

        $this->rateLimitOrAbort(md5('auth-' . Request::getIp()), App::getConfig('api.rate_limit.auth'));
    }

}