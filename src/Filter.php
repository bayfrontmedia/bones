<?php

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\FilterException;
use Bayfront\Bones\Interfaces\FilterInterface;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\Response;

abstract class Filter implements FilterInterface
{

    /*
     * All Filters will extend this.
     */

    /**
     * @var Container $container
     */

    protected $container;

    /**
     * @var Response $response
     */

    protected $response;

    /**
     * Filter constructor
     *
     * @throws FilterException
     */

    public function __construct()
    {

        $this->container = App::getContainer();

        try {

            $this->response = $this->container->get('response');

        } catch (NotFoundException $e) {

            throw new FilterException('Unable to construct filter: ' . get_called_class(), 0, $e);

        }

    }

}