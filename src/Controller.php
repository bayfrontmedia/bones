<?php

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\ControllerException;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\Response;

abstract class Controller
{

    /*
     * All Controllers will extend this.
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
     * Controller constructor
     *
     * @throws ControllerException
     */

    public function __construct()
    {

        $this->container = App::getContainer();

        try {

            $this->response = $this->container->get('response');

            $this->container->get('hooks')->doEvent('app.controller');

        } catch (NotFoundException $e) {

            throw new ControllerException('Unable to construct controller: ' . get_called_class(), 0, $e);

        }

    }

}