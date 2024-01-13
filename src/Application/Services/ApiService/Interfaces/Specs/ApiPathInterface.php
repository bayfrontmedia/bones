<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

interface ApiPathInterface
{

    /**
     * Get entire operation definition.
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
     * Get response object.
     *
     * @param string $name
     * @return ApiResponseInterface
     * @throws ApiExceptionInterface
     */
    public function getResponseObject(string $name): ApiResponseInterface;
}