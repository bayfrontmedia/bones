<?php

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\ModelException;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\Filesystem\Filesystem;
use Bayfront\PDO\Db;

abstract class Model
{

    /*
     * All models will extend this.
     */

    /**
     * @var Container
     */

    protected $container;

    /**
     * @var Filesystem
     */

    protected $filesystem;

    /**
     * @var Db
     */

    protected $db;

    /**
     * Model constructor.
     *
     * @throws ModelException
     */

    public function __construct()
    {

        $this->container = App::getContainer();

        try {

            if ($this->container->has('Bayfront\Filesystem\Filesystem')) {
                $this->filesystem = $this->container->get('Bayfront\Filesystem\Filesystem');
            }

            if ($this->container->has('Bayfront\PDO\DbFactory')) {
                $this->db = $this->container->get('Bayfront\PDO\DbFactory');
            }

            $this->container->get('Bayfront\Hooks\Hooks')->doEvent('app.model');

        } catch (NotFoundException $e) {

            throw new ModelException('Unable to construct model: ' . get_called_class(), 0, $e);

        }

    }

}