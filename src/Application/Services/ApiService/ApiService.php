<?php

namespace Bayfront\Bones\Application\Services\ApiService;

use Bayfront\Bones\Application\Services\Events\EventService;

class ApiService
{

    protected EventService $events;
    protected array $config;

    public function __construct(EventService $events, array $config)
    {
        $this->events = $events;
        $this->config = $config;
    }

}