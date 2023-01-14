<?php

namespace Bayfront\Bones\Controllers;

use Bayfront\Bones\Controller;
use Bayfront\Bones\Exceptions\ControllerException;
use Bayfront\Container\NotFoundException;
use Bayfront\Veil\Veil;

abstract class WebController extends Controller
{

    /**
     * @var Veil $veil
     */

    protected $veil;

    /**
     * @throws ControllerException
     */

    public function __construct()
    {

        parent::__construct();

        try {

            if ($this->container->has('Bayfront\Veil\Veil')) { // Veil is optional, so check if it exists

                $this->veil = $this->container->get('Bayfront\Veil\Veil');

            }

            $this->container->get('Bayfront\Hooks\Hooks')->doEvent('app.controller.web');

        } catch (NotFoundException $e) {

            throw new ControllerException('Unable to construct controller: ' . get_called_class(), 0, $e);

        }

    }

}