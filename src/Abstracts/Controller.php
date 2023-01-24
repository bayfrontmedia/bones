<?php

namespace Bayfront\Bones\Abstracts;

use Bayfront\Bones\Application\Services\EventService;

abstract class Controller
{

    public function __construct(EventService $events)
    {
        $events->doEvent('app.controller', $this);
    }

}