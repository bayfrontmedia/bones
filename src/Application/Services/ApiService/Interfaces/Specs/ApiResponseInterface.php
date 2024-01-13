<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs;

interface ApiResponseInterface
{

    /**
     * Get entire response definition.
     *
     * @return array
     */
    public function getDefinition(): array;

    /**
     * Get description of response.
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
     * Get schema name used for response.
     *
     * @return string
     */
    public function getSchemaName(): string;

    /**
     * Get response schema.
     *
     * @return ApiResponseSchemaInterface
     */
    public function getResponseSchema(): ApiResponseSchemaInterface;

}