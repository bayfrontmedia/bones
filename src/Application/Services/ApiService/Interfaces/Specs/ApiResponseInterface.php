<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs;

interface ApiResponseInterface
{

    /**
     * Get entire response object definition.
     *
     * @return array
     */
    public function getDefinition(): array;

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get array of headers to be added.
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Get schema name.
     *
     * @return string
     */
    public function getSchemaName(): string;

    /**
     * Get schema object.
     *
     * @return ApiSchemaObjectInterface
     */
    public function getSchemaObject(): ApiSchemaObjectInterface;

}