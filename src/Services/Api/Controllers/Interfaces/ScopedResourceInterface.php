<?php

namespace Bayfront\Bones\Services\Api\Controllers\Interfaces;

interface ScopedResourceInterface
{

    /**
     * Create resource.
     *
     * @param array $args
     * @return void
     */
    public function create(array $args): void;

    /**
     * Get resource collection.
     *
     * @param array $args
     * @return void
     */
    public function getCollection(array $args): void;

    /**
     * Get resource.
     *
     * @param array $args
     * @return void
     */
    public function get(array $args): void;

    /**
     * Update resource.
     *
     * @param array $args
     * @return void
     */
    public function update(array $args): void;

}