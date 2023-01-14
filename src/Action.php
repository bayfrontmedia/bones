<?php

namespace Bayfront\Bones;

use Bayfront\Container\Container;

abstract class Action
{

    /*
     * All Actions will extend this.
     */

    /**
     * @var Container $container
     */

    protected $container;

    /**
     * Action constructor
     *
     */

    public function __construct()
    {
        $this->container = App::getContainer();
    }

}