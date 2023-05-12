<?php

namespace Bayfront\Bones\Services\Api\Controllers;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Services\FilterService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\HttpException;
use Bayfront\Bones\Services\Api\Abstracts\Controllers\ApiController;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\Bones\Services\Api\Schemas\StatusResource;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpRequest\Request;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class StatusController extends ApiController
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

        $this->rateLimitOrAbort(md5('public-' . Request::getIp()), App::getConfig('api.rate_limit.public'));

        $events->doEvent('api.controller', $this);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidStatusCodeException
     * @throws HttpException
     * @throws InvalidSchemaException
     */
    public function index(): void
    {

        $response = [
            'id' => date('c'),
            'status' => 'OK',
            'clientIp' => Request::getIp()
        ];

        $fields = $this->parseFieldsQueryOrAbort(Request::getQuery(), 'status', [
            'status',
            'clientIp'
        ], ['*']);

        if (!in_array('*', $fields)) {

            $fields[] = 'id'; // Required

            $response = Arr::only($response, $fields);

        }

        $schema = StatusResource::create($response);

        $this->response->setStatusCode(200)->setHeaders([
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate'
        ])->sendJson($this->filters->doFilter('api.response', $schema));

    }

}