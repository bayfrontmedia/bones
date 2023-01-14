<?php

namespace Bayfront\Bones;

use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\Container\Container;

abstract class Action implements ActionInterface
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