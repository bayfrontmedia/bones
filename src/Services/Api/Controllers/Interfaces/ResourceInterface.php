<?php

namespace Bayfront\Bones\Services\Api\Controllers\Interfaces;

interface ResourceInterface
{

    /**
     * Create resource.
     *
     * @return void
     */
    public function create(): void;

    /**
     * Get resource collection.
     *
     * @return void
     */
    public function getCollection(): void;

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