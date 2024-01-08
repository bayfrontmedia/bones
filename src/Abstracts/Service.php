<?php

namespace Bayfront\Bones\Abstracts;

use Bayfront\Bones\Application\Services\Events\EventService;

abstract class Service
{

    public function __construct(EventService $events)
    {
        $events->doEvent('app.service', $this);
    }

}