<?php

namespace Bayfront\Bones\Interfaces;

interface BonesConstructorInterface
{

    /**
     * Get base path.
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Get public path.
     *
     * @return string
     */
    public function getPublicPath(): string;

}