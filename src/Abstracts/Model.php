<?php

namespace Bayfront\Bones\Abstracts;

use Bayfront\Bones\Application\Services\EventService;

abstract class Model
{

    public function __construct(EventService $events)
    {
        $events->doEvent('app.model', $this);
    }

}