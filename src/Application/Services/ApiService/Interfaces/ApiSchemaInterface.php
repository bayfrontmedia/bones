<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiPathInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseObjectInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiSchemaObjectInterface;

interface ApiSchemaInterface
{

    /**
     * Return an array of data conforming to the schema.
     *
     * @param ApiPathInterface $apiPath
     * @param ApiResponseObjectInterface $apiResponseObject
     * @param ApiSchemaObjectInterface $apiSchemaObject
     * @param array $data
     * @return array
     */
    public static function create(ApiPathInterface $apiPath, ApiResponseObjectInterface $apiResponseObject, ApiSchemaObjectInterface $apiSchemaObject, array $data): array;

}