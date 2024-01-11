<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

interface ApiSchemaInterface
{

    /**
     * Returns an array conforming to the desired schema.
     *
     * @param array $array (Input array)
     * @param array $config (Optional array used to pass options necessary to build the schema)
     * @return array
     * @throws ApiExceptionInterface
     */
    public static function create(array $array, array $config = []): array;

}