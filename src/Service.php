<?php

namespace Bayfront\Bones;

use Bayfront\Hooks\Hooks;

abstract class Service
{

    /*
     * All services will extend this.
     */

    /**
     * Service constructor.
     *
     */

    public function __construct(Hooks $hooks)
    {
        $hooks->doEvent('app.service', $this);
    }

}