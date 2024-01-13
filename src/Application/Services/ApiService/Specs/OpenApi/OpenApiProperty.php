<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiPropertyInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseSchemaInterface;

class OpenApiProperty implements ApiPropertyInterface
{

    protected ApiResponseSchemaInterface $apiResponseSchema;

    public function __construct(ApiResponseSchemaInterface $apiResponseSchema)
    {
        $this->apiResponseSchema = $apiResponseSchema;
    }



}