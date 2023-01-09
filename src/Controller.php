<?php

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\ControllerException;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\Filesystem\Filesystem;
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
     * @var Filesystem
     */

    protected $filesystem;

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

            /*
             * @throws Bayfront\Container\NotFoundException
             */

            $this->filesystem = $this->container->get('filesystem');

            $this->response = $this->container->get('response');

            /*
             * @throws Bayfront\Container\NotFoundException
             * @throws Bayfront\Hooks\EventException
             */

            $this->container->get('hooks')->doEvent('app.controller');

        } catch (NotFoundException $e) {

            throw new ControllerException('Unable to construct controller: ' . get_called_class(), 0, $e);

        }

    }

}