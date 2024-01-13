<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

interface ApiSchemaInterface
{

    /**
     * Return an array of data conforming to the schema.
     *
     * @param ApiOperationObjectInterface $apiOperationObject
     * @param ApiResponseObjectInterface $apiResponseObject
     * @param ApiSchemaObjectInterface $apiSchemaObject
     * @param array $data
     * @return array
     */
    public static function create(ApiOperationObjectInterface $apiOperationObject, ApiResponseObjectInterface $apiResponseObject, ApiSchemaObjectInterface $apiSchemaObject, array $data): array;

}