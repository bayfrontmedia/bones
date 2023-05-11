<?php

namespace Bayfront\Bones\Services\Api\Models\Interfaces;

interface RelationshipInterface
{

    /**
     * Get total count of relationships.
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Does relationship exist?
     *
     * @param string $relationship_id
     * @return bool
     */
    public function has(string $relationship_id): bool;

    /**
     * Add relationship.
     *
     * @param array $relationship_ids
     * @return void
     */
    public function add(array $relationship_ids): void;

    /**
     * Get multiple relationships.
     *
     * @param array $args
     * @return array
     */
    public function getCollection(array $args = []): array;

    /**
     * Remove relationship.
     *
     * @param array $relationship_ids
     * @return void
     */
    public function remove(array $relationship_ids): void;

}