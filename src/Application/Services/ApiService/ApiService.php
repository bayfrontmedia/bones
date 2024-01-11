<?php

namespace Bayfront\Bones\Application\Services\ApiService;

use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\Bones\Application\Services\Filters\FilterService;
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
    }

    /**
     * Send API response.
     *
     * @param array $data
     * @return void
     */
    public function respond(array $data): void
    {
        $this->response->sendJson($this->filters->doFilter('api.response', $data));
    }

}