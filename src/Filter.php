<?php

namespace Bayfront\Bones;

use Bayfront\Container\Container;

abstract class Filter
{

    /*
     * All Filters will extend this.
     */

    /**
     * @var Container $container
     */

    protected $container;

    /**
     * Filter constructor
     *
     */

    public function __construct()
    {
        $this->container = App::getContainer();
    }

}