<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020-2021 Bayfront Media
 */

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\ControllerException;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\HttpResponse\Response;
use Bayfront\Veil\Veil;

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
     * @var Veil $veil
     */

    protected $veil;

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

            $this->response = $this->container->get('response');

            if ($this->container->has('veil')) { // Veil is optional, so check if it exists

                $this->veil = $this->container->get('veil');

            }

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