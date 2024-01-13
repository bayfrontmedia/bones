<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

interface ApiPathInterface
{

    /**
     * Get entire path definition.
     *
     * @return array
     */
    public function getDefinition(): array;

    /**
     * Get description of path.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get response for path.
     *
     * @param string $name
     * @return ApiResponseInterface
     * @throws ApiExceptionInterface
     */
    public function getResponse(string $name): ApiResponseInterface;
}