<?php

namespace _namespace_\Controllers;

use Bayfront\Bones\Abstracts\Controller;
use Bayfront\Bones\Application\Services\Events\EventService;
use Bayfront\HttpResponse\Response;

/**
 * _controller_name_ Controller.
 *
 * Created with Bones v_bones_version_
 */
class _controller_name_ extends Controller
{

    protected EventService $events;
    protected Response $response;

    /**
     * The container will resolve any dependencies.
     * EventService is required by the abstract controller.
     */
    public function __construct(EventService $events, Response $response)
    {
        $this->events = $events;
        $this->response = $response;

        parent::__construct($events);
    }

}