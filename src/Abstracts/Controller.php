<?php

namespace Bayfront\Bones\Abstracts;

use Bayfront\Bones\Application\Services\Events\EventService;

abstract class Controller
{

    public function __construct(EventService $events)
    {
        $events->doEvent('app.controller', $this);
    }

}