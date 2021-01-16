<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020-2021 Bayfront Media
 */

namespace Bayfront\Bones;

use Bayfront\Bones\Exceptions\ModelException;
use Bayfront\Container\Container;
use Bayfront\Container\NotFoundException;
use Bayfront\PDO\Db;

abstract class Model
{

    /*
     * All models will extend this.
     */

    /**
     * @var Container $container
     */

    protected $container;

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

        if ($this->container->has('db')) { // db is optional, so check if it exists

            try {

                /*
                 * @throws Bayfront\Container\NotFoundException
                 */

                $this->db = $this->container->get('db');

            } catch (NotFoundException $e) {

                throw new ModelException('Unable to construct model: ' . get_called_class(), 0, $e);

            }

        }

    }

}