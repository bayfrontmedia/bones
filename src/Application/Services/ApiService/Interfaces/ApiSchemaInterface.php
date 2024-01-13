<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiPathInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseSchemaInterface;

interface ApiSchemaInterface
{

    /**
     * Return an array of data conforming to the schema.
     *
     * @param ApiPathInterface $apiPath
     * @param ApiResponseInterface $apiResponse
     * @param ApiResponseSchemaInterface $apiResponseSchema
     * @param array $data
     * @return array
     */
    public static function create(ApiPathInterface $apiPath, ApiResponseInterface $apiResponse, ApiResponseSchemaInterface $apiResponseSchema, array $data): array;

}