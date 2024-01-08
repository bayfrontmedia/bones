<?php

namespace _namespace_\Services;

use Bayfront\Bones\Abstracts\Service;
use Bayfront\Bones\Application\Services\Events\EventService;

/**
 * _service_name_ service.
 *
 * Created with Bones v_bones_version_
 */
class _service_name_ extends Service
{

    protected EventService $events;

    /**
     * The container will resolve any dependencies.
     * EventService is required by the abstract service.
     *
     * @param EventService $events
     */

    public function __construct(EventService $events)
    {
        $this->events = $events;

        parent::__construct($events);
    }

}