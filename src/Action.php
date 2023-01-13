<?php

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\ActionException;
use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\Response;

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
     * @var Response $response
     */

    protected $response;

    /**
     * Controller constructor
     *
     * @throws ActionException
     */

    public function __construct()
    {

        $this->container = App::getContainer();

        try {

            $this->response = $this->container->get('response');

        } catch (NotFoundException $e) {

            throw new ActionException('Unable to construct action: ' . get_called_class(), 0, $e);

        }

    }

}