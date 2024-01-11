<?php

namespace Bayfront\Bones\Application\Services\ApiService;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSchemaInterface;
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
     * @param ApiSchemaInterface $schema
     * @param array $schema_config
     * @return void
     */
    public function respond(array $data, ApiSchemaInterface $schema, array $schema_config = []): void
    {

        $response = (array)$this->filters->doFilter('api.response', $data); // Ensure returned from filter as an array

        $response = $schema::create($response, $schema_config);

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
            $this->events->doEvent('api.end', $this->response);

            throw $e;

        } catch (InvalidStatusCodeException) {
            throw $e;
        }

    }

}