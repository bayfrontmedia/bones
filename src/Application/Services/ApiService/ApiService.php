<?php

namespace Bayfront\Bones\Application\Services\ApiService;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;
use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Services\Filters\FilterService;
use Bayfront\HttpResponse\InvalidStatusCodeException;
use Bayfront\HttpResponse\Response;

class ApiService
{

    public EventService $events; // Needed by the Bones abstract controller (public)
    protected FilterService $filters;
    protected Response $response;
    protected array $config;

    public function __construct(EventService $events, FilterService $filters, Response $response, array $config)
    {
        $this->events = $events;
        $this->filters = $filters;
        $this->response = $response;
        $this->config = $config;

        $this->events->doEvent('api.start', $this);

    }

    /**
     * Send API response.
     *
     * @param array $data
     * @return void
     */
    public function respond(array $data): void
    {

        $response = $this->filters->doFilter('api.response', $data);

        $this->events->doEvent('api.end', $response);

        $this->response->sendJson($response);

    }

    /**
     * Handle an API exception and abort script execution.
     *
     * @param ApiExceptionInterface $e
     * @return never-return
     */
    public function abort(ApiExceptionInterface $e): void
    {

        try {

            $this->response->setStatusCode($e->getHttpStatusCode());

            /*
             * Do api.exception event
             *
             * Pass the exception and response as arguments to the event.
             */

            $this->events->doEvent('api.exception', $this->response, $e);

            throw $e;

        } catch (InvalidStatusCodeException) {
            throw $e;
        }

    }

}