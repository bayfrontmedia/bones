<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

interface ApiControllerInterface
{

    /**
     * Create new resource.
     *
     * @param array $params
     * @return void
     */
    public function create(array $params): void;

    /**
     * List a collection of resources.
     *
     * @param array $params
     * @return void
     */
    public function list(array $params): void;

    /**
     * Read a single resource.
     *
     * @param array $params
     * @return void
     */
    public function read(array $params): void;

    /**
     * Update a single existing resource.
     *
     * @param array $params
     * @return void
     */
    public function update(array $params): void;

    /**
     * Replace an entire single existing resource.
     *
     * @param array $params
     * @return void
     */
    public function replace(array $params): void;

    /**
     * Delete a single resource.
     *
     * @param array $params
     * @return void
     */
    public function delete(array $params): void;

}