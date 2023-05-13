<?php

namespace Bayfront\Bones\Services\Api\Controllers\Interfaces;

interface RelationshipInterface
{

    /**
     * Add resource relationship.
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
     * Remove resource relationship.
     *
     * @param array $args
     * @return void
     */
   public function remove(array $args): void;

}