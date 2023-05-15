<?php

namespace Bayfront\Bones\Services\Api\Models\Interfaces;

interface ScopedRelationshipInterface
{

    /**
     * Get allowed columns to be selected
     * where key = returned column name
     * and value = column to be selected.
     *
     * @return array
     */
    public function getSelectableCols(): array;

    /**
     * JSON-encoded columns.
     *
     * @return array
     */
    public function getJsonCols(): array;

    /**
     * Get total count of relationships.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @return int
     */
    public function getCount(string $scoped_id, string $resource_id): int;

    /**
     * Does relationship exist?
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param string $relationship_id
     * @return bool
     */
    public function has(string $scoped_id, string $resource_id, string $relationship_id): bool;

    /**
     * Add relationship.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     */
    public function add(string $scoped_id, string $resource_id, array $relationship_ids): void;

    /**
     * Get multiple relationships.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param array $args
     * @return array
     */
    public function getCollection(string $scoped_id, string $resource_id, array $args = []): array;

    /**
     * Remove relationship.
     *
     * @param string $scoped_id
     * @param string $resource_id
     * @param array $relationship_ids
     * @return void
     */
    public function remove(string $scoped_id, string $resource_id, array $relationship_ids): void;

}