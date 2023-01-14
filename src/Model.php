<?php

namespace Bayfront\Bones;

use Bayfront\Hooks\Hooks;

abstract class Model
{

    /*
     * All models will extend this.
     */

    /**
     * Model constructor.
     *
     */

    public function __construct(Hooks $hooks)
    {
        $hooks->doEvent('app.model');
    }

}