<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

interface ApiSpecInterface
{

    /**
     * Get entire specification array.
     *
     * @return array
     */
    public function getSpec(): array;

    /**
     * Get info key in dot notation.
     *
     * @param string $key (In dot notation)
     * @param mixed|null $default (Default value if not existing)
     * @return mixed
     */
    public function getInfo(string $key, mixed $default = null): mixed;

    /**
     * Get path.
     *
     * @param string $path
     * @param string $request_method
     * @return ApiPathInterface
     * @throws ApiExceptionInterface
     */
    public function getPath(string $path, string $request_method): ApiPathInterface;

    /**
     * Get schema definition.
     *
     * @param string $name (Case-sensitive schema name)
     * @return array
     * @throws ApiExceptionInterface
     */
    public function getSchema(string $name): array;

}