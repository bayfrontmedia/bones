<?php

namespace Bayfront\Bones\Services\Api\Models\Interfaces\Models;

interface ModelResourceInterface
{

    /**
     * Get required attributes.
     *
     * @return array
     */
    public function getRequiredAttrs(): array;

    /**
     * Get allowed attributes.
     *
     * @return array
     */
    public function getAllowedAttrs(): array;

    /**
     * Get validation rules for attributes.
     * @return array
     */
    public function getAttrsRules(): array;

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
     * Get total count of resources.
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Does resource ID exist?
     *
     * @param string $id
     * @return bool
     */
    public function idExists(string $id): bool;

    /**
     * Create resource.
     *
     * @param array $attrs
     * @return string
     */
    public function create(array $attrs): string;

    /**
     * Get multiple resources using a query builder.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/_docs/query-builder.md
     *
     * @param array $args (Allowed keys: select, where, orderBy, limit, offset)
     * @return array (Returned keys: data, meta)
     */
    public function getCollection(array $args = []): array;

    /**
     * Get single resource.
     *
     * @param string $id
     * @param array $cols
     * @return array
     */
    public function get(string $id, array $cols = ['*']): array;

    /**
     * Update resource.
     *
     * @param string $id
     * @param array $attrs
     * @return void
     */
    public function update(string $id, array $attrs): void;

    /**
     * Delete resource.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void;

}