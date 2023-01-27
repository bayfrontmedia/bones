<?php

namespace Bayfront\Bones;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Exceptions\BonesConstructorException;
use Bayfront\Bones\Interfaces\BonesConstructorInterface;

class BonesConstructor implements BonesConstructorInterface
{

    /** @var array */

    protected array $config;

    /**
     * @param array $config
     * @throws BonesConstructorException
     */

    public function __construct(array $config)
    {

        if (Arr::isMissing($config, [
                'base_path',
                'public_path'
            ]) || !is_string($config['base_path'])
            || !is_string($config['public_path'])) {

            throw new BonesConstructorException('Unable to create class (' . __CLASS__ . '): Invalid config array');

        }

        $this->config = $config;

    }

    /**
     * @inheritDoc
     */

    public function getBasePath(): string
    {
        return $this->config['base_path'];
    }

    /**
     * @inheritDoc
     */

    public function getPublicPath(): string
    {
        return $this->config['public_path'];
    }
}