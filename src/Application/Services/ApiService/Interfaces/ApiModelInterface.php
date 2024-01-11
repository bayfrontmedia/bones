<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

interface ApiModelInterface
{

    /**
     * Get allowed resource attributes.
     *
     * @return array
     */
    public function getAllowedAttributes(): array;

    /**
     * Get required resource attributes.
     *
     * @return array
     */
    public function getRequiredAttributes(): array;

    /**
     * Create a new resource.
     *
     * @param array $attributes
     * @return array
     * @throws ApiExceptionInterface
     */
    public function create(array $attributes): array;

    /**
     * List a collection of resources.
     *
     * @return array
     * @throws ApiExceptionInterface
     */
    public function list(): array;

    /**
     * Read a single resource.
     *
     * @param mixed $id
     * @return array
     * @throws ApiExceptionInterface
     */
    public function read(mixed $id): array;

    /**
     * Update a single existing resource.
     *
     * @param mixed $id
     * @return array
     * @throws ApiExceptionInterface
     */
    public function update(mixed $id): array;

    /**
     * Replace an entire single existing resource.
     *
     * @param mixed $id
     * @param array $attributes
     * @return array
     * @throws ApiExceptionInterface
     */
    public function replace(mixed $id, array $attributes): array;

    /**
     * Delete a single resource.
     *
     * @param mixed $id
     * @return bool
     * @throws ApiExceptionInterface
     */
    public function delete(mixed $id): bool;

}