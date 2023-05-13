<?php

namespace Bayfront\Bones\Services\Api\Models\Interfaces\Controllers;

interface ControllerRelationshipInterface
{

    /**
     * Add relationship to resource.
     *
     * @param array $args
     * @return void
     */
    public function add(array $args): void;

    /**
     * Get resource relationship collection.
     *
     * @param array $args
     * @return void
     */
    public function getCollection(array $args): void;

    /**
     * Remove relationship from resource.
     *
     * @param array $args
     * @return void
     */
    public function remove(array $args): void;

}